import './bootstrap';

// Sidebar di HP: tombol hamburger di header membuka panel geser, latar/Escape menutupnya.
const sidebar = document.querySelector('[data-sidebar]');
const latar = document.querySelector('[data-sidebar-latar]');
const tombolSidebar = document.querySelector('[data-buka-sidebar]');

function aturSidebar(terbuka) {
    if (!sidebar) return;
    sidebar.toggleAttribute('data-terbuka', terbuka);
    latar?.toggleAttribute('data-terbuka', terbuka);
    tombolSidebar?.setAttribute('aria-expanded', String(terbuka));
}

tombolSidebar?.addEventListener('click', () => aturSidebar(!sidebar.hasAttribute('data-terbuka')));
latar?.addEventListener('click', () => aturSidebar(false));
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') aturSidebar(false);
});

// Modal (<dialog>): tombol dengan data-buka-modal="id" membuka dialog; klik di luar kotak menutupnya.
document.querySelectorAll('[data-buka-modal]').forEach((tombol) => {
    tombol.addEventListener('click', () => document.getElementById(tombol.dataset.bukaModal)?.showModal());
});

// Grafik (<x-chart>): Chart.js (resources/js/grafik.js) hanya dimuat kalau halaman punya [data-chart].
const kanvasGrafik = document.querySelectorAll('[data-chart]');
if (kanvasGrafik.length) {
    import('./grafik').then(({ gambarSemuaGrafik }) => gambarSemuaGrafik(kanvasGrafik));
}

// Filter otomatis: <select data-kirim-otomatis> mengirim form-nya saat pilihan berubah (<x-pilih-bulan>).
document.querySelectorAll('[data-kirim-otomatis]').forEach((el) => {
    el.addEventListener('change', () => el.form?.requestSubmit());
});

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });
    if (dialog.hasAttribute('data-buka-otomatis')) dialog.showModal();
});
