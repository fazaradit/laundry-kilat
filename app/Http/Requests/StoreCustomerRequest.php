<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
        return [
            'nama' => ['required', 'string', 'max:100'],
            'nomor_telepon' => ['required', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pelanggan wajib diisi.',
            'nama.max' => 'Nama pelanggan maksimal 100 karakter.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'nomor_telepon.regex' => 'Nomor telepon harus diawali 0 dan terdiri dari 9-14 digit angka.',
            'alamat.max' => 'Alamat maksimal 1000 karakter.',
        ];
    }

    /**
     * Normalisasi input sebelum divalidasi: buang spasi/strip pada nomor telepon.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('nomor_telepon')) {
            $this->merge([
                'nomor_telepon' => preg_replace('/[\s\-]+/', '', (string) $this->input('nomor_telepon')),
            ]);
        }
    }
}
