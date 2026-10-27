{{--
    Tabel. Baris diisi lewat slot <tr><td>; gaya sel diterapkan otomatis dari sini.
    :align mengatur rata kolom header ('l'/'r'); untuk sel isi tambahkan class="text-right" sendiri.
    Baris yang disorot: <tr data-sorot>.
    <x-table :head="['Alat', 'kWh']" :align="['l', 'r']">
        @foreach ($alat as $a)
            <tr><td class="font-medium">{{ $a->nama }}</td><td class="text-right">{{ kwh($a->kwh) }}</td></tr>
        @endforeach
    </x-table>
--}}
@props(['head' => [], 'align' => []])

<div {{ $attributes->class('border border-line rounded-2xl overflow-hidden') }}>
    <div class="overflow-x-auto">
        <table
            class="w-full text-sm [&_tbody_tr]:border-t [&_tbody_tr]:border-line [&_tbody_tr]:bg-surface [&_tbody_tr]:transition-colors [&_tbody_tr:hover]:bg-aqua/6 [&_tbody_tr[data-sorot]]:bg-aqua-soft [&_tbody_tr[data-sorot]]:outline [&_tbody_tr[data-sorot]]:outline-1 [&_tbody_tr[data-sorot]]:-outline-offset-1 [&_tbody_tr[data-sorot]]:outline-aqua/50 [&_td]:h-13 [&_td]:px-4 [&_td:not(:first-child)]:font-mono [&_td:not(:first-child)]:text-[13px]">
            <thead class="bg-white/4">
                <tr>
                    @foreach ($head as $i => $judul)
                        <th @class([
                            'h-11 px-4 font-bold text-mute text-[11px] uppercase tracking-[0.08em] whitespace-nowrap',
                            'text-right' => ($align[$i] ?? 'l') === 'r',
                            'text-left' => ($align[$i] ?? 'l') !== 'r',
                        ])>{{ $judul }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
