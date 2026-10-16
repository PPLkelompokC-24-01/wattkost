{{-- Katalog komponen UI (hanya local/testing). Data di halaman ini contoh saja. Dihapus di Paket 2. --}}
<x-layouts.app title="Katalog Komponen">
    <x-page-header sub="Halaman pengembang: bandingkan setiap komponen dengan desain Figma.">
        <x-button variant="secondary" href="#beranda">Contoh Beranda</x-button>
        <x-button href="#komponen">Daftar Komponen</x-button>
    </x-page-header>

    {{-- ========== Contoh mini halaman Beranda (desain 01) ========== --}}
    <section id="beranda" class="scroll-mt-20">
        <h2 class="font-display text-[28px] font-semibold mb-4">Contoh: Beranda</h2>
        <x-page-header sub="Rabu, 08 Oktober 2026 · Kost Contoh, Kamar A01">
            <x-button variant="secondary" href="#">Catat Pemakaian</x-button>
            <x-button href="#">+ Catat Token</x-button>
        </x-page-header>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
            <x-stat-card label="Sisa kWh" :value="kwh(42.5, 1)" note="Diperbarui 08 Okt, 07.30" icon="bolt"
                tone="highlight" href="#" />
            <x-stat-card label="Prediksi token habis" value="6 hari lagi" note="Estimasi 14 Okt 2026" icon="receipt"
                href="#" />
            <x-stat-card label="Pemakaian bulan ini" :value="kwh(86.4, 1)" :note="rupiah(124850).' estimasi biaya'"
                icon="chart" href="#" />
            <x-stat-card label="Alat terboros" value="AC — 38,2 kWh" note="44% dari total" icon="plug" href="#" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <x-card title="Pemakaian Listrik 30 Hari" class="lg:col-span-2">
                <x-slot:action>
                    <x-button variant="link" href="#">Buka Dashboard →</x-button>
                </x-slot:action>
                <div
                    class="h-80 rounded-2xl bg-abyss/45 border border-line p-3 grid place-items-center text-sm text-mute">
                    Grafik Chart.js dengan data asli menyusul (bukan bagian paket ini)
                </div>
            </x-card>
            <x-card title="Ringkasan Pemakaian">
                <div class="rounded-2xl bg-gradient-to-br from-deep to-abyss border border-line overflow-hidden mb-4">
                    <x-ilustrasi-rumah class="w-full h-36" />
                </div>
                <x-info-row label="Pemakaian bulan ini">{{ kwh(86.4, 1) }}</x-info-row>
                <x-info-row label="Alat terboros">AC · 44%</x-info-row>
                <x-info-row label="Token diperkirakan habis">6 hari</x-info-row>
                <div class="flex flex-col gap-2 mt-4">
                    <x-button variant="secondary" href="#">Lihat Rekomendasi Hemat</x-button>
                    <x-button variant="secondary" href="#">Cek Peringatan Lonjakan</x-button>
                    <x-button variant="secondary" href="#">Lihat Jejak Karbon</x-button>
                </div>
            </x-card>
        </div>
    </section>

    {{-- ========== Semua komponen ========== --}}
    <section id="komponen" class="scroll-mt-20 mt-12 flex flex-col gap-6">
        <h2 class="font-display text-[28px] font-semibold">Daftar Komponen</h2>

        <x-card title="Alert (flash sukses/gagal)">
            <div class="flex flex-col gap-3">
                <x-alert tone="sukses">Alat berhasil ditambahkan.</x-alert>
                <x-alert tone="gagal">Data gagal disimpan.</x-alert>
                <x-alert tone="info">Token diperkirakan habis dalam 3 hari.</x-alert>
            </div>
        </x-card>

        <x-card title="Ikon (14)">
            <div class="grid grid-cols-4 sm:grid-cols-7 gap-4 text-xs text-secondary">
                @foreach (['bolt', 'home', 'plug', 'calendar', 'receipt', 'chart', 'target', 'trophy', 'file', 'building', 'bell', 'search', 'logout', 'leaf'] as $ikon)
                    <div class="flex flex-col items-center gap-2">
                        <span class="size-9 rounded-xl grid place-items-center bg-aqua-soft text-aqua">
                            <x-icon :name="$ikon" />
                        </span>
                        {{ $ikon }}
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Tombol">
            <div class="flex flex-wrap items-center gap-3">
                <x-button>Primary</x-button>
                <x-button variant="secondary">Secondary</x-button>
                <x-button variant="danger">Danger</x-button>
                <x-button variant="hero">Hero</x-button>
                <x-button variant="link" href="#">Link →</x-button>
                <x-button small variant="secondary">Edit</x-button>
                <x-button small variant="danger">Hapus</x-button>
                <x-button small>Tandai Lunas</x-button>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-stat-card label="Total Konsumsi" value="86,4 kWh/bulan" icon="chart" />
            <x-stat-card label="Estimasi Biaya" :value="rupiah(124850)" :note="'Tarif '.rupiah(1444.7, 2).'/kWh'"
                tone="highlight" />
            <x-stat-card label="Alat Terboros" value="AC" note="38,2 kWh · 44% dari total" big icon="plug" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Form: input, select, textarea">
                <div class="flex flex-col gap-4">
                    <x-input label="Nama Alat" name="demo_nama" placeholder="Contoh: Rice Cooker" required />
                    <x-input label="Daya (W)" name="demo_daya" type="number" placeholder="300" suffix="W" />
                    <x-input label="Tanggal" name="demo_tanggal" type="date" />
                    <x-select label="Kategori" name="demo_kategori" stacked>
                        <option value="">Pilih kategori</option>
                        <option>Dapur</option>
                        <option>Pendingin</option>
                    </x-select>
                    <x-textarea label="Isi Tips" name="demo_isi" placeholder="Tuliskan isi tips hemat..." />
                </div>
            </x-card>
            <x-card title="Filter, cari, tab">
                <div class="flex flex-col gap-4 items-start">
                    <x-select label="Periode">
                        <option>Jul – Okt 2026</option>
                        <option>Jan – Jun 2026</option>
                    </x-select>
                    <x-search placeholder="Cari alat..." />
                    <x-tabs :items="[
                        'Komponen' => route('dev.komponen'),
                        'Harian' => route('dev.komponen', ['periode' => 'harian']),
                        'Bulanan' => route('dev.komponen', ['periode' => 'bulanan']),
                    ]" />
                    <div class="flex items-center justify-between w-full py-2">
                        <span class="text-sm font-medium">Aktifkan Pengingat</span>
                        <x-toggle name="demo_aktif" checked label="Aktifkan Pengingat" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <x-checkbox name="demo_kanal[]" value="aplikasi" label="Notifikasi aplikasi" checked />
                        <x-checkbox name="demo_kanal[]" value="email" label="Email" />
                    </div>
                </div>
            </x-card>
        </div>

        <x-card title="Badge, progress, gauge">
            <div class="flex flex-wrap gap-3 mb-6">
                <x-badge tone="good">Lunas</x-badge>
                <x-badge tone="warning">Sedang</x-badge>
                <x-badge tone="danger">Belum</x-badge>
                <x-badge tone="neutral">Dibaca</x-badge>
            </div>
            <x-progress :value="42.5" :max="69.2" />
            <div class="flex flex-wrap items-end gap-8 mt-6">
                <x-gauge value="42,5" :percent="61" />
                <x-gauge value="42,5" :percent="61" compact />
                <x-gauge value="0" :percent="0" compact />
            </div>
        </x-card>

        <x-card title="Tabel">
            <x-table :head="['Alat', 'Daya', 'kWh/Bulan', 'Aksi']" :align="['l', 'r', 'r', 'l']">
                <tr>
                    <td class="font-medium">AC</td>
                    <td class="text-right">500 W</td>
                    <td class="text-right">{{ angka(90) }}</td>
                    <td>
                        <div class="flex gap-2"><x-button small variant="secondary">Edit</x-button><x-button small
                                variant="danger">Hapus</x-button></div>
                    </td>
                </tr>
                <tr data-sorot>
                    <td class="font-medium">Kulkas (baris disorot)</td>
                    <td class="text-right">100 W</td>
                    <td class="text-right">{{ angka(12) }}</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td class="font-medium">Laptop</td>
                    <td class="text-right">65 W</td>
                    <td class="text-right">{{ angka(8.58) }}</td>
                    <td>—</td>
                </tr>
            </x-table>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Info row & modal">
                <x-info-row label="Tarif listrik">{{ rupiah(1444.7, 2) }}/kWh</x-info-row>
                <x-info-row label="Daya tersedia">1.300 VA</x-info-row>
                <x-info-row label="Jumlah kamar">6 kamar</x-info-row>
                <div class="mt-4">
                    <x-button data-buka-modal="modal-demo">+ Tambah Alat</x-button>
                </div>
            </x-card>
            <x-card title="Empty state">
                <x-empty-state judul="Belum ada alat" pesan="Tambahkan alat pertama untuk mulai menghitung pemakaian."
                    href="#" aksi="+ Tambah Alat" icon="plug" />
            </x-card>
        </div>

        <x-card title="Receipt (Card dengan class receipt)" class="receipt">
            <p class="text-sm text-secondary">Tepi berlubang seperti struk, dipakai di form Catat Pembelian Token.</p>
        </x-card>
    </section>

    <x-modal id="modal-demo" title="Tambah Alat">
        <x-input label="Nama Alat" name="modal_nama" placeholder="Contoh: Rice Cooker" />
        <x-input label="Daya (W)" name="modal_daya" placeholder="300" suffix="W" />
        <x-input label="Jumlah" name="modal_jumlah" placeholder="1" />
        <x-slot:footer>
            <x-button>Simpan</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
