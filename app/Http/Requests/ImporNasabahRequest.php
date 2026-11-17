<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImporNasabahRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
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
            'file' => 'file Excel',
            'tanggal' => 'tanggal saldo awal',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'File harus berformat .xlsx (Excel).',
            'file.max' => 'Ukuran file maksimal 5 MB.',
            'tanggal.before_or_equal' => 'Tanggal saldo awal tidak boleh melebihi hari ini.',
        ];
    }
}
