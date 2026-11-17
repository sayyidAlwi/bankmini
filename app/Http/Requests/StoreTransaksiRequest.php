<?php

namespace App\Http\Requests;

use App\Models\Rekening;
use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransaksiRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rekening_id' => [
                'required',
                'integer',
                Rule::exists('rekening', 'id')->where('status', Rekening::STATUS_AKTIF),
            ],
            'jenis' => [
                'required',
                Rule::in([Transaksi::JENIS_SETORAN, Transaksi::JENIS_PENARIKAN]),
            ],
            'nominal' => [
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:9999999999999.99',
                'regex:/^\d{1,13}(\.\d{1,2})?$/',
            ],
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
            'rekening_id' => 'rekening',
            'jenis' => 'jenis transaksi',
            'nominal' => 'nominal',
        ];
    }
}
