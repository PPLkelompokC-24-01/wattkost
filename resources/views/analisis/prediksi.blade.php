{{-- PBI 8 — Prediksi Token Habis. Angka dari PrediksiTokenService, warna & saran dari controller; view hanya memformat. --}}
@php
    $teksSumber = [
        'meteran' => 'Dari pembacaan meteranmu',
        'pemakaian' => 'Dari catatan pemakaian alat',
        'default' => 'Perkiraan awal, isi data lebih banyak agar akurat',
    ];
    $teksHari = match (true) {
        $hasil === null => null,
        $sisaHari > 0 => '± '.$sisaHari.' hari lagi',
        $hasil->sisaKwh > 0 => 'Kurang dari 1 hari lagi',
        default => 'Token sudah habis',
    };
@endphp

<x-layouts.app title="Prediksi Token Habis">
    <x-page-header sub="Perkiraan kapan token listrikmu habis berdasarkan sisa kWh terakhir.">
        <x-button :href="route('token.index')">Catat Pembelian</x-button>
    </x-page-header>

    @if ($hasil === null)
        <x-card>
            <x-empty-state judul="Belum ada data sisa kWh" pesan="Input sisa kWh dari meteranmu dulu supaya kapan token habis bisa diperkirakan."
                :href="route('meteran.index')" aksi="Input Sisa kWh" icon="bolt" />
        </x-card>
    @else
        <div class="flex flex-col gap-3 mb-6">
            @if ($hasil->sisaKwh <= 0)
                <x-alert tone="gagal">Token sudah habis, segera beli token.</x-alert>
            @endif
            @if ($perluDiperbarui)
                <x-alert tone="info">
                    Perbarui sisa kWh agar prediksi akurat. Pembacaan terakhir {{ $hasil->dicatatPada->translatedFormat('d M Y') }}.
                    <a href="{{ route('meteran.index') }}" class="underline">Input sisa kWh</a>
                </x-alert>
            @endif
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            <x-stat-card class="sm:col-span-2" label="Perkiraan token habis" icon="calendar" tone="highlight" big
                :value="$hasil->perkiraanHabis->translatedFormat('l, d M Y')"
                :note="$teksHari" />

            <x-card title="Info">
                <div class="mb-3">
                    <x-badge :tone="$warna">{{ $teksHari }}</x-badge>
                </div>
                <x-info-row label="Sisa kWh terakhir">{{ kwh($hasil->sisaKwh) }}</x-info-row>
                <x-info-row label="Dicatat pada">{{ $hasil->dicatatPada->translatedFormat('d M Y, H:i') }}</x-info-row>
                <x-info-row label="Rata-rata pemakaian">{{ kwh($hasil->rataKwhHarian) }}/hari</x-info-row>
                <x-info-row label="Sumber perhitungan">{{ $teksSumber[$hasil->sumber] }}</x-info-row>
            </x-card>
        </div>
    @endif
</x-layouts.app>
