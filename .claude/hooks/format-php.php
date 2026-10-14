<?php
/**
 * Hook PostToolUse: setelah Claude mengedit file .php, rapikan dengan Laravel Pint.
 * Supaya semua pasangan punya format kode identik (mengurangi konflik merge karena spasi/format).
 * Diam-diam dilewati kalau Pint belum terpasang. Tidak pernah gagal.
 */
$input = json_decode(stream_get_contents(STDIN), true) ?: [];
$file = $input['tool_input']['file_path'] ?? '';
if (! str_ends_with($file, '.php') || str_ends_with($file, '.blade.php')) {
    exit(0);
}
$root = $input['cwd'] ?? getcwd();
$pint = $root.'/vendor/bin/pint';
if (! file_exists($pint) || ! file_exists($file)) {
    exit(0);
}
@shell_exec('php '.escapeshellarg($pint).' '.escapeshellarg($file).' 2>&1');
exit(0);
