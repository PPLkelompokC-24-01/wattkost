<?php

namespace Tests\Feature;

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
