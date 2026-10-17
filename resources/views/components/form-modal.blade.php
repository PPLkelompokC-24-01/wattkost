{{--
    Modal berisi form untuk pola CRUD (lihat "Pola halaman" di docs/KONVENSI.md).
    Menangani @csrf, method spoofing (PUT/DELETE), input hidden _form, dan membuka ulang
    modal ini otomatis kalau form inilah yang barusan gagal validasi (old('_form') === $form).
    <x-form-modal id="modal-tambah" title="Tambah Alat" :action="route('alat.store')">
        @include('alat.partials.form', ['alat' => null])
    </x-form-modal>
    <x-form-modal id="modal-ubah" title="Ubah Alat" form="ubah" method="PUT" buka
        :action="route('alat.update', $ubah)">...</x-form-modal>
--}}
@props(['id', 'title', 'action', 'method' => 'POST', 'form' => 'tambah', 'buka' => false, 'simpan' => 'Simpan'])

<x-modal :id="$id" :title="$title" :buka="$buka || old('_form') === $form" {{ $attributes }}>
    <form id="{{ $id }}-form" method="POST" action="{{ $action }}" class="flex flex-col gap-4">
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method(strtoupper($method))
        @endif
        <input type="hidden" name="_form" value="{{ $form }}">
        {{ $slot }}
    </form>
    <x-slot:footer>
        <x-button type="submit" form="{{ $id }}-form">{{ $simpan }}</x-button>
    </x-slot:footer>
</x-modal>
