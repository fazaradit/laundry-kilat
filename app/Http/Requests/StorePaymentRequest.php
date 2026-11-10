<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * `kembalian` sengaja tidak ada di sini. Kembalian dihitung server dari
 * selisih uang_diterima dan jumlah_bayar (FR-30), karena angka uang tidak
 * boleh berasal dari input pengguna.
 */
class StorePaymentRequest extends FormRequest
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
            'metode' => ['required', 'in:tunai,transfer,qris'],
            'jumlah_bayar' => ['required', 'integer', 'min:1'],
            'uang_diterima' => ['nullable', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'metode.in' => 'Metode pembayaran tidak dikenal.',
            'jumlah_bayar.required' => 'Jumlah bayar wajib diisi.',
            'jumlah_bayar.integer' => 'Jumlah bayar harus berupa angka bulat tanpa titik atau koma.',
            'jumlah_bayar.min' => 'Jumlah bayar minimal Rp 1.',
            'uang_diterima.integer' => 'Uang diterima harus berupa angka bulat tanpa titik atau koma.',
            'uang_diterima.min' => 'Uang diterima minimal Rp 1.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }

    /**
     * Tiga aturan yang butuh data pesanan, jadi tidak bisa ditulis di rules().
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Order|null $order */
                $order = $this->route('order');

                if (! $order instanceof Order) {
                    return;
                }

                $jumlah = (int) $this->input('jumlah_bayar');
                $sisa = $order->sisa_bayar;

                if ($order->status->value === 'dibatalkan') {
                    $validator->errors()->add('jumlah_bayar', 'Pesanan yang dibatalkan tidak bisa menerima pembayaran.');

                    return;
                }

                if ($sisa <= 0) {
                    $validator->errors()->add('jumlah_bayar', 'Pesanan ini sudah lunas.');

                    return;
                }

                if ($jumlah > $sisa) {
                    $validator->errors()->add(
                        'jumlah_bayar',
                        'Jumlah bayar melebihi sisa tagihan (Rp '.number_format($sisa, 0, ',', '.').').'
                    );
                }

                // Tunai wajib menyebut uang yang diserahkan, karena dari situ
                // kembalian dihitung. Metode lain tidak butuh.
                if ($this->input('metode') === 'tunai') {
                    $diterima = $this->input('uang_diterima');

                    if ($diterima === null || $diterima === '') {
                        $validator->errors()->add('uang_diterima', 'Uang diterima wajib diisi untuk pembayaran tunai.');

                        return;
                    }

                    if ((int) $diterima < $jumlah) {
                        $validator->errors()->add(
                            'uang_diterima',
                            'Uang diterima kurang dari jumlah bayar (Rp '.number_format($jumlah, 0, ',', '.').').'
                        );
                    }
                }
            },
        ];
    }
}
