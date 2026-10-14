<?php
/**
 * Hook UserPromptSubmit: menyimpan setiap prompt ke docs/ai-log/
 * sebagai bukti penggunaan AI (ketentuan 6.1 proposal: prompt wajib dilampirkan).
 * File: docs/ai-log/<YYYY-MM-DD>-<branch>.md
 * Tidak pernah memblokir prompt: selalu exit 0.
 */
$input = json_decode(stream_get_contents(STDIN), true) ?: [];
$prompt = trim($input['prompt'] ?? '');
if ($prompt === '') {
    exit(0);
}

$root = $input['cwd'] ?? getcwd();
$dir = $root.'/docs/ai-log';
if (! is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

$branch = trim((string) @shell_exec('git -C '.escapeshellarg($root).' branch --show-current 2>'.(PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null')));
$branch = $branch !== '' ? preg_replace('/[^A-Za-z0-9._-]+/', '-', $branch) : 'tanpa-branch';
$user = trim((string) @shell_exec('git -C '.escapeshellarg($root).' config user.name')) ?: 'anon';

date_default_timezone_set('Asia/Jakarta');
$file = sprintf('%s/%s-%s.md', $dir, date('Y-m-d'), $branch);
if (! file_exists($file)) {
    file_put_contents($file, "# Log Prompt AI — {$branch} — ".date('d M Y')."\n\nTools: Claude Code\n\n");
}

// Jangan simpan sesuatu yang tampak seperti rahasia
$prompt = preg_replace('/(password|secret|token|key)\s*[:=]\s*\S+/i', '$1=[DISENSOR]', $prompt);

$entry = sprintf("## %s — %s\n\n```text\n%s\n```\n\n", date('H:i'), $user, $prompt);
file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
exit(0);
