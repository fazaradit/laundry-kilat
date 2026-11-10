<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_layanan' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'in:kg,pcs'],
            'harga' => ['required', 'integer', 'min:500', 'max:10000000'],
            'estimasi_hari' => ['required', 'integer', 'min:1', 'max:14'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_layanan.required' => 'Nama layanan wajib diisi.',
            'nama_layanan.max' => 'Nama layanan maksimal 100 karakter.',
            'satuan.required' => 'Satuan wajib dipilih.',
            'satuan.in' => 'Satuan harus kg atau pcs.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat (tanpa titik atau koma).',
            'harga.min' => 'Harga minimal Rp 500.',
            'harga.max' => 'Harga maksimal Rp 10.000.000.',
            'estimasi_hari.required' => 'Estimasi hari wajib diisi.',
            'estimasi_hari.integer' => 'Estimasi hari harus berupa angka.',
            'estimasi_hari.min' => 'Estimasi hari minimal 1.',
            'estimasi_hari.max' => 'Estimasi hari maksimal 14.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}
