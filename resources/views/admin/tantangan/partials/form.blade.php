{{--
    Isian form tantangan; dipakai modal tambah ($tantangan = null) & modal ubah ($tantangan = data yang diubah).
    $labelJenis dari admin/tantangan/index.blade.php.
--}}
<x-input label="Judul" name="judul" :value="$tantangan?->judul" placeholder="Contoh: 7 Hari Tanpa Boros" />
<x-textarea label="Deskripsi" name="deskripsi" :value="$tantangan?->deskripsi" placeholder="Opsional, tampil di kartu tantangan" />
<x-select label="Jenis Target" name="jenis_target" stacked>
    <option value="">Pilih jenis target</option>
    @foreach ($labelJenis as $jenis => $label)
        <option value="{{ $jenis }}" @selected(old('jenis_target', $tantangan?->jenis_target) === $jenis)>{{ $label }}</option>
    @endforeach
</x-select>
<x-input label="Nilai Target" name="nilai_target" type="number" step="0.01" min="0.01" max="100" suffix="kWh/hari atau %"
    :value="$tantangan?->nilai_target" placeholder="6" />
<div class="grid grid-cols-2 gap-4">
    <x-input label="Durasi" name="durasi_hari" type="number" min="1" max="60" suffix="hari"
        :value="$tantangan?->durasi_hari" placeholder="7" />
    <x-input label="Poin" name="poin" type="number" min="1" max="1000" suffix="poin"
        :value="$tantangan?->poin" placeholder="100" />
</div>
<div class="flex items-center justify-between gap-4">
    <span class="text-sm font-semibold">Aktif (tampil di tab Tersedia)</span>
    {{-- Toggle tidak dicentang tidak mengirim apa pun → hidden "0" dulu; kalau dicentang, "1" menimpanya. --}}
    <input type="hidden" name="aktif" value="0">
    <x-toggle name="aktif" :checked="$tantangan?->aktif ?? true" label="Aktif" />
</div>
