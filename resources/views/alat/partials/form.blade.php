{{-- Isian form alat; dipakai modal tambah ($alat = null) & modal ubah ($alat = data yang diubah). --}}
<x-select label="Pilih dari katalog (opsional)" name="katalog_alat_id" stacked data-pilih-katalog>
    <option value="">— isi manual —</option>
    @foreach ($katalog as $item)
        <option value="{{ $item->id }}" @selected((string) old('katalog_alat_id', $alat?->katalog_alat_id) === (string) $item->id)>
            {{ $item->nama_alat }} ({{ $item->daya_watt }} W)
        </option>
    @endforeach
</x-select>
<x-input label="Nama Alat" name="nama_alat" :value="$alat?->nama_alat" placeholder="Contoh: Rice cooker" maxlength="100" />
<x-select label="Kategori" name="kategori" stacked>
    <option value="">Pilih kategori</option>
    @foreach (config('wattkost.kategori_alat') as $kategori)
        <option value="{{ $kategori }}" @selected(old('kategori', $alat?->kategori) === $kategori)>{{ $kategori }}</option>
    @endforeach
</x-select>
<div class="grid grid-cols-2 gap-4">
    <x-input label="Daya (W)" name="daya_watt" type="number" :value="$alat?->daya_watt" placeholder="300" suffix="W"
        min="1" max="10000" />
    <x-input label="Jumlah" name="jumlah" type="number" :value="$alat?->jumlah ?? 1" min="1" max="50" />
</div>
