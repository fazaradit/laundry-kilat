<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\UpdateOrderStatusRequest;

/**
 * Versi API dari UpdateOrderStatusRequest.
 *
 * Aturan transisi status tetap ditegakkan di OrderService, bukan di sini.
 * Form Request hanya memastikan bentuk input benar dan pelaku dikenal.
 */
class UpdateStatusApiRequest extends UpdateOrderStatusRequest
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
