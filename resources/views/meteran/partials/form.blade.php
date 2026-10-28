{{-- Isian form pembacaan meteran; dipakai modal catat ($sisaKwh = null) & modal ubah ($sisaKwh = data yang diubah). --}}
<x-input label="Sisa kWh di Meteran" name="sisa_kwh" type="number" :value="$sisaKwh?->sisa_kwh" placeholder="42,50"
    suffix="kWh" step="0.01" min="0" max="10000" />
<x-input label="Waktu Pembacaan" name="dicatat_pada" type="datetime-local"
    :value="($sisaKwh?->dicatat_pada ?? now())->format('Y-m-d\TH:i')" :max="now()->format('Y-m-d\TH:i')" />
