<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Angka bersama Sprint 2 di config/wattkost.php (PR C2), dipakai PBI 09 & 22.
 */
class KonfigurasiWattkostTest extends TestCase
{
    public function test_config_lonjakan_terbaca(): void
    {
        $this->assertSame(1.5, config('wattkost.lonjakan_ambang'));
        $this->assertSame(2.0, config('wattkost.lonjakan_tinggi'));
        $this->assertSame(7, config('wattkost.lonjakan_min_hari'));
    }

    public function test_config_pengingat_terbaca(): void
    {
        $this->assertSame(3, config('wattkost.pengingat_batas_hari_default'));
    }
}
