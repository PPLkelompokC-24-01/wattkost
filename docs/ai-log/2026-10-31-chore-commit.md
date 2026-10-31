# Log Prompt AI — chore-commit — 31 Oct 2026

Tools: Claude Code

## 09:27 — Puti Azzahra Navita

```text
<pasted_content id="63d5">
Tim memutuskan: Claude Code TIDAK BOLEH menjalankan git add, commit,
push, merge, rebase, reset, revert, cherry-pick, tag, atau membuat/
merge PR. Semua itu dilakukan anggota sendiri (P1–P4). Claude cukup
menuliskan perintah yang harus diketik anggota.

Ubah:
1. .claude/settings.json: pindahkan/tambahkan ke "deny":
   Bash(git add:*), Bash(git commit:*), Bash(git push:*),
   Bash(git merge:*), Bash(git rebase:*), Bash(git reset:*),
   Bash(git revert:*), Bash(git cherry-pick:*), Bash(git tag:*),
   Bash(gh pr create:*), Bash(gh pr merge:*).
   Hapus aturan yang sama dari "ask"/"allow" kalau ada, supaya tidak
   bentrok. git status, git diff, git log, git fetch, dan
   git merge-tree tetap boleh (dipakai /cek-sebelum-pr).
2. CLAUDE.md bagian "Aturan keras": tambah aturan baru bahwa Claude
   tidak pernah commit/push/merge, melainkan menuliskan perintahnya
   untuk diketik anggota satu per satu.
3. .claude/commands/cek-sebelum-pr.md, selesaikan-konflik.md, dan
   mulai-pbi.md: di bagian akhir, jangan menawarkan "saya yang
   commit". Ganti dengan daftar perintah git yang harus diketik
   anggota.
4. docs/GIT-WORKFLOW.md: tambah satu baris aturan yang sama.
Jangan ubah file lain. Tunjukkan rencana dulu, lalu jalankan
php artisan test.
</pasted_content id="63d5">
```

## 09:31 — Puti Azzahra Navita

```text
/cek-sebelum-pr
```

