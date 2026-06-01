<?php

namespace App\Http\Requests;

use App\Support\TargetPeriod;
use App\Support\TargetRealisasiFieldCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertTargetKelompokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'targets' => ['required_without:nominal_target', 'array'],
            'nominal_target' => ['required_without:targets', 'numeric', 'min:0', 'max:999999999999.99'],
            'tgl_tgt' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];

        foreach (TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1 as $key) {
            $rules['targets.'.$key] = ['nullable', 'numeric', 'min:0', 'max:999999999999.99'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->has('nominal_target')) {
                return;
            }
            $targets = $this->input('targets');
            if (! is_array($targets) || $targets === []) {
                $validator->errors()->add('targets', 'Minimal satu field target harus diisi.');

                return;
            }
            $hasValue = false;
            foreach (TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1 as $key) {
                if (! array_key_exists($key, $targets)) {
                    continue;
                }
                $v = $targets[$key];
                if ($v !== null && $v !== '') {
                    $hasValue = true;
                    break;
                }
            }
            if (! $hasValue) {
                $validator->errors()->add('targets', 'Minimal satu field target harus diisi.');
            }

            $tgl = $this->input('tgl_tgt');
            if ($tgl !== null && $tgl !== '' && is_string($tgl)) {
                if (! TargetPeriod::isEndOfMonth(trim($tgl))) {
                    $validator->errors()->add(
                        'tgl_tgt',
                        'Tanggal target harus akhir bulan (periode bulanan).'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function targetsForService(): array
    {
        if ($this->has('nominal_target')) {
            return ['STR_SP' => $this->input('nominal_target')];
        }

        $targets = $this->input('targets', []);

        return is_array($targets) ? $targets : [];
    }
}
