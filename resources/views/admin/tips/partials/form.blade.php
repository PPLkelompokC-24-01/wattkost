{{-- Isian form tips hemat; dipakai modal tambah ($tip = null) & modal ubah ($tip = data yang diubah). --}}
<x-input label="Judul" name="judul" :value="$tip?->judul" placeholder="Contoh: Atur suhu AC ke 25°C" />
<x-select label="Kategori" name="kategori" stacked>
    <option value="">Pilih kategori</option>
    @foreach ([...config('wattkost.kategori_alat'), 'Umum'] as $kategori)
        <option value="{{ $kategori }}" @selected(old('kategori', $tip?->kategori) === $kategori)>{{ $kategori }}</option>
    @endforeach
</x-select>
<x-textarea label="Isi Tips" name="isi" :value="$tip?->isi" placeholder="Tuliskan isi tips hemat..." />
