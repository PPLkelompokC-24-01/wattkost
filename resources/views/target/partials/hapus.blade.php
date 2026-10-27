{{-- Tombol hapus target dengan konfirmasi. $target = TargetHemat. --}}
<form method="POST" action="{{ route('target.destroy', $target) }}"
    onsubmit="return confirm(@js('Yakin hapus target '.\Illuminate\Support\Carbon::create($target->tahun, $target->bulan, 1)->translatedFormat('F Y').'?'))">
    @csrf
    @method('DELETE')
    <x-button small variant="danger" type="submit">Hapus</x-button>
</form>
