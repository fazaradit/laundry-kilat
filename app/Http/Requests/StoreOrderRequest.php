<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Tidak ada field total, harga_satuan, atau subtotal di sini.
 * Semua angka uang dihitung server di OrderService (BR-01).
 */
class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'distinct', 'exists:services,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pelanggan wajib dipilih.',
            'customer_id.exists' => 'Pelanggan yang dipilih tidak ditemukan.',
            'items.required' => 'Pesanan harus memiliki minimal satu layanan.',
            'items.array' => 'Format item pesanan tidak valid.',
            'items.min' => 'Pesanan harus memiliki minimal satu layanan.',
            'items.*.service_id.required' => 'Layanan pada setiap baris wajib dipilih.',
            'items.*.service_id.distinct' => 'Layanan yang sama tidak boleh dipilih dua kali. Gabungkan jumlahnya pada satu baris.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah pada setiap baris wajib diisi.',
            'items.*.qty.numeric' => 'Jumlah harus berupa angka.',
            'items.*.qty.min' => 'Jumlah minimal 0,01.',
            'items.*.qty.max' => 'Jumlah maksimal 999,99.',
            'diskon.integer' => 'Diskon harus berupa angka bulat tanpa titik atau koma.',
            'diskon.min' => 'Diskon tidak boleh bernilai negatif.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Layanan nonaktif tidak boleh dipakai di pesanan baru (FR-11 / BR-04).
     * Aturan ini butuh query, jadi tidak bisa ditulis di rules().
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ids = collect($this->input('items', []))
                    ->pluck('service_id')
                    ->filter()
                    ->map(static fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    return;
                }

                $aktif = Service::query()
                    ->aktif()
                    ->whereIn('id', $ids)
                    ->pluck('id')
                    ->map(static fn ($id) => (int) $id)
                    ->all();

                $nonaktif = $ids->diff($aktif);

                if ($nonaktif->isNotEmpty()) {
                    $nama = Service::query()
                        ->whereIn('id', $nonaktif)
                        ->pluck('nama_layanan')
                        ->implode(', ');

                    $validator->errors()->add(
                        'items',
                        "Layanan berikut sudah nonaktif dan tidak bisa dipakai: {$nama}."
                    );
                }
            },
        ];
    }
}
