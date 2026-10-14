## PBI
PBI-XX — <nama> (bagian ...)  · Pasangan: P? (<nama driver>, <nama navigator>)

## Yang dikerjakan
-

## Cara mengetes manual
1. Login sebagai `penghuni1@wattkost.test`
2. Buka ...

## Screenshot (HP & laptop)

## Checklist Definition of Done
- [ ] Sesuai `docs/pbi/PBI-XX-*.md` dan UC di proposal (standard + alternative process)
- [ ] Hanya mengubah file milik modul ini (file bersama: sudah izin pemiliknya → sebutkan)
- [ ] Tidak mengedit migration lama; migration baru → `docs/DATABASE.md` diperbarui
- [ ] Feature test 5 skenario wajib + unit test service; `php artisan test` hijau
- [ ] `pint --test` lolos, `npm run build` sukses
- [ ] Sudah `git merge origin/develop` terbaru, tidak ada konflik
- [ ] Log prompt `docs/ai-log/` ikut di-commit
- [ ] Perbedaan dari proposal dicatat di `docs/PERUBAHAN-RANCANGAN.md` (kalau ada)
- [ ] Tidak ada `dd()`, `dump()`, `console.log`, `.env`, atau rahasia
