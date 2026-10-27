<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class KomponenUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_halaman_katalog_komponen_tampil(): void
    {
        $this->get(route('dev.komponen'))
            ->assertOk()
            ->assertSee('Katalog Komponen')
            ->assertSee('Contoh: Beranda')
            ->assertSee('Rp124.850');
    }

    public function test_ikon_dirender_dan_ikon_tidak_dikenal_jadi_bolt(): void
    {
        $this->blade('<x-icon name="plug" class="size-4" />')
            ->assertSee('M5 2v4M11 2v4', false)
            ->assertSee('size-4', false);

        $this->blade('<x-icon name="tidak-ada" />')
            ->assertSee('M9.5 1.8 4 9h4', false);
    }

    public function test_tombol_dengan_href_jadi_tautan_tanpa_href_jadi_button(): void
    {
        $this->blade('<x-button href="/token">Catat Token</x-button>')
            ->assertSee('<a href="/token"', false)
            ->assertSee('bg-aqua text-abyss', false);

        $this->blade('<x-button type="submit" variant="danger" small>Hapus</x-button>')
            ->assertSee('<button type="submit"', false)
            ->assertSee('text-coral', false)
            ->assertSee('h-8 px-3', false);
    }

    public function test_card_menampilkan_judul_dan_slot_action(): void
    {
        $this->blade(<<<'BLADE'
            <x-card title="Ringkasan">
                <x-slot:action>
                    <span>Aksi</span>
                </x-slot:action>
                Isi kartu
            </x-card>
            BLADE)
            ->assertSee('Ringkasan')
            ->assertSee('<span>Aksi</span>', false)
            ->assertSee('Isi kartu');
    }

    public function test_stat_card_highlight_dan_tautan_detail(): void
    {
        $this->blade('<x-stat-card label="Sisa kWh" value="42,5 kWh" note="Diperbarui" tone="highlight" href="/meteran" />')
            ->assertSee('from-deep to-surface', false)
            ->assertSee('href="/meteran"', false)
            ->assertSee('Lihat detail →');

        $this->blade('<x-stat-card label="Total" value="10" />')
            ->assertDontSee('Lihat detail')
            ->assertSee('bg-surface border-line', false);
    }

    public function test_stat_card_hanya_bisa_diklik_dan_naik_saat_hover_kalau_punya_href(): void
    {
        $this->blade('<x-stat-card label="Sisa kWh" value="42,5 kWh" href="/meteran" />')
            ->assertSee('href="/meteran"', false)
            ->assertSee('cursor-pointer', false)
            ->assertSee('hover:-translate-y-0.5', false)
            ->assertSee('Lihat detail →');

        $this->blade('<x-stat-card label="Sisa kWh" value="—" note="Belum ada data" />')
            ->assertDontSee('href=', false)
            ->assertDontSee('cursor-pointer', false)
            ->assertDontSee('hover:-translate-y-0.5', false)
            ->assertDontSee('Lihat detail');
    }

    public function test_layout_guest_ilustrasi_di_alur_normal_dan_tersembunyi_di_layar_pendek(): void
    {
        $html = (string) $this->blade('<x-layouts.guest title="Masuk">Form</x-layouts.guest>');

        $this->assertMatchesRegularExpression('/data-ilustrasi-login class="[^"]*\[@media\(max-height:699px\)\]:hidden/', $html);
        $this->assertStringNotContainsString('absolute w-[540px]', $html);
    }

    public function test_input_memakai_old_dan_menampilkan_error(): void
    {
        $this->blade('<x-input label="Daya" name="daya_watt" suffix="W" value="100" />')
            ->assertSee('name="daya_watt"', false)
            ->assertSee('value="100"', false)
            ->assertSee('W');

        $this->withViewErrors(['daya_watt' => 'Daya wajib diisi.'])
            ->blade('<x-input label="Daya" name="daya_watt" />')
            ->assertSee('Daya wajib diisi.')
            ->assertSee('text-coral', false);
    }

    public function test_textarea_select_dan_search_dirender(): void
    {
        $this->blade('<x-textarea label="Isi" name="isi" value="Halo" />')
            ->assertSee('<textarea name="isi"', false)
            ->assertSee('Halo');

        $this->blade('<x-select name="kategori" label="Kategori" stacked><option>Dapur</option></x-select>')
            ->assertSee('name="kategori"', false)
            ->assertSee('<option>Dapur</option>', false);

        $this->blade('<x-search placeholder="Cari alat..." />')
            ->assertSee('method="GET"', false)
            ->assertSee('name="q"', false);
    }

    public function test_tabs_menandai_url_yang_aktif(): void
    {
        $this->blade('<x-tabs :items="$items" />', ['items' => ['Harian' => url('/'), 'Bulanan' => url('/bulanan')]])
            ->assertSeeInOrder(['aria-current="page"', 'Harian', 'Bulanan'], false);
    }

    public function test_badge_sesuai_tone(): void
    {
        $this->blade('<x-badge tone="danger">Belum</x-badge>')->assertSee('bg-coral-soft text-coral', false);
        $this->blade('<x-badge tone="warning">Sedang</x-badge>')->assertSee('bg-amber-soft text-amber', false);
        $this->blade('<x-badge tone="neutral">Dibaca</x-badge>')->assertSee('bg-white/6 text-secondary', false);
        $this->blade('<x-badge>Lunas</x-badge>')->assertSee('bg-aqua-soft text-aqua', false);
    }

    public function test_progress_mengisi_kotak_sesuai_nilai(): void
    {
        $html = (string) $this->blade('<x-progress :value="50" :max="100" />');

        $this->assertSame(10, substr_count($html, 'from-aqua to-lime'));
        $this->assertSame(10, substr_count($html, 'bg-white/10'));
    }

    public function test_gauge_busur_mengikuti_persen(): void
    {
        $this->blade('<x-gauge value="42,5" :percent="61" />')
            ->assertSee('stroke-dasharray="61 100"', false)
            ->assertSee('42,5');

        $this->blade('<x-gauge value="0" :percent="150" compact />')
            ->assertSee('stroke-dasharray="100 100"', false)
            ->assertSee('size-24', false);
    }

    public function test_tabel_modal_toggle_checkbox_dan_lainnya_dirender(): void
    {
        $this->blade('<x-table :head="[\'Alat\', \'kWh\']" :align="[\'l\', \'r\']"><tr><td>AC</td></tr></x-table>')
            ->assertSee('overflow-x-auto', false)
            ->assertSee('<td>AC</td>', false)
            ->assertSeeInOrder(['text-left', 'Alat', 'text-right', 'kWh'], false);

        $this->blade('<x-modal id="m1" title="Tambah Alat">Isi<x-slot:footer><button>Simpan</button></x-slot:footer></x-modal>')
            ->assertSee('<dialog id="m1"', false)
            ->assertSee('method="dialog"', false)
            ->assertSee('Batal');

        $this->blade('<x-toggle name="aktif" checked />')->assertSee('checked', false);
        $this->blade('<x-checkbox name="email" label="Email" />')->assertSee('type="checkbox"', false)->assertSee('Email');
        $this->blade('<x-ilustrasi-rumah class="w-full" />')->assertSee('Ilustrasi rumah kost', false);
        $this->blade('<x-info-row label="Sisa">42,5 kWh</x-info-row>')->assertSeeInOrder(['Sisa', '42,5 kWh']);
        $this->blade('<x-alert tone="gagal">Gagal</x-alert>')->assertSee('role="alert"', false);
        $this->blade('<x-empty-state judul="Belum ada alat" href="/alat/create" aksi="Tambah" />')
            ->assertSee('Belum ada alat')
            ->assertSee('href="/alat/create"', false);
    }

    public function test_form_modal_menangani_csrf_method_dan_penanda_form(): void
    {
        $html = (string) $this->blade('<x-form-modal id="m-ubah" title="Ubah" action="/alat/1" method="put" form="ubah">Isi</x-form-modal>');

        $this->assertStringContainsString('<form id="m-ubah-form" method="POST" action="/alat/1"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('name="_form" value="ubah"', $html);
        $this->assertStringContainsString('form="m-ubah-form"', $html);
        $this->assertStringNotContainsString('data-buka-otomatis', $html);

        $tambah = (string) $this->blade('<x-form-modal id="m-tambah" title="Tambah" action="/alat">Isi</x-form-modal>');
        $this->assertStringNotContainsString('name="_method"', $tambah);
        $this->assertStringContainsString('name="_form" value="tambah"', $tambah);
    }

    public function test_form_modal_terbuka_kalau_diminta(): void
    {
        $this->blade('<x-form-modal id="m" title="Ubah" action="/x" form="ubah" buka>Isi</x-form-modal>')
            ->assertSee('data-buka-otomatis', false);
    }

    public function test_empty_state_bisa_membuka_modal(): void
    {
        $this->blade('<x-empty-state judul="Belum ada alat" modal="modal-tambah" aksi="+ Tambah" />')
            ->assertSee('data-buka-modal="modal-tambah"', false)
            ->assertDontSee('href=', false);
    }

    public function test_contoh_crud_menampilkan_daftar_dan_modal_tambah_tertutup(): void
    {
        $this->get(route('dev.komponen'))
            ->assertOk()
            ->assertSee('Contoh CRUD')
            ->assertSee('Rice cooker')
            ->assertSee('id="modal-tambah-contoh"', false)
            ->assertDontSee('id="modal-ubah-contoh"', false);
    }

    public function test_contoh_crud_parameter_ubah_membuka_modal_ubah_berisi_data(): void
    {
        $html = $this->get(route('dev.komponen', ['ubah' => 2]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<dialog id="modal-ubah-contoh"[^>]*data-buka-otomatis/', $html);
        $this->assertStringContainsString('action="'.route('dev.contoh.update', 2).'"', $html);
        $this->assertStringContainsString('value="Rice cooker"', $html);
    }

    public function test_contoh_crud_parameter_ubah_yang_tidak_ada_404(): void
    {
        $this->get(route('dev.komponen', ['ubah' => 99]))->assertNotFound();
    }

    public function test_contoh_crud_simpan_tidak_valid_kembali_dengan_error_dan_penanda_form(): void
    {
        $this->from(route('dev.komponen'))
            ->post(route('dev.contoh.store'), ['_form' => 'tambah', 'nama_alat' => '', 'daya_watt' => 0])
            ->assertRedirect(route('dev.komponen'))
            ->assertSessionHasErrors(['nama_alat', 'kategori', 'daya_watt'])
            ->assertSessionHasInput('_form', 'tambah');

        $html = $this->get(route('dev.komponen'))->assertSee('Kolom nama alat wajib diisi.')->getContent();
        $this->assertMatchesRegularExpression('/<dialog id="modal-tambah-contoh"[^>]*data-buka-otomatis/', $html);
    }

    public function test_contoh_crud_ubah_tidak_valid_membuka_modal_ubah_bukan_modal_tambah(): void
    {
        $halamanUbah = route('dev.komponen', ['ubah' => 1]);

        $this->from($halamanUbah)
            ->put(route('dev.contoh.update', 1), ['_form' => 'ubah', 'nama_alat' => 'Kipas baru', 'daya_watt' => ''])
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('daya_watt');

        $html = $this->get($halamanUbah)->getContent();
        $this->assertMatchesRegularExpression('/<dialog id="modal-ubah-contoh"[^>]*data-buka-otomatis/', $html);
        $this->assertDoesNotMatchRegularExpression('/<dialog id="modal-tambah-contoh"[^>]*data-buka-otomatis/', $html);
        $this->assertStringContainsString('value="Kipas baru"', $html); // old() menang atas data asli
    }

    public function test_contoh_crud_simpan_ubah_dan_hapus_valid(): void
    {
        $data = ['nama_alat' => 'Setrika', 'kategori' => 'Rumah Tangga', 'daya_watt' => 350];

        $this->post(route('dev.contoh.store'), $data)
            ->assertRedirect(route('dev.komponen').'#contoh-crud')
            ->assertSessionHas('sukses');
        $this->put(route('dev.contoh.update', 1), $data)->assertSessionHas('sukses');
        $this->put(route('dev.contoh.update', 99), $data)->assertNotFound();
        $this->delete(route('dev.contoh.destroy', 3))->assertSessionHas('sukses');
    }

    public function test_pilih_bulan_menampilkan_12_bulan_terakhir_dan_menandai_pilihan(): void
    {
        Carbon::setTestNow('2026-10-17');

        $html = $this->get(route('dev.komponen', ['bulan' => '2026-08', 'tab' => 'harian']))->getContent();

        $this->assertMatchesRegularExpression('/<option value="2026-08" selected>\s*Agustus 2026/', $html);
        $this->assertStringContainsString('Oktober 2026', $html);
        $this->assertStringContainsString('value="2025-11"', $html);   // bulan ke-12 ke belakang
        $this->assertStringNotContainsString('value="2025-10"', $html);
        $this->assertStringNotContainsString('value="2026-11"', $html);
        $this->assertStringContainsString('data-kirim-otomatis', $html);
        $this->assertStringContainsString('<input type="hidden" name="tab" value="harian">', $html);
    }

    public function test_pilih_bulan_nilai_masa_depan_menandai_bulan_berjalan(): void
    {
        Carbon::setTestNow('2026-10-17');

        $this->get(route('dev.komponen', ['bulan' => '2027-01']))
            ->assertSee('<option value="2026-10" selected>', false);
    }

    public function test_chart_mengirim_data_lewat_atribut_json(): void
    {
        $html = (string) $this->blade(
            '<x-chart type="bar" :labels="$labels" :datasets="$datasets" :tinggi="240" satuan="kWh" />',
            ['labels' => ['Sep', 'Okt'], 'datasets' => [['label' => 'Pemakaian', 'data' => ['a' => 94.4, 'b' => 86.4]]]],
        );

        $this->assertStringContainsString('style="height: 240px"', $html);
        preg_match('/data-chart="([^"]+)"/', $html, $cocok);
        $this->assertSame([
            'type' => 'bar',
            'labels' => ['Sep', 'Okt'],
            'datasets' => [['label' => 'Pemakaian', 'data' => [94.4, 86.4]]], // kunci array dibuang
            'satuan' => 'kWh',
        ], json_decode(html_entity_decode($cocok[1]), true));
    }

    public function test_chart_tipe_tidak_dikenal_jadi_garis(): void
    {
        $this->blade('<x-chart type="pie" :labels="[\'A\']" :datasets="[[\'label\' => \'X\', \'data\' => [1]]]" />')
            ->assertSee('&quot;type&quot;:&quot;line&quot;', false)
            ->assertSee('Grafik garis: X');
    }

    public function test_chart_data_kosong_menampilkan_empty_state(): void
    {
        $this->blade('<x-chart :labels="[]" :datasets="[]" kosong="Catat pemakaian dulu." />')
            ->assertDontSee('data-chart', false)
            ->assertSee('Belum ada data')
            ->assertSee('Catat pemakaian dulu.');

        $this->blade('<x-chart :labels="[\'Okt\']" :datasets="[[\'label\' => \'X\', \'data\' => []]]" />')
            ->assertDontSee('data-chart', false);
    }

    public function test_halaman_komponen_memuat_contoh_grafik(): void
    {
        $this->get(route('dev.komponen'))
            ->assertSee('data-chart=', false)
            ->assertSee('Catat pemakaian alat untuk melihat grafik.');
    }

    public function test_page_header_kosong_tidak_dirender(): void
    {
        $this->assertSame('', trim((string) $this->blade('<x-page-header />')));

        $this->blade('<x-page-header sub="Keterangan"><button>Aksi</button></x-page-header>')
            ->assertSee('Keterangan')
            ->assertSee('<button>Aksi</button>', false);
    }

    public function test_layout_guest_dirender(): void
    {
        $this->blade('<x-layouts.guest title="Masuk">Form login</x-layouts.guest>')
            ->assertSee('Listrik aman.')
            ->assertSee('Form login')
            ->assertSee('<title>Masuk · ', false);
    }

    public function test_layout_menampilkan_flash_sukses_dan_gagal(): void
    {
        session()->flash('sukses', 'Alat berhasil ditambahkan.');
        session()->flash('gagal', 'Ada yang salah.');

        $this->blade('<x-layouts.app title="Uji">Isi</x-layouts.app>')
            ->assertSee('Alat berhasil ditambahkan.')
            ->assertSee('Ada yang salah.');
    }
}
