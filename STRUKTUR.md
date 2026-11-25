# Struktur Proyek Laundry Kilat

Catatan kegunaan setiap file dan folder. Ditulis untuk menjawab pertanyaan "file ini untuk apa"
tanpa harus membaca ulang seluruh kode.

Dokumen pendamping:

| Dokumen | Isi |
|---|---|
| `README.md` | Cara install, akun demo, hak akses, daftar endpoint |
| `DEMO.md` | Urutan demo UAS beserta angka yang harus muncul |
| `API.md` | Bentuk request dan response 8 endpoint API |

---

## Pola Besar

Aplikasi ini memakai pemisahan tanggung jawab yang sederhana dan konsisten:

```
Request masuk
  -> routes/            : menentukan URL mana yang menangani
  -> Middleware         : memeriksa login dan role
  -> Form Request       : memvalidasi bentuk input
  -> Controller         : mengatur alur, tidak menghitung uang
  -> Service            : aturan bisnis dan transaksi database
  -> Model              : data, relasi, dan accessor
  -> View / Resource    : menampilkan hasil
```

Aturan yang dijaga:

- Angka uang tidak pernah dipercaya dari klien. `OrderService` dan `PaymentService` yang menghitung.
- Validasi tidak ditulis dua kali. Form Request API mewarisi Form Request web.
- Logika yang dipakai web dan API berada di Service, bukan di Controller.
- Tampilan tidak menyimpan aturan bisnis. Tombol yang disembunyikan bukan pengaman.

---

## Struktur Folder

```
laundry-kilat/
├── app/
│   ├── Enums/              nilai tetap dan state machine status
│   ├── Http/
│   │   ├── Controllers/    controller web
│   │   ├── Controllers/Api controller REST API
│   │   ├── Middleware/     pemeriksaan role admin
│   │   ├── Requests/       validasi web
│   │   ├── Requests/Api/   validasi API yang mewarisi versi web
│   │   └── Resources/      bentuk JSON
│   ├── Models/             model Eloquent dan relasi
│   ├── Providers/          konfigurasi boot aplikasi
│   └── Services/           aturan bisnis pesanan dan pembayaran
├── bootstrap/              titik start aplikasi
├── config/                 konfigurasi Laravel
├── database/
│   ├── factories/          data acak untuk tes
│   ├── migrations/         struktur tabel
│   └── seeders/            data demo
├── public/                 document root
├── resources/
│   ├── css/                entry Tailwind
│   ├── js/                 entry JavaScript
│   └── views/              seluruh halaman Blade
├── routes/                 definisi URL web dan API
└── tests/                  tes otomatis
```

---

## 1. `app/Enums`

| File | Kegunaan | Hal penting |
|---|---|---|
| `OrderStatus.php` | Enum empat status pesanan: `diterima`, `diproses`, `selesai`, `dibatalkan` | Di sinilah state machine berada: `berikutnya()`, `bolehPindahKe()`, `bolehDibatalkan()`, `sudahFinal()`, `aktifValues()`. `label()` untuk teks di layar dan `badgeClass()` untuk warna badge |

Status lama seperti `dicuci`, `disetrika`, `siap_diambil`, dan `diambil` sudah dihapus lewat migrasi
`2026_11_10_000002_sederhanakan_status_pesanan.php`.

---

## 2. `app/Models`

| File | Kegunaan | Relasi dan hal penting |
|---|---|---|
| `User.php` | Petugas aplikasi, role `admin` atau `kasir` | `orders()`, `payments()`, `statusLogs()`, dan `isAdmin()`. `isAdmin()` dipakai middleware dan `OrderService` |
| `Customer.php` | Pelanggan laundry | `orders()`. Dipakai untuk menolak hapus pelanggan yang punya riwayat pesanan |
| `Service.php` | Master layanan | `orderItems()`, `scopeAktif()`, `isAktif()`. Cast `harga` dan `estimasi_hari` ke integer |
| `Order.php` | Pesanan | Relasi `customer()`, `user()` sebagai petugas, `items()`, `payments()`, `statusLogs()`. Accessor `dibayar`, `sisa_bayar`, `status_pelunasan`, `sudahLunas()`, `sudahFinal()` |
| `OrderItem.php` | Satu baris layanan dalam pesanan | Cast `qty` ke `decimal:2`, sebab `qty` tampil `"1.50"` di JSON. `harga_satuan` adalah snapshot harga saat transaksi |
| `Payment.php` | Pembayaran pesanan | Menyimpan `jumlah_bayar` sebagai alokasi tagihan, `uang_diterima` sebagai uang fisik, dan `kembalian` hasil hitung server |
| `OrderStatusLog.php` | Riwayat perpindahan status | Menyimpan `status_lama`, `status_baru`, `user_id` pelaku, dan `catatan` |

`status_pelunasan` bukan kolom database. Nilainya diturunkan dari `SUM(payments.jumlah_bayar)`
supaya tidak mungkin berbeda dengan tabel pembayaran.

---

## 3. `app/Services`

| File | Kegunaan | Dipanggil oleh |
|---|---|---|
| `OrderService.php` | `buatPesanan()` dan `ubahStatus()` | Form pesanan web, tombol status, `POST /api/orders`, `PUT /api/orders/{order}/status` |
| `PaymentService.php` | `catatPembayaran()` dengan `lockForUpdate()` | Form pembayaran web dan `POST /api/orders/{order}/payments` |

Aturan yang ditegakkan di sini:

- Harga satuan selalu diambil dari master layanan, tidak pernah dari input.
- Total dihitung server, diskon tidak boleh melebihi subtotal.
- Status hanya boleh maju satu langkah.
- Pembatalan hanya boleh dilakukan admin.
- Sisa tagihan dibaca setelah baris pesanan dikunci, agar dua kasir tidak melunasi tagihan yang sama.

---

## 4. `app/Http/Controllers` (web)

| File | Kegunaan |
|---|---|
| `Controller.php` | Base class kosong bawaan Laravel |
| `AuthController.php` | `showLogin()`, `login()` dengan `Auth::attempt()` dan regenerasi sesi, `logout()` |
| `DashboardController.php` | Mengambil `GET /api/stats` lewat `Http::timeout(5)`. Kalau server API internal mati, statistik tampil nol, bukan error 500 |
| `CustomerController.php` | `index()` dengan pencarian, `create()`, `store()`, `edit()`, `update()`, `destroy()`. Hapus ditolak bila pelanggan punya pesanan |
| `ServiceController.php` | CRUD layanan, hanya untuk admin lewat route. Hapus ditolak bila layanan sudah dipakai pesanan |
| `OrderController.php` | `index()` dengan filter dan pencarian, `create()`, `store()`, `show()`, `struk()`, `report()`, `updateStatus()`, `batalkan()` |
| `PaymentController.php` | `store()` mencatat pembayaran lalu redirect dengan pesan nominal dan kembalian |

Catatan penting `OrderController@report()`: halaman laporan **tidak query database langsung**.
Ia memanggil `GET /api/orders` lalu membungkus hasil JSON dengan `LengthAwarePaginator` manual.
Tujuannya agar laporan dan konsumen API lain memakai jalur data yang sama.

---

## 5. `app/Http/Controllers/Api`

| File | Kegunaan |
|---|---|
| `OrderController.php` | `index()` dengan filter, pagination, dan agregat `meta.total_nilai` serta `meta.total_dibayar`. `show()`, `store()`, `updateStatus()`, `storePayment()`. Helper `validatedFilter()`, `terapkanFilter()`, `detailResponse()` |
| `ServiceController.php` | `index()` daftar layanan dan `destroy()` dengan penjagaan `orderItems()->exists()` |
| `StatsController.php` | Empat angka: pesanan hari ini, pesanan aktif, pendapatan hari ini, total pelanggan |

`detailResponse()` dibuat supaya `POST /api/orders` dan `PUT /api/orders/{order}/status`
mengembalikan bentuk JSON yang persis sama dengan `GET /api/orders/{id}`.

---

## 6. `app/Http/Requests` (validasi web)

| File | Kegunaan |
|---|---|
| `StoreCustomerRequest.php` | Validasi tambah pelanggan. Telepon wajib diawali `0` dan dinormalisasi |
| `UpdateCustomerRequest.php` | Validasi ubah pelanggan, aturan sama dengan tambah |
| `StoreServiceRequest.php` | Validasi tambah layanan, harga minimal 500 |
| `UpdateServiceRequest.php` | Validasi ubah layanan |
| `StoreOrderRequest.php` | Validasi pesanan. `after()` menolak layanan yang sudah nonaktif |
| `UpdateOrderStatusRequest.php` | Status wajib salah satu dari empat nilai enum |
| `StorePaymentRequest.php` | Validasi pembayaran. `after()` mengecek sisa tagihan, pesanan batal, dan kewajiban `uang_diterima` untuk tunai |

`after()` dipakai untuk aturan yang butuh query database, karena `rules()` tidak bisa mengakses data
pesanan saat validasi berjalan.

---

## 7. `app/Http/Requests/Api`

| File | Mewarisi | Perubahan |
|---|---|---|
| `StoreOrderApiRequest.php` | `StoreOrderRequest` | `authorize()` menjadi `true`, menambah aturan `user_id` |
| `UpdateStatusApiRequest.php` | `UpdateOrderStatusRequest` | `authorize()` menjadi `true`, menambah aturan `user_id` |
| `StorePaymentApiRequest.php` | `StorePaymentRequest` | `authorize()` menjadi `true`, menambah aturan `user_id` |

`authorize()` versi web memeriksa `$this->user() !== null`. Di route API tidak ada sesi, sehingga
nilainya selalu `null`. Dengan pewarisan ini, aturan dan pesan bahasa Indonesia tidak ditulis dua kali.

---

## 8. `app/Http/Resources`

| File | Kegunaan |
|---|---|
| `OrderResource.php` | Bentuk JSON pesanan: `customer`, `petugas`, `items[]`, `dibayar`, `sisa_bayar`, `status_pelunasan` |
| `ServiceResource.php` | Bentuk JSON layanan. Hanya kolom yang boleh dilihat klien, tanpa `created_at` dan `updated_at` |

---

## 9. `app/Http/Middleware`

| File | Kegunaan |
|---|---|
| `CheckAdminRole.php` | Menolak `403` jika user bukan admin. Didaftarkan sebagai alias `admin` di `bootstrap/app.php` |

Middleware hanya membatasi halaman. Aturan role yang menyangkut data tetap diperiksa lagi di
`OrderService`, karena request bisa datang dari API atau dari luar tombol.

---

## 10. `app/Providers`

| File | Kegunaan |
|---|---|
| `AppServiceProvider.php` | Mengarahkan seluruh `->links()` ke `vendor.pagination.custom`, karena project memakai CSS polos, bukan markup Tailwind bawaan |

---

## 11. `routes`

| File | Kegunaan |
|---|---|
| `web.php` | Route publik `/login`, grup `auth` untuk seluruh halaman, dan grup `admin` untuk layanan serta pembatalan. Route `orders/report` didaftarkan **sebelum** `Route::resource('orders')` agar tidak tertangkap sebagai `{order}` |
| `api.php` | Delapan endpoint REST API. Otomatis berprefix `/api` |
| `console.php` | Perintah artisan kustom, masih bawaan dan tidak dipakai |

---

## 12. `database/migrations`

| File | Kegunaan |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Tabel `users` termasuk kolom `role`, tabel reset password, dan sessions |
| `0001_01_01_000001_create_cache_table.php` | Tabel cache bawaan Laravel |
| `0001_01_01_000002_create_jobs_table.php` | Tabel queue bawaan Laravel |
| `2026_11_10_000001_create_laundry_tables.php` | Enam tabel inti: `customers`, `services`, `orders`, `order_items`, `payments`, `order_status_logs`. Perhatikan `cascadeOnDelete` pada anak pesanan dan `restrictOnDelete` pada pelanggan serta layanan |
| `2026_11_10_000002_sederhanakan_status_pesanan.php` | Memetakan enum lama enam nilai ke empat nilai baru. Memakai enum jembatan agar MySQL tidak memotong data saat persempitan |
| `2026_11_10_000003_tambah_uang_diterima_pada_pembayaran.php` | Menambah kolom `uang_diterima` untuk memisahkan uang fisik dari alokasi tagihan |

`cascadeOnDelete` pada `payments`, `order_items`, dan `order_status_logs` adalah alasan pesanan tidak
punya endpoint hapus: menghapus pesanan akan ikut menghapus riwayat pembayaran.

---

## 13. `database/seeders`

| File | Kegunaan |
|---|---|
| `DatabaseSeeder.php` | Memanggil empat seeder berurutan sesuai ketergantungan foreign key |
| `UserSeeder.php` | Membuat `admin@laundry.test` dan `kasir@laundry.test`, password `password` |
| `ServiceSeeder.php` | Lima layanan awal dengan harga dan estimasi tetap |
| `CustomerSeeder.php` | Dua belas pelanggan lewat factory, nama bergaya Indonesia |
| `OrderSeeder.php` | Lima belas pesanan konsisten: 4 diterima, 4 diproses, 5 selesai, 2 dibatalkan, dengan 8 pembayaran. Satu pesanan diterima diberi DP hari ini agar Dashboard tidak nol |

Data demo dijaga konsisten dengan aturan aplikasi. Pesanan `selesai` umumnya lunas, tetapi satu
sengaja masih DP untuk menunjukkan bahwa status pengerjaan dan status pelunasan adalah dua hal
terpisah.

---

## 14. `database/factories`

| File | Kegunaan |
|---|---|
| `UserFactory.php` | User acak untuk tes |
| `CustomerFactory.php` | Pelanggan acak |
| `ServiceFactory.php` | Layanan acak, punya state `nonaktif()` |
| `OrderFactory.php` | Pesanan acak, punya state `dibatalkan()` dan `status()` |
| `OrderItemFactory.php` | Baris item acak |
| `PaymentFactory.php` | Pembayaran acak |
| `OrderStatusLogFactory.php` | Log status acak |

Factory dipakai tes. Seeder hanya memakai factory untuk pelanggan, sisanya data tetap agar angka
demo tidak berubah setiap kali seed.

---

## 15. `resources/views`

| File | Kegunaan |
|---|---|
| `layouts/app.blade.php` | Layout utama. Seluruh CSS dan design token ada di file ini, termasuk satu warna accent dan skala radius |
| `layouts/print.blade.php` | Layout struk tanpa navbar, dipakai untuk cetak |
| `partials/navbar.blade.php` | Menu navigasi, nama user, dan role yang sedang login |
| `partials/alert.blade.php` | Menampilkan flash `success` dan `error` |
| `auth/login.blade.php` | Form login |
| `dashboard.blade.php` | Kartu statistik dari `GET /api/stats` |
| `customers/index.blade.php` | Tabel pelanggan dengan pencarian dan pagination |
| `customers/create.blade.php` | Form tambah pelanggan |
| `customers/edit.blade.php` | Form ubah pelanggan |
| `services/index.blade.php` | Tabel layanan, hanya admin |
| `services/create.blade.php` | Form tambah layanan |
| `services/edit.blade.php` | Form ubah layanan |
| `orders/index.blade.php` | Tabel pesanan dengan filter status dan pencarian |
| `orders/create.blade.php` | Form pesanan multi-item dengan JavaScript yang menghitung total di layar |
| `orders/show.blade.php` | Detail pesanan, tombol status satu langkah, form pembayaran, dan riwayat status |
| `orders/struk.blade.php` | Nota cetak memakai harga snapshot |
| `orders/report.blade.php` | Laporan periode dengan dua angka: nilai pesanan dan uang diterima |
| `vendor/pagination/custom.blade.php` | Tampilan pagination pengganti bawaan Laravel |
| `welcome.blade.php` | Sisa scaffold Laravel, tidak dipakai |

JavaScript di `orders/create.blade.php` hanya untuk kenyamanan. Kalau dimatikan atau diubah lewat
DevTools, server tetap menghitung ulang total dari master layanan.

---

## 16. `resources/css` dan `resources/js`

| File | Kegunaan |
|---|---|
| `css/app.css` | Entry Tailwind, hanya `@import 'tailwindcss'` dan font. Styling aplikasi sebenarnya ada di layout |
| `js/app.js` | Entry JavaScript dari Vite |
| `js/bootstrap.js` | Setup axios bawaan Laravel |

---

## 17. `tests`

| File | Jumlah tes | Kegunaan |
|---|---|---|
| `Feature/ApiTest.php` | 11 | API baca: struktur JSON, filter status, rentang tanggal, pagination, total periode, 404, stats |
| `Feature/ApiWriteTest.php` | 19 | API tulis: `user_id`, 403 kasir, lompat status, sisa tagihan, pembayaran tunai, hapus layanan |
| `Feature/Fase0Test.php` | 13 | Fondasi: auth, middleware admin, normalisasi telepon, harga minimal, hapus yang dijaga, status maju |
| `Feature/OrderTest.php` | 21 | Pembuatan pesanan, total, diskon, kode order, estimasi, state machine, tombol status |
| `Feature/PaymentTest.php` | 14 | DP, pelunasan, kembalian, tunai, transfer, pesanan batal, pesanan lunas |
| `Feature/ReportTest.php` | 9 | Laporan dari API, fallback saat API mati, struk memakai harga snapshot |
| `Feature/ExampleTest.php` | 2 | Halaman login bisa dibuka, dashboard butuh login |
| `Unit/ExampleTest.php` | 1 | Tes bawaan `true === true` |
| `TestCase.php` | - | Base class tes Laravel |

Total 90 tes. Jalankan dengan:

```bash
php artisan test
```

Tes memakai database `laundry_kilat_test` sesuai `phpunit.xml`, bukan database demo.

---

## 18. Konfigurasi dan Root

| File | Kegunaan |
|---|---|
| `.env.example` | Template konfigurasi. Berisi `INTERNAL_API_URL` dan `APP_FAKER_LOCALE=id_ID` |
| `bootstrap/app.php` | Mendaftarkan route web, api, console, dan alias middleware `admin` |
| `bootstrap/providers.php` | Daftar service provider |
| `public/index.php` | Titik masuk semua request HTTP |
| `config/services.php` | Satu tambahan penting: `internal_api.base_url` dari `INTERNAL_API_URL` |
| `config/*.php` lainnya | Konfigurasi standar Laravel, tidak diubah |
| `phpunit.xml` | Konfigurasi tes, termasuk database `laundry_kilat_test` |
| `composer.json` | Dependency PHP |
| `package.json` | Dependency JavaScript: Vite dan Tailwind |
| `vite.config.js` | Konfigurasi build asset |
| `artisan` | CLI Laravel |
| `README.md` | Cara install, akun demo, tabel hak akses, daftar endpoint |
| `DEMO.md` | Urutan demo UAS beserta angka verifikasi |
| `API.md` | Dokumentasi delapan endpoint API |
| `STRUKTUR.md` | Dokumen ini |

`APP_DEBUG=false` disetel untuk demo agar error tampil sebagai JSON `{"message": "..."}` tanpa path
folder proyek.

---

## Alur Satu Request

### Halaman web

```
Browser
  -> routes/web.php
  -> middleware auth atau admin
  -> Form Request
  -> Controller
  -> Service (DB::transaction)
  -> Model
  -> redirect + flash message
  -> Blade view
```

### Endpoint API

```
Postman atau klien lain
  -> routes/api.php
  -> Api Form Request (mewarisi aturan web, menambah user_id)
  -> Api Controller
  -> Service yang sama dengan web
  -> Resource atau response()->json()
```

---

## File yang Sering Ditanya Saat Demo

| Pertanyaan | Jawaban singkat | File |
|---|---|---|
| Kenapa total tidak bisa diubah dari form? | Total dihitung ulang di server dari harga master | `app/Services/OrderService.php` |
| Kenapa status tidak bisa melompat? | State machine di enum dan service | `app/Enums/OrderStatus.php`, `app/Services/OrderService.php` |
| Kenapa kasir tidak bisa membatalkan? | Diperiksa middleware di web dan `abort(403)` di service | `CheckAdminRole.php`, `OrderService.php` |
| Kenapa struk lama tidak berubah saat harga naik? | `order_items.harga_satuan` adalah snapshot | `OrderResource.php`, `orders/struk.blade.php` |
| Kenapa laporan menampilkan dua angka? | Nilai pesanan berbeda dari uang yang sudah masuk | `Api/OrderController.php`, `orders/report.blade.php` |
| Kenapa perlu dua server? | `php artisan serve` satu request per waktu, laporan memanggil API sendiri | `DashboardController.php`, `config/services.php` |
| Kenapa API tidak memakai token? | Mengikuti cakupan modul, identitas dikirim lewat `user_id` | `routes/api.php`, `Requests/Api/*` |
| Kenapa pesanan tidak bisa dihapus? | `cascadeOnDelete` akan ikut menghapus riwayat pembayaran | `create_laundry_tables.php` |
| Kenapa `qty` di JSON berupa string? | Cast `decimal:2` menjaga presisi | `app/Models/OrderItem.php` |

---

## Cara Menambah Fitur Baru

Urutan yang menjaga pola tetap konsisten:

1. Tambah kolom lewat migration baru, jangan ubah migration lama yang sudah dijalankan.
2. Sesuaikan `$fillable` dan `casts()` di model.
3. Taruh aturan validasi di Form Request, bukan di controller.
4. Taruh aturan bisnis yang menyentuh uang atau status di Service.
5. Controller hanya memanggil service dan menentukan response.
6. Kalau ada endpoint API, buat Form Request API yang mewarisi versi web.
7. Tambah tes di `tests/Feature`.
8. Perbarui `README.md` dan `API.md` bila perilaku berubah.
