<?php

namespace App\Observers;

/**
 * Didaftarkan lewat #[ObservedBy] di model PemakaianHarian (PR M2).
 * Kerangka saja — isinya ditulis Beden (PBI 09 & 22): method `saved(PemakaianHarian $pemakaian)` yang memanggil
 * PengingatTokenService::periksa() dan DeteksiLonjakanService::periksa(). Lihat docs/ARSITEKTUR.md.
 */
class PemakaianHarianObserver
{
    //
}
