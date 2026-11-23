<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNasabahRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rekening = $this->route('nasabah')?->rekening()->orderBy('id')->first();

        return [
            'no_rekening' => [
                'required',
                'string',
                'max:30',
                Rule::unique('rekening', 'no_rekening')->ignore($rekening?->id),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'angkatan' => ['required', 'string', 'max:30'],
            'program_studi' => ['required', 'string', 'max:100'],
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
            'no_rekening' => 'NISN / nomor rekening',
            'nama' => 'nama customer',
            'angkatan' => 'generation (angkatan)',
            'program_studi' => 'program studi',
        ];
    }
}
