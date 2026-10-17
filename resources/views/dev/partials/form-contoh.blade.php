{{-- Isian form "Contoh CRUD"; dipakai modal tambah ($alat = null) & modal ubah ($alat = data yang diubah). --}}
<x-input label="Nama Alat" name="nama_alat" :value="$alat?->nama_alat" placeholder="Contoh: Rice Cooker" />
<x-select label="Kategori" name="kategori" stacked>
    <option value="">Pilih kategori</option>
    @foreach (config('wattkost.kategori_alat') as $kategori)
        <option value="{{ $kategori }}" @selected(old('kategori', $alat?->kategori) === $kategori)>{{ $kategori }}</option>
    @endforeach
</x-select>
<x-input label="Daya (W)" name="daya_watt" type="number" :value="$alat?->daya_watt" placeholder="300" suffix="W" />
