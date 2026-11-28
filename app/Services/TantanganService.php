<?php

namespace App\Services;

use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tantangan hemat (PBI 20): ikut, nilai, progres, total poin. Rumus di docs/pbi/PBI-20-tantangan-hemat.md.
 * Tanpa cron: peserta yang sudah lewat tanggal selesai dinilai saat halaman Tantangan / Leaderboard dibuka.
 * Hasil penilaian (status, poin_didapat, catatan, dinilai_pada) DISIMPAN karena spesifikasi memintanya.
 */
final class TantanganService
{
    public const CATATAN_DATA_KURANG = 'Data kurang';

    public const CATATAN_MELEBIHI_BATAS = 'Ada hari melebihi batas';

    public const CATATAN_TANPA_PEMBANDING = 'Tidak ada data pembanding';

    public const CATATAN_TIDAK_TERCAPAI = 'Target tidak tercapai';

    /** Toleransi pembulatan float saat membandingkan kWh (mis. 0,81 yang tersimpan 0,8100000001). */
    private const EPSILON = 1e-9;

    public function __construct(private KonsumsiService $konsumsi) {}

    /** Mulai hari ini; selesai = mulai + durasi − 1 (ikut 1 Okt, 7 hari → selesai 7 Okt). */
    public function ikuti(User $user, Tantangan $tantangan, ?CarbonInterface $hariIni = null): PesertaTantangan
    {
        $mulai = $this->hari($hariIni);

        return $user->pesertaTantangan()->create([
            'tantangan_id' => $tantangan->id,
            'mulai' => $mulai,
            'selesai' => $mulai->copy()->addDays($tantangan->durasi_hari - 1),
            'status' => PesertaTantangan::STATUS_BERJALAN,
            'poin_didapat' => 0,
        ]);
    }

    /** A1: user masih punya keikutsertaan berjalan di tantangan yang sama → tidak boleh ikut lagi. */
    public function sedangBerjalan(User $user, Tantangan $tantangan): bool
    {
        return $user->pesertaTantangan()
            ->where('tantangan_id', $tantangan->id)
            ->where('status', PesertaTantangan::STATUS_BERJALAN)
            ->exists();
    }

    /** Isi status, poin_didapat, catatan, dinilai_pada. kWh dari KonsumsiService::kwhPerTanggal. */
    public function nilai(PesertaTantangan $peserta): PesertaTantangan
    {
        $catatan = $this->alasanGagal($peserta);
        $berhasil = $catatan === null;

        $peserta->update([
            'status' => $berhasil ? PesertaTantangan::STATUS_BERHASIL : PesertaTantangan::STATUS_GAGAL,
            'poin_didapat' => $berhasil ? $peserta->tantangan->poin : 0,
            'catatan' => $catatan,
            'dinilai_pada' => now(),
        ]);

        return $peserta;
    }

    /** Nilai peserta berjalan milik $user yang tanggal selesainya < hari ini. Mengembalikan jumlah yang dinilai. */
    public function nilaiYangSelesai(User $user, ?CarbonInterface $hariIni = null): int
    {
        $daftar = $this->berjalanYangSelesai($user->pesertaTantangan()->getQuery(), $hariIni)
            ->each(fn (PesertaTantangan $peserta) => $this->nilai($peserta->setRelation('user', $user)));

        return $daftar->count();
    }

    /** Sama seperti nilaiYangSelesai tetapi untuk semua user (dipakai Leaderboard, PBI 21). */
    public function nilaiSemuaYangSelesai(?CarbonInterface $hariIni = null): int
    {
        $daftar = $this->berjalanYangSelesai(PesertaTantangan::query()->with('user'), $hariIni)
            ->each(fn (PesertaTantangan $peserta) => $this->nilai($peserta));

        return $daftar->count();
    }

    /**
     * hariKe dibatasi 1..durasi. Contoh: mulai 1 Okt, 7 hari, hari ini 3 Okt → hariKe 3, persenWaktu 42,857…
     *
     * @return array{hariKe: int, durasi: int, persenWaktu: float, keterangan: string}
     */
    public function progres(PesertaTantangan $peserta, ?CarbonInterface $hariIni = null): array
    {
        $durasi = $this->durasi($peserta);
        $hariKe = (int) $peserta->mulai->diffInDays($this->hari($hariIni)) + 1;
        $hariKe = max(1, min($durasi, $hariKe));

        return [
            'hariKe' => $hariKe,
            'durasi' => $durasi,
            'persenWaktu' => $hariKe / $durasi * 100,
            'keterangan' => "Hari {$hariKe} dari {$durasi}",
        ];
    }

    /** Jumlah poin_didapat dari tantangan yang berhasil (milik $user saja). */
    public function totalPoin(User $user): int
    {
        return (int) $user->pesertaTantangan()
            ->where('status', PesertaTantangan::STATUS_BERHASIL)
            ->sum('poin_didapat');
    }

    /** null = berhasil; string = alasan gagal (disimpan di kolom catatan). */
    private function alasanGagal(PesertaTantangan $peserta): ?string
    {
        $tantangan = $peserta->tantangan;
        $durasi = $this->durasi($peserta);
        $mulai = $peserta->mulai->toDateString();

        // persen_hemat butuh periode pembanding sama panjang tepat sebelum `mulai`, jadi diambil sekaligus.
        $awal = $tantangan->jenis_target === Tantangan::JENIS_PERSEN_HEMAT
            ? $peserta->mulai->copy()->subDays($durasi)
            : $peserta->mulai;
        $perTanggal = $this->konsumsi->kwhPerTanggal($peserta->user, $awal, $peserta->selesai);

        $periode = $perTanggal->filter(fn (float $kwh, string $tanggal) => $tanggal >= $mulai);
        $target = (float) $tantangan->nilai_target;

        // Minimal ceil(durasi ÷ 2) tanggal tercatat — untuk kedua jenis (persen_hemat: keputusan PO 28 Nov 2026).
        if ($periode->count() < (int) ceil($durasi / 2)) {
            return self::CATATAN_DATA_KURANG;
        }

        if ($tantangan->jenis_target === Tantangan::JENIS_MAKS_KWH_HARIAN) {
            return $periode->every(fn (float $kwh) => $kwh <= $target + self::EPSILON)
                ? null
                : self::CATATAN_MELEBIHI_BATAS;
        }

        // persen_hemat: kwhPeriode ≤ kwhSebelum × (1 − target ÷ 100)
        $kwhSebelum = $perTanggal->filter(fn (float $kwh, string $tanggal) => $tanggal < $mulai)->sum();
        if ($kwhSebelum <= self::EPSILON) {
            return self::CATATAN_TANPA_PEMBANDING;
        }

        return $periode->sum() <= $kwhSebelum * (1 - $target / 100) + self::EPSILON
            ? null
            : self::CATATAN_TIDAK_TERCAPAI;
    }

    /**
     * @param  Builder<PesertaTantangan>  $query
     * @return Collection<int, PesertaTantangan>
     */
    private function berjalanYangSelesai($query, ?CarbonInterface $hariIni): Collection
    {
        // whereDate + string supaya aman di SQLite (lihat catatan di model PesertaTantangan).
        return $query
            ->where('status', PesertaTantangan::STATUS_BERJALAN)
            ->whereDate('selesai', '<', $this->hari($hariIni)->toDateString())
            ->with('tantangan')
            ->get();
    }

    /** Durasi dari tanggal peserta (bukan tantangan.durasi_hari) supaya tetap benar kalau admin mengubah durasi. */
    private function durasi(PesertaTantangan $peserta): int
    {
        return (int) $peserta->mulai->diffInDays($peserta->selesai) + 1;
    }

    private function hari(?CarbonInterface $hariIni): Carbon
    {
        return Carbon::parse(($hariIni ?? now())->toDateString());
    }
}
