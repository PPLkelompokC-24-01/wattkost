<?php

namespace App\Http\Requests\TargetHemat;

use App\Models\TargetHemat;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Validasi tambah target hemat (PBI 11). Field `bulan` (Y-m) dipecah jadi tahun & bulan lewat dataTarget(). */
class StoreTargetHematRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bulan' => ['bail', 'required', 'date_format:Y-m', function (string $atribut, string $nilai, Closure $gagal) {
                // Dari Okt 2026: Okt 2027 masih boleh, Nov 2027 ditolak. Bulan yang sudah lewat boleh.
                if (Carbon::createFromFormat('!Y-m', $nilai)->gt(now()->startOfMonth()->addMonths(12))) {
                    $gagal('Bulan target paling jauh 12 bulan dari sekarang.');
                }
            }],
            'jenis' => ['required', Rule::in([TargetHemat::JENIS_KWH, TargetHemat::JENIS_RUPIAH])],
            'nilai' => ['required', 'numeric', 'gt:0', 'max:100000000'],
        ];
    }

    /** Satu target per bulan per user (unique user_id, tahun, bulan); target yang sedang diubah diabaikan. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('bulan')) {
                return;
            }

            ['tahun' => $tahun, 'bulan' => $bulan] = $this->dataTarget();
            $sudahAda = $this->user()->targetHemat()
                ->where('tahun', $tahun)->where('bulan', $bulan)
                ->when($this->targetDiabaikan(), fn ($query, TargetHemat $target) => $query->whereKeyNot($target->id))
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add('bulan', 'Target untuk bulan ini sudah ada, silakan ubah target yang lama.');
            }
        }];
    }

    /** @return array{tahun: int, bulan: int, jenis: string, nilai: mixed} */
    public function dataTarget(): array
    {
        $periode = Carbon::createFromFormat('!Y-m', $this->input('bulan'));

        return [
            'tahun' => $periode->year,
            'bulan' => $periode->month,
            'jenis' => $this->input('jenis'),
            'nilai' => $this->input('nilai'),
        ];
    }

    protected function targetDiabaikan(): ?TargetHemat
    {
        return null;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'bulan' => 'bulan',
            'jenis' => 'jenis target',
            'nilai' => 'nilai target',
        ];
    }
}
