{{-- PBI 20 — Tantangan Hemat (penghuni). Tata letak mengikuti fungsi Tantangan() di docs/desain/figma-make/pages.txt. --}}
<x-layouts.app title="Tantangan Hemat">
    @php
        $adaLeaderboard = Route::has('leaderboard.index'); // PBI 21; tautan muncul setelah route-nya ada
    @endphp

    <x-page-header>
        {{-- ?tab=tersedia tetap menyorot tab Tersedia --}}
        <x-tabs :items="[
            'Tersedia' => request('tab') === 'tersedia' ? route('tantangan.index', ['tab' => 'tersedia']) : route('tantangan.index'),
            'Diikuti' => route('tantangan.index', ['tab' => 'diikuti']),
            'Selesai' => route('tantangan.index', ['tab' => 'selesai']),
        ]" />
        @if ($adaLeaderboard)
            <x-button variant="secondary" :href="route('leaderboard.index')">Leaderboard →</x-button>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($tab === 'tersedia')
                @if ($tersedia->isEmpty())
                    <x-card>
                        <x-empty-state judul="Belum ada tantangan" pesan="Belum ada tantangan aktif. Cek lagi nanti, ya."
                            icon="trophy" />
                    </x-card>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($tersedia as $tantangan)
                            @include('tantangan.partials.kartu', [
                                'mode' => 'tersedia',
                                'berjalan' => in_array($tantangan->id, $idBerjalan, true),
                            ])
                        @endforeach
                    </div>
                @endif
            @elseif ($tab === 'diikuti')
                @if ($diikuti->isEmpty())
                    <x-card>
                        <x-empty-state judul="Belum ada tantangan yang diikuti"
                            pesan="Pilih tantangan di tab Tersedia, lalu klik Ikuti." :href="route('tantangan.index')"
                            aksi="Lihat tantangan" icon="trophy" />
                    </x-card>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($diikuti as $peserta)
                            @include('tantangan.partials.kartu', [
                                'tantangan' => $peserta->tantangan,
                                'mode' => 'diikuti',
                                'progres' => $progres[$peserta->id],
                            ])
                        @endforeach
                    </div>
                @endif
            @else
                @if ($selesai->isEmpty())
                    <x-card>
                        <x-empty-state judul="Belum ada tantangan selesai"
                            pesan="Hasil dinilai otomatis setelah durasi tantangan berakhir." icon="trophy" />
                    </x-card>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($selesai as $peserta)
                            @include('tantangan.partials.kartu', ['tantangan' => $peserta->tantangan, 'mode' => 'selesai'])
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="flex flex-col gap-6">
            {{-- Keputusan PO no. 3: catatan "Peringkat X dari Y" diisi di PBI 21 --}}
            <x-stat-card label="Total Poin Saya" :value="angka($totalPoin, 0).' poin'" icon="trophy"
                :href="$adaLeaderboard ? route('leaderboard.index') : null" />

            <x-card title="Sedang Diikuti">
                @forelse ($diikuti as $peserta)
                    <div @class(['pt-4 mt-4 border-t border-line' => ! $loop->first])>
                        <p class="text-sm">{{ $peserta->tantangan->judul }}</p>
                        <div class="my-2">
                            <x-progress :value="$progres[$peserta->id]['hariKe']" :max="$progres[$peserta->id]['durasi']" />
                        </div>
                        <p class="text-xs text-mute">{{ $progres[$peserta->id]['keterangan'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-mute">Belum ada tantangan yang sedang diikuti.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-layouts.app>
