<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\StoreOrderRequest;

/**
 * Versi API dari StoreOrderRequest.
 *
 * Aturan validasi dan pesan bahasa Indonesia diwarisi apa adanya, jadi tidak
 * ada kemungkinan dua jalur (web dan API) punya aturan berbeda. Yang ditimpa
 * hanya authorize(), karena di route API tidak ada sesi login sehingga
 * pemeriksaan `$this->user() !== null` milik versi web selalu gagal.
 */
class StoreOrderApiRequest extends StoreOrderRequest
{
    /**
     * API tidak memakai sesi. Pelaku dikirim eksplisit lewat `user_id`.
     */
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
