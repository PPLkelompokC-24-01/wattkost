{{-- Isian form target; dipakai modal tambah ($target = null) & modal ubah ($target = data yang diubah). --}}
<x-input label="Bulan" name="bulan" type="month"
    :value="$target ? sprintf('%04d-%02d', $target->tahun, $target->bulan) : now()->format('Y-m')" />
<x-select label="Jenis target" name="jenis" stacked>
    <option value="kwh" @selected(old('jenis', $target?->jenis) === 'kwh')>kWh (batas pemakaian listrik)</option>
    <option value="rupiah" @selected(old('jenis', $target?->jenis) === 'rupiah')>Rupiah (batas biaya, pakai tarif Profil Kost)</option>
</x-select>
<x-input label="Nilai target" name="nilai" type="number" step="0.01" min="0" :value="$target?->nilai" placeholder="Contoh: 70" />
