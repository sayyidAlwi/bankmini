<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MulaiShiftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama petugas',
        ];
    }
}
