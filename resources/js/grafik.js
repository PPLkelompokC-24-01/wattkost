// Gaya grafik WattKost (LineChart & BarChart di docs/desain/figma-make/ui.txt) untuk <x-chart>.
// Dimuat oleh app.js hanya kalau halaman punya [data-chart], supaya Chart.js tidak membebani halaman lain.
import Chart from 'chart.js/auto';

const AQUA = '#25C6AC';
const TEKS = '#A8BDC0';
const GRID = 'rgba(255, 255, 255, 0.08)';

Chart.defaults.font.family = "'Outfit', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = TEKS;

const formatAngka = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

// Area di bawah garis: aqua 24% di atas → transparan di bawah.
function gradasiArea({ chart }) {
    const { ctx, chartArea } = chart;
    if (!chartArea) return 'transparent';
    const gradasi = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    gradasi.addColorStop(0, 'rgba(37, 198, 172, 0.24)');
    gradasi.addColorStop(1, 'rgba(37, 198, 172, 0)');
    return gradasi;
}

// Dataset pertama = garis aqua + area; dataset berikutnya = garis putih putus-putus (mis. proyeksi).
function gayaGaris(dataset, i) {
    const utama = i === 0;
    const terakhir = dataset.data.length - 1;

    return {
        ...dataset,
        borderColor: utama ? AQUA : 'rgba(255, 255, 255, 0.6)',
        borderWidth: 3,
        borderDash: utama ? [] : [9, 8],
        borderCapStyle: 'round',
        borderJoinStyle: 'round',
        backgroundColor: utama ? gradasiArea : 'transparent',
        fill: utama,
        tension: 0.35,
        pointRadius: (c) => (utama && c.dataIndex === terakhir ? 6 : 0), // titik hanya di data terakhir
        pointHoverRadius: 6,
        pointBackgroundColor: '#FFFFFF',
        pointBorderColor: AQUA,
        pointBorderWidth: 3,
    };
}

// 1 dataset: batang terakhir aqua, lainnya putih transparan. >1 dataset (perbandingan): dataset terakhir aqua.
function gayaBatang(dataset, i, jumlahDataset) {
    const terakhir = dataset.data.length - 1;
    const warna = jumlahDataset === 1
        ? dataset.data.map((_, j) => (j === terakhir ? AQUA : 'rgba(255, 255, 255, 0.14)'))
        : i === jumlahDataset - 1 ? AQUA : 'rgba(255, 255, 255, 0.24)';

    return {
        ...dataset,
        backgroundColor: warna,
        hoverBackgroundColor: warna,
        borderRadius: 8,
        borderSkipped: 'start',
        maxBarThickness: jumlahDataset === 1 ? 64 : 28,
    };
}

export function gambarGrafik(kanvas) {
    const { type, labels, datasets, satuan } = JSON.parse(kanvas.dataset.chart);
    const banyak = datasets.length > 1;
    // satuan "Rp" ditulis di depan tanpa spasi (sama dengan helper rupiah()); satuan lain di belakang.
    const formatNilai = (v) => (satuan === 'Rp' ? `Rp${formatAngka.format(v)}` : formatAngka.format(v) + (satuan ? ` ${satuan}` : ''));

    return new Chart(kanvas, {
        type,
        data: {
            labels,
            datasets: datasets.map((d, i) => (type === 'bar' ? gayaBatang(d, i, datasets.length) : gayaGaris(d, i))),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // tinggi diatur pembungkus (prop tinggi)
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: banyak,
                    align: 'end',
                    labels: { color: TEKS, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8 },
                },
                tooltip: {
                    backgroundColor: '#0C3038',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    titleColor: TEKS,
                    bodyColor: '#FFFFFF',
                    bodyFont: { weight: '600' },
                    cornerRadius: 12,
                    padding: 10,
                    displayColors: banyak,
                    callbacks: {
                        label: (c) => (banyak ? `${c.dataset.label}: ` : '') + formatNilai(c.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: TEKS, maxRotation: 0, autoSkipPadding: 16 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: GRID },
                    border: { display: false, dash: [5, 7] }, // garis grid putus-putus
                    ticks: { color: TEKS, maxTicksLimit: 6, callback: (v) => formatAngka.format(v) },
                },
            },
        },
    });
}

export function gambarSemuaGrafik(daftarKanvas) {
    // Tunggu font Outfit siap supaya label sumbu tidak tergambar dengan font cadangan.
    document.fonts.ready.then(() => daftarKanvas.forEach(gambarGrafik));
}
