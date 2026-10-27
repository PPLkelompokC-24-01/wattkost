<x-layouts.app title="Pemakaian Harian">
    <x-page-header sub="Catat jam pemakaian setiap alat per hari">
        {{-- Ganti tanggal → halaman dimuat ulang dengan jam yang sudah tercatat di tanggal itu (UC-02 A1) --}}
        <form method="GET" action="{{ route('pemakaian.index') }}" class="flex items-center gap-2">
            <x-input name="tanggal" type="date" :value="$tanggal->toDateString()" :max="today()->toDateString()"
                aria-label="Pilih tanggal" data-kirim-otomatis />
            <noscript><x-button type="submit" variant="secondary" small>Tampilkan</x-button></noscript>
        </form>
        @if ($daftarAlat->isNotEmpty())
            <x-button type="submit" form="form-pemakaian">Simpan</x-button>
        @endif
    </x-page-header>

    {{-- Error yang tidak menempel ke satu baris alat (tanggal, alat bukan milik user) --}}
    @foreach (['tanggal', 'jam'] as $kolom)
        @error($kolom)
            <x-alert tone="gagal" class="mb-6">{{ $message }}</x-alert>
        @enderror
    @endforeach

    @if ($daftarAlat->isEmpty())
        {{-- UC-02 A2 --}}
        <x-card>
            <x-empty-state judul="Belum ada alat listrik" pesan="Tambahkan alat dulu sebelum mencatat jam pemakaian."
                :href="route('alat.index')" aksi="Ke Alat Listrik" icon="plug" />
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-card :title="'Input Jam Pemakaian · '.$tanggal->translatedFormat('d M Y')" class="lg:col-span-2 min-w-0">
                @if ($jamTercatat->isNotEmpty())
                    <x-slot:action>
                        @include('pemakaian.partials.tombol-hapus', ['tanggal' => $tanggal])
                    </x-slot:action>
                @endif
                {{-- Tombol Simpan ada di page header (form="form-pemakaian"), sesuai desain --}}
                <form id="form-pemakaian" method="POST" action="{{ route('pemakaian.store') }}">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                    <x-table :head="['Alat', 'Daya', 'Jam Pakai/Hari']" :align="['l', 'r', 'l']">
                        @foreach ($daftarAlat as $alat)
                            @php
                                $error = $errors->first("jam.{$alat->id}");
                                $jamLama = isset($jamTercatat[$alat->id]) ? (float) $jamTercatat[$alat->id] : '';
                            @endphp
                            <tr>
                                <td class="font-medium whitespace-nowrap">{{ $alat->nama_alat }}</td>
                                <td class="text-right whitespace-nowrap">
                                    {{ angka($alat->daya_watt, 0) }} W{{ $alat->jumlah > 1 ? ' × '.$alat->jumlah : '' }}
                                </td>
                                <td class="py-2">
                                    <div @class([
                                        'w-32 h-9 rounded-lg border px-3 flex items-center justify-between gap-2 focus-within:ring-4',
                                        'border-line focus-within:border-aqua focus-within:ring-aqua-soft' => ! $error,
                                        'border-coral focus-within:ring-coral-soft' => $error,
                                    ])>
                                        <input type="number" name="jam[{{ $alat->id }}]" value="{{ old("jam.{$alat->id}", $jamLama) }}"
                                            min="0" max="24" step="0.25" inputmode="decimal" placeholder="0" data-jam
                                            aria-label="Jam pakai {{ $alat->nama_alat }}"
                                            class="w-full min-w-0 bg-transparent text-white placeholder:text-mute focus:outline-none">
                                        <span class="text-xs text-mute">jam</span>
                                    </div>
                                    @if ($error)
                                        <span class="mt-1 block text-xs text-coral font-sans">{{ $error }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </form>

                <div class="mt-4 flex justify-between items-center gap-4 rounded-lg bg-wash p-4">
                    <span class="text-sm font-medium">Total Jam Pemakaian</span>
                    <span class="text-lg font-bold tabular-nums" data-total-jam>{{ angka($jamTercatat->sum()) }} jam</span>
                </div>
            </x-card>

            {{-- Hari ini − 6 s.d. hari ini, hanya tanggal yang punya catatan. Kolom kWh menyusul setelah KonsumsiService merge. --}}
            <x-card title="Riwayat 7 Hari" class="min-w-0">
                @forelse ($riwayat as $hari => $totalJam)
                    @php($tanggalRiwayat = \Illuminate\Support\Carbon::parse($hari))
                    <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm">
                        <div class="min-w-0">
                            <p @class(['font-medium', 'text-aqua' => $tanggalRiwayat->isSameDay($tanggal)])>
                                {{ $tanggalRiwayat->translatedFormat('d M') }}
                            </p>
                            <p class="text-mute tabular-nums">{{ angka($totalJam) }} jam</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-button small variant="secondary" :href="route('pemakaian.index', ['tanggal' => $hari])">Ubah</x-button>
                            @include('pemakaian.partials.tombol-hapus', ['tanggal' => $tanggalRiwayat])
                        </div>
                    </div>
                @empty
                    <x-empty-state judul="Belum ada riwayat" pesan="Belum ada catatan pemakaian dalam 7 hari terakhir."
                        icon="calendar" class="py-6" />
                @endforelse
            </x-card>
        </div>

        {{-- UC-02 langkah 4: total jam dihitung langsung saat isian berubah. Inline karena layout tidak punya @stack('scripts'). --}}
        <script>
            (() => {
                const total = document.querySelector('[data-total-jam]');
                const isian = document.querySelectorAll('[data-jam]');
                const hitung = () => {
                    const jam = [...isian].reduce((jumlah, el) => jumlah + (parseFloat(el.value) || 0), 0);
                    total.textContent = jam.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' jam';
                };

                isian.forEach((el) => el.addEventListener('input', hitung));
                hitung(); // juga saat kembali dari error validasi (isian lama dari old())
            })();
        </script>
    @endif
</x-layouts.app>
