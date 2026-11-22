# PBI-31 Kelola Notifikasi (RUD) — PBI BARU
**Pemegang:** Luthfan (P3) · **Jira:** SCRUM-198 · **UC:** UC-31 (baru) · **Mockup:** belum ada; lonceng ikut gaya header, halaman ikut gaya tabel `Pengingat Terakhir` di fungsi `Pengingat` (`docs/desain/figma-make/pages.txt`)
**Bergantung pada:** tabel `notifications` (PR M2, Puti), komponen `x-lonceng-notifikasi` di header (PR C2, P4).
**Ditunggu oleh:** PBI 09 Pengingat Token dan PBI 22 Deteksi Lonjakan (Beden). **Harus merge paling lambat Sabtu 28 Nov.**

## User story
Sebagai **penghuni kost**, saya ingin melihat, menandai dibaca, dan menghapus notifikasi di lonceng, supaya tidak ketinggalan pengingat penting.

## Use case scenario singkat (UC-31)
- **Pre:** penghuni sudah login. **Post:** status baca/hapus notifikasi tersimpan.
- **Standard:** sistem menampilkan jumlah notifikasi belum dibaca di lonceng → penghuni membuka lonceng → sistem menampilkan daftar notifikasi terbaru → penghuni membuka satu notifikasi → sistem menandainya dibaca dan membuka halaman tujuannya.
- **A1** Belum ada notifikasi → empty state "Belum ada notifikasi". **A2** Tandai semua dibaca. **A3** Hapus satu notifikasi / hapus semua yang sudah dibaca (dengan konfirmasi).

## Tabel `notifications` (bawaan Laravel, dibuat di PR M2 lewat `php artisan make:notifications-table`)
Tidak ada tabel buatan sendiri. Model `User` sudah memakai trait `Notifiable`, jadi relasi `$user->notifications()` dan `$user->unreadNotifications()` sudah tersedia.

## Kontrak notifikasi (dipakai semua PBI yang mengirim notifikasi)
Kelas dasar `App\Notifications\NotifikasiWattKost` (abstract, dibuat Luthfan):
```php
abstract class NotifikasiWattKost extends Notification
{
    abstract public function jenis(): string;    // 'pengingat_token' | 'lonjakan' | ...
    abstract public function judul(): string;    // "Token hampir habis"
    abstract public function pesan(): string;    // 1–2 kalimat
    public function url(): ?string { return null; }        // halaman tujuan saat diklik
    public function kirimEmail(object $notifiable): bool { return false; }

    /** Selalu 'database'; tambah 'mail' kalau kirimEmail() true. Tanpa queue (sinkron). */
    public function via(object $notifiable): array;
    /** ['jenis' => ..., 'judul' => ..., 'pesan' => ..., 'url' => ...] */
    public function toArray(object $notifiable): array;
    /** MailMessage sederhana: subject = judul, line = pesan, action = url (kalau ada). */
    public function toMail(object $notifiable): MailMessage;
}
```
PBI 09 dan 22 membuat turunan kelas ini di `app/Notifications/` milik mereka. Email: `MAIL_MAILER=log` saat develop, Mailtrap saat demo. Kredensial hanya di `.env` lokal.

## Service: `NotifikasiService` (Luthfan)
```php
public function jumlahBelumDibaca(User $user): int;
/** paginate 15, terbaru dulu */
public function daftar(User $user): LengthAwarePaginator;
public function tandaiDibaca(User $user, string $id): DatabaseNotification;  // 404 kalau bukan milik user
public function tandaiSemuaDibaca(User $user): int;                         // jumlah yang diubah
public function hapus(User $user, string $id): void;                        // 404 kalau bukan milik user
public function hapusYangSudahDibaca(User $user): int;
```
Semua query lewat `$user->notifications()`, sehingga notifikasi user lain otomatis tidak ketemu (404).

## Route (`routes/modules/notifikasi.php`, middleware `auth`)
| Method | URL | Name | Keterangan |
|---|---|---|---|
| GET | /notifikasi | notifikasi.index | daftar |
| PATCH | /notifikasi/{id}/baca | notifikasi.baca | tandai dibaca lalu redirect ke `url` notifikasi (atau kembali ke daftar) |
| PATCH | /notifikasi/baca-semua | notifikasi.baca-semua | |
| DELETE | /notifikasi/{id} | notifikasi.destroy | |
| DELETE | /notifikasi | notifikasi.bersihkan | hapus semua yang sudah dibaca |

`{id}` adalah UUID (`whereUuid('id')`). Tidak ada FormRequest karena tidak ada input selain id.

## Tampilan
- **Lonceng** (`resources/views/components/lonceng-notifikasi.blade.php`, kerangkanya dibuat P4 di PR C2, isinya diteruskan Luthfan): ikon lonceng + `x-badge` angka belum dibaca (disembunyikan kalau 0, tampil "9+" kalau lebih dari 9). Klik → `notifikasi.index`.
- **Halaman** `resources/views/notifikasi/index.blade.php`: `x-page-header` "Notifikasi" + tombol "Tandai semua dibaca" dan "Hapus yang sudah dibaca". Tabel: tanggal (`translatedFormat('d M Y H:i')`), judul (tebal kalau belum dibaca), pesan, status `x-badge` Baru/Dibaca, aksi Buka & Hapus.
- Pesan flash: "Semua notifikasi ditandai dibaca.", "Notifikasi dihapus.", "3 notifikasi yang sudah dibaca dihapus."

## Contoh untuk test
Buat notifikasi lewat kelas uji di `tests/` (turunan `NotifikasiWattKost`) atau `$user->notifications()->create([...])`.
- penghuni1 punya 3 notifikasi, 2 belum dibaca → lonceng menampilkan **2**.
- Tandai semua dibaca → `tandaiSemuaDibaca()` mengembalikan **2**, lonceng hilang angkanya.
- Hapus yang sudah dibaca → tersisa **0**.

## Kriteria penerimaan
- [ ] Lonceng menampilkan jumlah belum dibaca yang benar di semua halaman
- [ ] Lihat daftar, tandai satu/semua dibaca, hapus satu, hapus yang sudah dibaca
- [ ] Notifikasi milik user lain tidak bisa dibaca/ditandai/dihapus (404)
- [ ] Kelas `NotifikasiWattKost` siap dipakai PBI 09 dan 22 (via database selalu, mail opsional)

## Test wajib
Feature: tamu → login; halaman tampil; empty state A1; `test_tandai_satu_dibaca_dan_redirect_ke_url`; `test_tandai_semua_dibaca`; `test_hapus_notifikasi`; `test_hapus_yang_sudah_dibaca`; `test_notifikasi_user_lain_404` (baca & hapus); `test_lonceng_menampilkan_jumlah_belum_dibaca`.
Unit `NotifikasiServiceTest`: contoh angka di atas; `NotifikasiWattKostTest`: `via()` hanya `database` saat `kirimEmail` false, `database` + `mail` saat true, `toArray()` berisi 4 kunci.

## Skenario Selenium (`tests/selenium/test_pbi_31_notifikasi.py`)
Siapkan notifikasi lewat seeder `NotifikasiSeeder` (2 notifikasi belum dibaca untuk penghuni1, dibuat Luthfan).
1. Login penghuni1 → lonceng menampilkan angka 2.
2. Klik lonceng → halaman Notifikasi tampil 2 baris berstatus "Baru".
3. Klik "Tandai semua dibaca" → pesan sukses, angka lonceng hilang.
4. Klik "Hapus yang sudah dibaca" → konfirmasi → empty state "Belum ada notifikasi".
