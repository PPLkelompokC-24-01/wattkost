{{-- Isian form pembelian token; dipakai modal catat ($pembelian = null) & modal ubah ($pembelian = data yang diubah). --}}
<x-input label="Tanggal Beli" name="tanggal_beli" type="date"
    :value="$pembelian?->tanggal_beli->toDateString() ?? today()->toDateString()" :max="today()->toDateString()" />
<div class="grid grid-cols-2 gap-4">
    <x-input label="Nominal (Rp)" name="nominal" type="number" :value="$pembelian?->nominal" placeholder="100000"
        suffix="Rp" min="1000" max="10000000" />
    <x-input label="kWh Diperoleh" name="jumlah_kwh" type="number" :value="$pembelian?->jumlah_kwh" placeholder="65,93"
        suffix="kWh" step="0.01" min="0.01" max="10000" />
</div>
<p class="text-xs text-mute -mt-2">kWh di struk sudah dipotong pajak (PPJ) & biaya admin, jadi tidak sama dengan nominal ÷ tarif.</p>
<x-input label="Nomor Meter (opsional)" name="nomor_meter" inputmode="numeric" maxlength="20" placeholder="14234567890"
    :value="$pembelian ? $pembelian->nomor_meter : $nomorMeterTerakhir" />
<x-input label="Keterangan (opsional)" name="keterangan" :value="$pembelian?->keterangan" maxlength="255"
    placeholder="Contoh: Beli lewat m-banking" />
