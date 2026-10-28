<?php

namespace App\Http\Requests\Token;

/** Validasi ubah pembacaan meteran: aturannya sama dengan catat, tapi waktu kosong tidak diganti. */
class UpdateSisaKwhRequest extends StoreSisaKwhRequest
{
    /** @return array<string, mixed> */
    protected function waktuDefault(): array
    {
        return [];
    }
}
