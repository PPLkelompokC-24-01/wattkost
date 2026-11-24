<?php

return [
    'kategori_alat' => ['Pendingin', 'Dapur', 'Elektronik', 'Penerangan', 'Rumah Tangga', 'Lainnya'],
    'golongan_tarif' => [ // tarif PLN berlaku Okt 2026 (sumber: PLN/ESDM, cek ulang tiap triwulan)
        'R-1/TR 450 VA (subsidi)' => ['daya_va' => 450,  'tarif' => 415.00],
        'R-1/TR 900 VA (subsidi)' => ['daya_va' => 900,  'tarif' => 605.00],
        'R-1/TR 900 VA-RTM' => ['daya_va' => 900,  'tarif' => 1352.00],
        'R-1/TR 1.300 VA' => ['daya_va' => 1300, 'tarif' => 1444.70],
        'R-1/TR 2.200 VA' => ['daya_va' => 2200, 'tarif' => 1444.70],
        'R-2/TR 3.500–5.500 VA' => ['daya_va' => 3500, 'tarif' => 1699.53],
    ],
    'default_kwh_harian' => 3.0,   // fallback prediksi token (PBI 8 A2)
    'min_hari_data_prediksi' => 3, // minimal hari data agar rata-rata dianggap valid
    // kg CO2 per kWh, sistem Jawa-Madura-Bali. Sumber: Kepmen ESDM No. 163.K/HK.02/MEM.S/2021
    // (Faktor Emisi GRK Sistem Ketenagalistrikan, data 2019). Sementara; angka lampiran dicek P2 (PBI 24).
    'faktor_emisi_default' => 0.87,

    // Deteksi lonjakan (PBI 22): rasio = kWh tanggal ÷ rata-rata kWh/hari 7 hari sebelumnya.
    'lonjakan_ambang' => 1.5,   // rasio > 1,5 → lonjakan (tingkat "sedang")
    'lonjakan_tinggi' => 2.0,   // rasio ≥ 2,0 → tingkat "tinggi"
    'lonjakan_min_hari' => 7,   // minimal hari berdata sebelum deteksi dijalankan

    // Pengingat token (PBI 09): nilai awal pengingat_batas_hari (kirim kalau sisa hari ≤ angka ini).
    'pengingat_batas_hari_default' => 3,
];
