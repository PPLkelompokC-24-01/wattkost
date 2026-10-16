{{--
    Modal memakai <dialog> asli. Dibuka oleh tombol mana pun dengan data-buka-modal="<id>"
    (lihat resources/js/app.js). Tombol Batal & × menutup tanpa JS (form method="dialog").
    <x-button data-buka-modal="modal-tambah-alat">+ Tambah Alat</x-button>
    <x-modal id="modal-tambah-alat" title="Tambah Alat">
        <form id="form-alat" method="POST" action="{{ route('alat.store') }}">@csrf ...</form>
        <x-slot:footer><x-button type="submit" form="form-alat">Simpan</x-button></x-slot:footer>
    </x-modal>
    buka → modal langsung terbuka saat halaman dimuat (mis. ada error validasi).
--}}
@props(['id', 'title', 'footer' => null, 'buka' => false])

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-judul" @if ($buka) data-buka-otomatis @endif
    {{ $attributes->class('m-auto w-[calc(100%-2rem)] max-w-[480px] p-0 text-white bg-raised rounded-[28px] border border-line-strong shadow-[0_24px_48px_rgba(0,0,0,.35)] backdrop:bg-abyss/75 backdrop:backdrop-blur-md') }}>
    <div class="h-16 px-6 flex items-center justify-between border-b border-line">
        <h2 id="{{ $id }}-judul" class="font-display text-xl font-bold">{{ $title }}</h2>
        <form method="dialog">
            <button aria-label="Tutup" class="size-9 rounded-full bg-white/6 text-mute text-xl">×</button>
        </form>
    </div>
    <div class="p-6 flex flex-col gap-4">{{ $slot }}</div>
    <div class="px-6 py-4 border-t border-line flex justify-end gap-3">
        <form method="dialog">
            <x-button type="submit" variant="secondary">Batal</x-button>
        </form>
        {{ $footer }}
    </div>
</dialog>
