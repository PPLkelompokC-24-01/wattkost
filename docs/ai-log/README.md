# Log Prompt AI

Bukti penggunaan AI sesuai ketentuan 6.1 proposal ("prompt wajib dilampirkan").

- Prompt **Claude Code** tercatat otomatis ke `YYYY-MM-DD-<branch>.md` oleh hook `.claude/hooks/catat-prompt.php`. Commit file ini bersama kode di PR.
- Prompt dari **tool lain** (ChatGPT, Gemini, Claude chat) untuk planning/testing/review/retro: salin manual ke `manual/YYYY-MM-DD-<proses>-<tool>.md` (atau tempel tautan share percakapan).
- Jangan menulis password, token, atau data pribadi di prompt — repo ini publik. Hook menyensor pola `password=...` sederhana, tapi tetap hati-hati.
