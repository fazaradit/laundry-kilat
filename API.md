# Dokumentasi REST API Laundry Kilat

Dokumentasi seluruh endpoint REST API aplikasi Laundry Kilat (Laravel 12).
Contoh response di bawah adalah hasil pemanggilan langsung ke aplikasi yang berjalan dengan data
hasil `php artisan migrate:fresh --seed`, bukan contoh yang dikarang.

## Informasi Umum

| Item | Keterangan |
|---|---|
| Base URL | `http://127.0.0.1:8000/api` |
| Format data | JSON (`Content-Type: application/json`) |
| Header wajib | `Accept: application/json` |
| Autentikasi | Tidak ada token |
| Identitas pelaku | Dikirim eksplisit lewat `user_id` di body untuk endpoint tulis |
| Prefix route | Otomatis `/api` dari `routes/api.php` |
| `APP_DEBUG` | `false` saat demo |

Response daftar pesanan memakai pagination Laravel:

```json
{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 15, "per_page": 15, "total": 15 }
}
```

Response daftar layanan **tidak** memakai pagination, karena jumlah layanan master selalu kecil.
Bentuknya hanya `{ "data": [ ... ] }`.

Kode status yang dipakai:

| Status | Arti |
|---|---|
| `200 OK` | Berhasil membaca atau memperbarui |
| `201 Created` | Berhasil membuat resource baru |
| `403 Forbidden` | Aksi tidak diizinkan untuk role pelaku |
| `404 Not Found` | ID yang diminta tidak ada |
| `422 Unprocessable Entity` | Data tidak lolos validasi atau melanggar aturan bisnis |

### Peringatan header `Accept`

`Accept: application/json` bukan formalitas. Kalau header ini tidak dikirim dan validasi gagal,
Laravel menganggap pemanggilnya browser lalu membalas **`302 Redirect`** ke halaman HTML, bukan
`422` dengan body JSON. Di Postman, pastikan tab Headers berisi baris berikut sebelum menguji
skenario gagal.

```
Accept: application/json
```

## Kenapa API Ini Tanpa Token

Seluruh penulisan data di aplikasi web lewat `OrderService` dan `PaymentService`, bukan langsung
dari controller. Service itulah yang memegang aturan bisnis: harga selalu diambil dari master
layanan, total dihitung server, perpindahan status dibatasi state machine, pembatalan hanya untuk
admin, dan sisa tagihan dikunci saat pembayaran dicatat.

Endpoint tulis di API memanggil service yang sama, jadi aturan tersebut tetap berlaku. Yang tidak
bisa dibawa dari web adalah **sesi login**: request API tidak punya cookie, sehingga server tidak
tahu siapa yang sedang memanggil. Karena itu pelaku dikirim eksplisit sebagai `user_id` di body,
pola yang sama dengan `POST /api/loans` pada modul Pertemuan 9.

Konsekuensinya nyata dan tidak disembunyikan: siapa pun yang bisa menjangkau alamat API dapat
mengaku sebagai user mana pun. Pembatasan role tetap berjalan setelah `user_id` dibaca (kasir yang
mengirim `user_id` miliknya sendiri tetap mendapat `403` saat membatalkan pesanan), tetapi API ini
**tidak boleh** dipublikasikan ke internet tanpa lapisan autentikasi di depannya. Pembahasan lengkap
ada di bagian Keterbatasan.

## Ringkasan 8 Endpoint

| No | Method | Endpoint | Fungsi | Status yang mungkin |
|---|---|---|---|---|
| 1 | `GET` | `/api/services` | Daftar layanan | `200`, `422` |
| 2 | `GET` | `/api/orders` | Daftar pesanan dengan filter dan pagination | `200`, `422` |
| 3 | `GET` | `/api/orders/{id}` | Detail satu pesanan | `200`, `404` |
| 4 | `GET` | `/api/stats` | Angka ringkasan untuk dashboard | `200` |
| 5 | `POST` | `/api/orders` | Buat pesanan baru | `201`, `422` |
| 6 | `PUT` | `/api/orders/{order}/status` | Ubah status satu langkah | `200`, `403`, `422` |
| 7 | `POST` | `/api/orders/{order}/payments` | Catat pembayaran | `201`, `422` |
| 8 | `DELETE` | `/api/services/{service}` | Hapus layanan yang belum dipakai | `200`, `404`, `422` |

Pesanan **tidak** punya endpoint `DELETE`. Alasannya ada di bagian Keterbatasan.

## Filter dan Query String

### `GET /api/orders`

| Parameter | Tipe | Aturan |
|---|---|---|
| `status` | string | `diterima`, `diproses`, `selesai`, `dibatalkan`. Nama tak dikenal diabaikan |
| `dari` | `Y-m-d` | Batas awal `tanggal_masuk`, inklusif |
| `sampai` | `Y-m-d` | Batas akhir `tanggal_masuk`, inklusif, tidak boleh sebelum `dari` |
| `per_page` | integer | 1 sampai 100, default 15 |
| `page` | integer | Halaman, mengikuti paginator Laravel |

### `GET /api/services`

| Parameter | Tipe | Aturan |
|---|---|---|
| `status` | string | `aktif` atau `nonaktif`. Nilai lain ditolak `422` |

### Perbedaan perilaku status yang tidak dikenal

Kedua endpoint memperlakukan status salah ketik secara berbeda, dan ini disengaja.

| Request | Hasil | Alasan |
|---|---|---|
| `/api/orders?status=entah` | `200`, semua pesanan | Halaman Laporan memakai endpoint ini. URL salah ketik tidak boleh memecah laporan |
| `/api/services?status=xxx` | `422` | Filter layanan dipakai untuk keperluan administratif, jadi kesalahan input sebaiknya terlihat |

## Skenario Gagal

Semua contoh di bawah dikirim dengan header `Accept: application/json`.

| Request | Status | Body |
|---|---|---|
| `GET /api/orders/99999` | `404` | `{"message": "No query results for model [App\\Models\\Order] 99999"}` |
| `GET /api/orders?dari=2026-11-10&sampai=2026-11-01` | `422` | `{"message": "The sampai field must be a date after or equal to dari.", ...}` |
| `GET /api/orders?per_page=500` | `422` | `{"message": "The per page field must not be greater than 100.", ...}` |
| `GET /api/services?status=xxx` | `422` | `{"message": "The selected status is invalid.", ...}` |
| `PUT /api/orders/{id}/status` dengan `user_id` kasir dan `status=dibatalkan` | `403` | `{"message": "Pembatalan pesanan hanya dapat dilakukan oleh Admin."}` |
| `POST /api/orders` tanpa `user_id` | `422` | `{"errors": {"user_id": ["Petugas (user_id) wajib diisi ..."]}}` |
| `POST /api/orders/{id}/payments` melebihi sisa | `422` | `{"errors": {"jumlah_bayar": ["Jumlah bayar melebihi sisa tagihan (Rp 27.500)."]}}` |
| `DELETE /api/services/1` untuk layanan yang sudah dipakai | `422` | `{"errors": {"service": ["Layanan \"Cuci Kering\" sudah dipakai pada pesanan ..."]}}` |

Pesan validasi endpoint `GET` masih pesan bawaan Laravel berbahasa Inggris
(`The sampai field must be ...`). Endpoint tulis memakai pesan berbahasa Indonesia karena Form
Request-nya mewarisi versi web. Penjelasannya ada di Catatan Implementasi.

## Detail Endpoint

### 1. `GET /api/services` - Daftar Layanan

- Response `200 OK`:

```json
{
    "data": [
        {
            "id": 5,
            "nama_layanan": "Bed Cover",
            "satuan": "pcs",
            "harga": 30000,
            "estimasi_hari": 4,
            "status": "aktif"
        },
        {
            "id": 1,
            "nama_layanan": "Cuci Kering",
            "satuan": "kg",
            "harga": 7000,
            "estimasi_hari": 2,
            "status": "aktif"
        },
        {
            "id": 4,
            "nama_layanan": "Cuci Sepatu",
            "satuan": "pcs",
            "harga": 25000,
            "estimasi_hari": 3,
            "status": "aktif"
        },
        {
            "id": 3,
            "nama_layanan": "Cuci Setrika",
            "satuan": "kg",
            "harga": 10000,
            "estimasi_hari": 3,
            "status": "aktif"
        },
        {
            "id": 2,
            "nama_layanan": "Setrika",
            "satuan": "kg",
            "harga": 5000,
            "estimasi_hari": 2,
            "status": "aktif"
        }
    ]
}
```

Urutan mengikuti `nama_layanan`, bukan `id`. Hanya kolom yang boleh dilihat klien yang dikirim;
`created_at` dan `updated_at` tidak ikut.

- Response `422` untuk `?status=xxx`:

```json
{
    "message": "The selected status is invalid.",
    "errors": {
        "status": [
            "The selected status is invalid."
        ]
    }
}
```

### 2. `GET /api/orders` - Daftar Pesanan

- Request contoh: `GET /api/orders?per_page=1`

- Response `200 OK` (array `meta.links` milik paginator dipotong agar ringkas):

```json
{
    "data": [
        {
            "id": 13,
            "kode_order": "LDR-20261110-0004",
            "customer": {
                "id": 1,
                "nama": "Artanto Budiman",
                "nomor_telepon": "086097723406"
            },
            "petugas": {
                "id": 2,
                "name": "Siti Rahmawati"
            },
            "tanggal_masuk": "2026-11-10 11:24:00",
            "estimasi_selesai": "2026-11-12",
            "subtotal": 10500,
            "diskon": 0,
            "total": 10500,
            "dibayar": 0,
            "sisa_bayar": 10500,
            "status_pelunasan": "Belum Bayar",
            "status": "diterima",
            "items": [
                {
                    "service": "Cuci Kering",
                    "satuan": "kg",
                    "qty": "1.50",
                    "harga_satuan": 7000,
                    "subtotal": 10500
                }
            ]
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8000/api/orders?per_page=1&page=1",
        "last": "http://127.0.0.1:8000/api/orders?per_page=1&page=15",
        "prev": null,
        "next": "http://127.0.0.1:8000/api/orders?per_page=1&page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 15,
        "per_page": 1,
        "to": 1,
        "total": 15,
        "total_nilai": 492000,
        "total_dibayar": 225000
    }
}
```

`meta.total_nilai` dan `meta.total_dibayar` adalah agregat **seluruh periode filter**, bukan hanya
halaman yang sedang tampil. Keduanya dihitung sebelum `paginate()`, jadi angkanya tidak berubah saat
pindah halaman.

`total_nilai` menjumlah `orders.total`, sedangkan `total_dibayar` menjumlah `payments.jumlah_bayar`
untuk pesanan yang masuk filter. Dua angka ini sengaja dipisah: pesanan yang masuk hari ini bisa
dibayar sebagian kemarin, dan pesanan yang masuk hari ini bisa belum dibayar sama sekali.

### 3. `GET /api/orders/{id}` - Detail Pesanan

- Request contoh: `GET /api/orders/11`

- Response `200 OK`:

```json
{
    "data": {
        "id": 11,
        "kode_order": "LDR-20261110-0002",
        "customer": {
            "id": 11,
            "nama": "Titi Namaga",
            "nomor_telepon": "083749648508"
        },
        "petugas": {
            "id": 2,
            "name": "Siti Rahmawati"
        },
        "tanggal_masuk": "2026-11-10 09:10:00",
        "estimasi_selesai": "2026-11-14",
        "subtotal": 44000,
        "diskon": 3000,
        "total": 41000,
        "dibayar": 15000,
        "sisa_bayar": 26000,
        "status_pelunasan": "DP",
        "status": "diterima",
        "items": [
            {
                "service": "Cuci Kering",
                "satuan": "kg",
                "qty": "2.00",
                "harga_satuan": 7000,
                "subtotal": 14000
            },
            {
                "service": "Bed Cover",
                "satuan": "pcs",
                "qty": "1.00",
                "harga_satuan": 30000,
                "subtotal": 30000
            }
        ]
    }
}
```

Pesanan ini memperlihatkan pembayaran bertahap: `total` 41.000, sudah dibayar 15.000, sisa 26.000,
sehingga `status_pelunasan` bernilai `DP`.

- Response `404 Not Found`:

```json
{
    "message": "No query results for model [App\\Models\\Order] 99999"
}
```

Response `404` ini bersih karena `APP_DEBUG=false`. Dengan `APP_DEBUG=true`, Laravel menambahkan
`exception`, `file`, `line`, dan `trace` berisi path folder proyek.

### 4. `GET /api/stats` - Statistik Ringkasan

- Response `200 OK`:

```json
{
    "pesanan_hari_ini": 4,
    "pesanan_aktif": 8,
    "pendapatan_hari_ini": 15000,
    "total_pelanggan": 12
}
```

| Field | Sumber |
|---|---|
| `pesanan_hari_ini` | `COUNT(orders)` dengan `tanggal_masuk` hari ini |
| `pesanan_aktif` | `COUNT(orders)` berstatus `diterima` atau `diproses` |
| `pendapatan_hari_ini` | `SUM(payments.jumlah_bayar)` dengan `tanggal_bayar` hari ini |
| `total_pelanggan` | `COUNT(customers)` |

`pendapatan_hari_ini` menghitung uang yang benar-benar masuk, bukan nilai pesanan. Karena itu
angkanya bisa lebih kecil dari `meta.total_nilai` pada `GET /api/orders`, dan itu memang tujuannya.

### 5. `POST /api/orders` - Buat Pesanan

- Body (raw JSON):

```json
{
    "user_id": 2,
    "customer_id": 1,
    "items": [
        { "service_id": 1, "qty": 3.5 },
        { "service_id": 5, "qty": 1 }
    ],
    "diskon": 2000,
    "catatan": "Antar sore"
}
```

| Field | Wajib | Keterangan |
|---|---|---|
| `user_id` | ya | Petugas yang menerima pesanan, harus ada di tabel `users` |
| `customer_id` | ya | Harus ada di tabel `customers` |
| `items` | ya | Minimal satu baris, `service_id` tidak boleh dobel |
| `items.*.qty` | ya | 0,01 sampai 999,99 |
| `diskon` | tidak | Rupiah, tidak boleh negatif, tidak boleh melebihi subtotal |
| `catatan` | tidak | Maksimal 1000 karakter |

Field `total`, `subtotal`, `harga_satuan`, dan `status` **tidak diterima** dari klien. Kalau tetap
dikirim, nilainya diabaikan dan dihitung server. Harga diambil dari master layanan, status awal
selalu `diterima`.

- Response `201 Created`:

```json
{
    "data": {
        "id": 16,
        "kode_order": "LDR-20261110-0005",
        "customer": {
            "id": 1,
            "nama": "Artanto Budiman",
            "nomor_telepon": "086097723406"
        },
        "petugas": {
            "id": 2,
            "name": "Siti Rahmawati"
        },
        "tanggal_masuk": "2026-11-10 16:25:43",
        "estimasi_selesai": "2026-11-14",
        "subtotal": 54500,
        "diskon": 2000,
        "total": 52500,
        "dibayar": 0,
        "sisa_bayar": 52500,
        "status_pelunasan": "Belum Bayar",
        "status": "diterima",
        "items": [
            {
                "service": "Cuci Kering",
                "satuan": "kg",
                "qty": "3.50",
                "harga_satuan": 7000,
                "subtotal": 24500
            },
            {
                "service": "Bed Cover",
                "satuan": "pcs",
                "qty": "1.00",
                "harga_satuan": 30000,
                "subtotal": 30000
            }
        ]
    }
}
```

`subtotal` 54.500 berasal dari 3,5 kg × 7.000 + 1 × 30.000. Setelah diskon 2.000, `total` menjadi
52.500. `kode_order` dibuat server dengan format `LDR-YYYYMMDD-NNNN` dan nomor urut per hari.

- Response `422` bila body `{}`:

```json
{
    "message": "Pelanggan wajib dipilih. (and 2 more errors)",
    "errors": {
        "customer_id": [
            "Pelanggan wajib dipilih."
        ],
        "items": [
            "Pesanan harus memiliki minimal satu layanan."
        ],
        "user_id": [
            "Petugas (user_id) wajib diisi karena API tidak memakai sesi login."
        ]
    }
}
```

- Response `422` bila layanan sudah nonaktif:

```json
{
    "message": "Layanan berikut sudah nonaktif dan tidak bisa dipakai: Cuci Express.",
    "errors": {
        "items": [
            "Layanan berikut sudah nonaktif dan tidak bisa dipakai: Cuci Express."
        ]
    }
}
```

### 6. `PUT /api/orders/{order}/status` - Ubah Status

- Body (raw JSON):

```json
{
    "user_id": 2,
    "status": "diproses",
    "catatan": "Mulai dikerjakan"
}
```

| Field | Wajib | Keterangan |
|---|---|---|
| `user_id` | ya | Pelaku perubahan status |
| `status` | ya | Salah satu dari `diterima`, `diproses`, `selesai`, `dibatalkan` |
| `catatan` | tidak | Maksimal 255 karakter, disimpan di `order_status_logs` |

Perpindahan yang sah hanya satu langkah: `diterima → diproses → selesai`. Pembatalan boleh dari
`diterima` atau `diproses`, dan hanya oleh user dengan `role = admin`. `user_id` kasir yang meminta
`dibatalkan` tetap ditolak, karena pemeriksaan role memakai user yang dikirim tersebut.

- Response `200 OK`:

```json
{
    "data": {
        "id": 16,
        "kode_order": "LDR-20261110-0005",
        "customer": {
            "id": 1,
            "nama": "Artanto Budiman",
            "nomor_telepon": "086097723406"
        },
        "petugas": {
            "id": 2,
            "name": "Siti Rahmawati"
        },
        "tanggal_masuk": "2026-11-10 16:25:43",
        "estimasi_selesai": "2026-11-14",
        "subtotal": 54500,
        "diskon": 2000,
        "total": 52500,
        "dibayar": 0,
        "sisa_bayar": 52500,
        "status_pelunasan": "Belum Bayar",
        "status": "diproses",
        "items": [
            {
                "service": "Cuci Kering",
                "satuan": "kg",
                "qty": "3.50",
                "harga_satuan": 7000,
                "subtotal": 24500
            },
            {
                "service": "Bed Cover",
                "satuan": "pcs",
                "qty": "1.00",
                "harga_satuan": 30000,
                "subtotal": 30000
            }
        ]
    }
}
```

Bentuk response-nya persis sama dengan `GET /api/orders/{id}`, supaya klien tidak perlu memanggil
ulang setelah mengubah status. `petugas` tetap petugas yang menerima pesanan; pelaku perubahan
status tercatat di `order_status_logs` dan tidak muncul di response ini.

- Response `403` bila `user_id` menunjuk kasir dan `status` bernilai `dibatalkan`:

```json
{
    "message": "Pembatalan pesanan hanya dapat dilakukan oleh Admin."
}
```

- Response `422` bila status dilompati (`diterima` langsung ke `selesai`):

```json
{
    "message": "Status tidak boleh berpindah dari Diterima ke Selesai.",
    "errors": {
        "status": [
            "Status tidak boleh berpindah dari Diterima ke Selesai."
        ]
    }
}
```

- Response `422` bila pesanan sudah final:

```json
{
    "message": "Pesanan berstatus Selesai tidak dapat diubah lagi.",
    "errors": {
        "status": [
            "Pesanan berstatus Selesai tidak dapat diubah lagi."
        ]
    }
}
```

### 7. `POST /api/orders/{order}/payments` - Catat Pembayaran

- Body (raw JSON, tunai):

```json
{
    "user_id": 2,
    "metode": "tunai",
    "jumlah_bayar": 25000,
    "uang_diterima": 30000
}
```

- Body (raw JSON, transfer atau QRIS):

```json
{
    "user_id": 2,
    "metode": "transfer",
    "jumlah_bayar": 25000
}
```

| Field | Wajib | Keterangan |
|---|---|---|
| `user_id` | ya | Kasir yang mencatat pembayaran |
| `metode` | ya | `tunai`, `transfer`, atau `qris` |
| `jumlah_bayar` | ya | Dialokasikan ke tagihan, tidak boleh melebihi `sisa_bayar` |
| `uang_diterima` | tunai | Uang fisik yang diserahkan, minimal sama dengan `jumlah_bayar` |
| `catatan` | tidak | Maksimal 255 karakter |

Dua angka uang sengaja dipisah. `jumlah_bayar` adalah bagian yang mengurangi tagihan, sedangkan
`uang_diterima` adalah uang fisik yang diterima kasir. `kembalian` dihitung server dari
`uang_diterima - jumlah_bayar` dan hanya bermakna untuk tunai. Untuk transfer dan QRIS,
`uang_diterima` disimpan `null` dan `kembalian` bernilai 0.

- Response `201 Created`:

```json
{
    "data": {
        "id": 9,
        "order_id": 16,
        "metode": "tunai",
        "jumlah_bayar": 25000,
        "uang_diterima": 30000,
        "kembalian": 5000,
        "catatan": null,
        "tanggal_bayar": "2026-11-10 16:25:53",
        "petugas": {
            "id": 2,
            "name": "Siti Rahmawati"
        },
        "order": {
            "id": 16,
            "kode_order": "LDR-20261110-0005",
            "total": 52500,
            "dibayar": 25000,
            "sisa_bayar": 27500,
            "status_pelunasan": "DP"
        }
    }
}
```

Response menyertakan ringkasan pesanan setelah pembayaran dicatat, sehingga klien langsung tahu
sisa tagihan dan status pelunasan terbaru tanpa request tambahan.

- Response `422` bila melebihi sisa tagihan:

```json
{
    "message": "Jumlah bayar melebihi sisa tagihan (Rp 27.500).",
    "errors": {
        "jumlah_bayar": [
            "Jumlah bayar melebihi sisa tagihan (Rp 27.500)."
        ]
    }
}
```

- Response `422` bila tunai tanpa `uang_diterima`:

```json
{
    "message": "Uang diterima wajib diisi untuk pembayaran tunai.",
    "errors": {
        "uang_diterima": [
            "Uang diterima wajib diisi untuk pembayaran tunai."
        ]
    }
}
```

- Response `422` bila pesanan sudah dibatalkan:

```json
{
    "message": "Pesanan yang dibatalkan tidak bisa menerima pembayaran.",
    "errors": {
        "jumlah_bayar": [
            "Pesanan yang dibatalkan tidak bisa menerima pembayaran."
        ]
    }
}
```

### 8. `DELETE /api/services/{service}` - Hapus Layanan

- Response `200 OK`:

```json
{
    "message": "Layanan \"Layanan Uji\" berhasil dihapus."
}
```

Yang boleh dihapus hanya layanan yang belum pernah dipakai pesanan. Layanan yang sudah dipakai
harus dinonaktifkan, bukan dihapus, supaya riwayat pesanan dan snapshot harga tetap utuh.

- Response `422` bila layanan sudah dipakai:

```json
{
    "message": "Layanan \"Cuci Kering\" sudah dipakai pada pesanan, jadi tidak bisa dihapus. Ubah statusnya menjadi nonaktif.",
    "errors": {
        "service": [
            "Layanan \"Cuci Kering\" sudah dipakai pada pesanan, jadi tidak bisa dihapus. Ubah statusnya menjadi nonaktif."
        ]
    }
}
```

- Response `404 Not Found`:

```json
{
    "message": "No query results for model [App\\Models\\Service] 99999"
}
```

## Catatan Implementasi

### Resource untuk bentuk response

`GET` memakai `OrderResource` dan `ServiceResource`, sehingga bentuk response ditulis di satu tempat
dan tidak ada kolom internal yang ikut terkirim. Endpoint tulis juga mengembalikan bentuk yang sama
lewat `detailResponse()`, jadi `POST` dan `PUT` tidak punya format JSON sendiri.

Pengecualiannya `POST /payments`, yang memakai `response()->json()` langsung. Bentuk data pembayaran
berbeda dari pesanan, dan membuat Resource khusus untuk satu endpoint di akhir pengerjaan hanya
menambah file tanpa manfaat.

### Mengapa `qty` berupa string

`OrderItem` memakai cast `decimal:2`. Laravel mengembalikan hasil cast desimal sebagai string agar
presisi tidak hilang saat melewati JSON, jadi `qty` tampil `"1.50"`, bukan `1.5`. Ini perilaku
bawaan, bukan kesalahan penulisan. Klien yang butuh angka harus mengonversinya sendiri.

### N+1 dicegah dengan `withSum`

`dibayar` dan `sisa_bayar` dihitung dari tabel `payments`. Kalau accessor dipanggil per baris, satu
halaman berisi 15 pesanan menghasilkan 15 query tambahan. Karena itu query daftar memakai
`withSum('payments as dibayar', 'jumlah_bayar')`, dan accessor `Order::getDibayarAttribute()`
memakai hasil agregat itu bila tersedia.

### Agregat dihitung sebelum `paginate()`

`total_nilai` dan `total_dibayar` dihitung dari `clone $query` sebelum pagination. Kalau dihitung
setelahnya, angkanya hanya mencakup halaman yang sedang tampil dan laporan periode akan berubah
setiap kali halaman diganti.

Nilai itu disisipkan ke `meta` lewat `getData(true)`. Pemakaian `additional(['meta' => ...])`
menghasilkan `meta.total` berbentuk array `[15, 15]`, karena Laravel menumpuk kunci yang sudah ada.

### Form Request API mewarisi versi web

Tiga Form Request baru hanya menimpa `authorize()` dan menambah aturan `user_id`:

```
Api\StoreOrderApiRequest    extends StoreOrderRequest
Api\UpdateStatusApiRequest  extends UpdateOrderStatusRequest
Api\StorePaymentApiRequest  extends StorePaymentRequest
```

`authorize()` versi web memeriksa `$this->user() !== null`, dan di route API nilai itu selalu
`null`. Tanpa pewarisan ini, seluruh request tulis akan gagal `403`. Dengan pewarisan, aturan
validasi dan pesan bahasa Indonesia tidak ditulis dua kali, jadi web dan API tidak mungkin
memakai aturan berbeda.

### Dua lapis perlindungan pada `DELETE`

Pemeriksaan `orderItems()->exists()` di controller memberi pesan yang bisa dibaca pengguna. Di
belakangnya, foreign key `order_items.service_id` memakai `restrictOnDelete()`, sehingga database
menolak penghapusan meskipun ada jalur kode lain yang lupa memeriksa. Keduanya sengaja dipasang.

### Pesan validasi `GET` masih bahasa Inggris

`Api\OrderController@validatedFilter()` dan `Api\ServiceController@index()` memakai
`$request->validate()` langsung, sehingga pesannya bawaan Laravel:

```
The sampai field must be a date after or equal to dari.
The per page field must not be greater than 100.
The selected status is invalid.
```

Ini tidak diterjemahkan karena pesan tersebut hanya dibaca pemanggil API, bukan pengguna akhir di
layar. Endpoint tulis berbeda: pesannya tampil di Postman saat demo dan memakai bahasa Indonesia
lewat Form Request.

### `APP_DEBUG=false` saat demo

Dengan `APP_DEBUG=true`, `GET /api/orders/99999` membalas stack trace berisi path folder proyek dan
potongan kode. Dengan `APP_DEBUG=false`, body-nya hanya `{"message": "..."}`. Nilai ini disetel di
`.env` dan `.env.example`.

## Keterbatasan

Bagian ini ditulis apa adanya supaya tidak menjadi temuan saat tanya jawab.

**1. Tidak ada token, `user_id` bisa dipalsukan.**
Siapa pun yang bisa menjangkau alamat API dapat mengirim `user_id` milik user lain. Pembatasan role
tetap berjalan terhadap user yang dikirim, tetapi API ini tidak boleh diekspos ke internet tanpa
autentikasi. Cakupan modul Pertemuan 9 berhenti di endpoint publik; menambah Sanctum atau API key
akan menjadi langkah berikutnya.

**2. Pesanan tidak bisa dihapus lewat API.**
`payments`, `order_items`, dan `order_status_logs` memakai `cascadeOnDelete()` terhadap `orders`.
Menghapus pesanan berarti menghapus riwayat pembayaran dan log statusnya sekaligus. Untuk aplikasi
yang menyimpan uang, itu berbahaya. Pesanan yang salah input dibatalkan lewat
`PUT /api/orders/{order}/status`, bukan dihapus.

**3. `total_nilai` memasukkan pesanan yang dibatalkan.**
Angka ini menjumlah `orders.total` tanpa memandang status, karena mewakili nilai transaksi yang
masuk pada periode tersebut. Realisasi kas ada di `total_dibayar` dan di `pendapatan_hari_ini`.
Selisih keduanya adalah tagihan yang belum tertagih, bukan kesalahan hitung.

**4. Hanya `per_page` yang dibatasi, bukan jumlah halaman.**
`per_page` maksimal 100, tetapi tidak ada pembatasan total request per klien. Untuk pemakaian
internal dengan satu atau dua kasir, ini memadai; untuk API publik, rate limiting perlu ditambahkan.
