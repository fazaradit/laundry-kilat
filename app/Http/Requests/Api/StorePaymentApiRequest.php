<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\StorePaymentRequest;

/**
 * Versi API dari StorePaymentRequest.
 *
 * Aturan sisa tagihan dan kecukupan uang tunai tetap ditegakkan ulang di
 * PaymentService, karena keputusannya bergantung pada isi database saat itu.
 */
class StorePaymentApiRequest extends StorePaymentRequest
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
        return array_merge(parent::rules(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'user_id.required' => 'Petugas (user_id) wajib diisi karena API tidak memakai sesi login.',
            'user_id.exists' => 'Petugas dengan user_id tersebut tidak ditemukan.',
        ]);
    }
}
