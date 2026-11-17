<?php

namespace App\Http\Requests;

class LaporanFilterRequest extends DateRangeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'akun_id' => ['nullable', 'integer', 'exists:akun,id'],
        ];
    }
}
