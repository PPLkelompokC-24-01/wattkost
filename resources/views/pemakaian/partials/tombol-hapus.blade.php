{{-- Hapus semua catatan satu tanggal (dipakai di kartu input & riwayat). $tanggal = Carbon --}}
<form method="POST" action="{{ route('pemakaian.destroy', $tanggal->toDateString()) }}"
    onsubmit="return confirm(@js('Yakin hapus semua catatan pemakaian tanggal '.$tanggal->translatedFormat('d M Y').'?'))">
    @csrf
    @method('DELETE')
    <x-button small variant="danger" type="submit">Hapus</x-button>
</form>
