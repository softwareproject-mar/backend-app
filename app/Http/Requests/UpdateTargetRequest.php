<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'JLH_AGT_BR' => ['nullable', 'integer'],
            'STR_SP' => ['nullable', 'string', 'max:50'],
            'SLD_SP' => ['nullable', 'string', 'max:50'],
            'STR_SW' => ['nullable', 'string', 'max:50'],
            'SLD_SW' => ['nullable', 'string', 'max:50'],
            'STR_SS' => ['nullable', 'string', 'max:50'],
            'SLD_SS' => ['nullable', 'string', 'max:50'],
            'STR_SHR' => ['nullable', 'string', 'max:50'],
            'SLD_SHR' => ['nullable', 'string', 'max:50'],
            'STR_SMD' => ['nullable', 'string', 'max:50'],
            'SLD_SMD' => ['nullable', 'string', 'max:50'],
            'STR_SPD' => ['nullable', 'string', 'max:50'],
            'SLD_SPD' => ['nullable', 'string', 'max:50'],
            'STR_SBJ' => ['nullable', 'string', 'max:50'],
            'SLD_SBJ' => ['nullable', 'string', 'max:50'],
            'STR_SJP' => ['nullable', 'string', 'max:50'],
            'SLD_SJP' => ['nullable', 'string', 'max:50'],
            'STR_SRY' => ['nullable', 'string', 'max:50'],
            'SLD_SRY' => ['nullable', 'string', 'max:50'],
            'STR_SKA' => ['nullable', 'string', 'max:50'],
            'SLD_SKA' => ['nullable', 'string', 'max:50'],
            'STR_SRI' => ['nullable', 'string', 'max:50'],
            'SLD_SRI' => ['nullable', 'string', 'max:50'],
            'STR_SSD' => ['nullable', 'string', 'max:50'],
            'SLD_SSD' => ['nullable', 'string', 'max:50'],
            'PCR_PJM' => ['nullable', 'string', 'max:50'],
            'SLD_PJM' => ['nullable', 'string', 'max:50'],
            'BNG_PJM' => ['nullable', 'string', 'max:50'],
            'SLD_BNG' => ['nullable', 'string', 'max:50'],
            'ASR_PKK' => ['nullable', 'string', 'max:50'],
            'REK_SHR' => ['nullable', 'integer'],
            'REK_SPD' => ['nullable', 'integer'],
            'REK_SMD' => ['nullable', 'integer'],
            'REK_SRY' => ['nullable', 'integer'],
            'STF_SBJ' => ['nullable', 'integer'],
            'STF_SJP' => ['nullable', 'integer'],
            'JLH_REK' => ['nullable', 'integer'],
            'JLH_TAB' => ['nullable', 'integer'],
            'TBN_PK' => ['nullable', 'integer'],
            'PRC_SHR' => ['nullable', 'string', 'max:50'],
            'JLH_TAR_SHR' => ['nullable', 'integer'],
            'SLD_T_SHR' => ['nullable', 'string', 'max:50'],
            'PRC_SMD' => ['nullable', 'string', 'max:50'],
            'JLH_TAR_SMD' => ['nullable', 'integer'],
            'SLD_T_SMD' => ['nullable', 'string', 'max:50'],
            'PRC_SPD' => ['nullable', 'string', 'max:50'],
            'JLH_TAR_SPD' => ['nullable', 'integer'],
            'SLD_T_SPD' => ['nullable', 'string', 'max:50'],
            'PRC_SRY' => ['nullable', 'string', 'max:50'],
            'JLH_TAR_SRY' => ['nullable', 'integer'],
            'SLD_T_SRY' => ['nullable', 'string', 'max:50'],
        ];
    }
}
