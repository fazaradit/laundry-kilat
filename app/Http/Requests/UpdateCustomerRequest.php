<?php

namespace App\Http\Requests;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    // Aturan validasi identik dengan StoreCustomerRequest; dipisah agar
    // controller tetap membaca intent yang jelas dan mudah ditambah nanti.
}
