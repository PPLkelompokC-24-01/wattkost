{{-- Isian form alat standar; dipakai modal tambah ($katalog = null) & modal ubah ($katalog = data yang diubah). --}}
<x-input label="Nama Alat" name="nama_alat" :value="$katalog?->nama_alat" placeholder="Contoh: Dispenser" maxlength="100" />
<x-input label="Daya Default" name="daya_watt" type="number" :value="$katalog?->daya_watt" placeholder="350" suffix="W"
    min="1" max="10000" />
<x-select label="Kategori" name="kategori" stacked>
    <option value="">Pilih kategori</option>
    @foreach (config('wattkost.kategori_alat') as $kategori)
        <option value="{{ $kategori }}" @selected(old('kategori', $katalog?->kategori) === $kategori)>{{ $kategori }}</option>
    @endforeach
</x-select>
@if ($katalog)
    <p class="text-xs text-mute">Mengubah daya di katalog tidak mengubah alat yang sudah ditambahkan penghuni.</p>
@endif
