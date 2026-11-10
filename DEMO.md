# Skenario Demo UAS - Laundry Kilat

Dokumen ini berisi urutan demo aplikasi **Laundry Kilat** untuk UAS Workshop Pemrograman Framework.
Ikuti langkahnya dari atas ke bawah. Semua langkah dilakukan langsung di browser dan Postman, bukan lewat slide.

Repositori: <https://github.com/fazaradit/laundry-kilat>

---

## Persiapan sebelum demo

### 1. Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- MySQL (XAMPP/Laragon) dengan database `laundry_kilat`
- Browser dan Postman sudah terbuka

### 2. Reset database ke kondisi demo

```bash
php artisan migrate:fresh --seed
```

Perintah ini menghapus seluruh tabel lalu mengisinya kembali dengan data dummy dari Seeder,
sehingga angka yang tampil saat demo selalu konsisten:

| Data | Jumlah |
|---|---|
| Pelanggan | 12 |
| Layanan | 5 |
| Pesanan | 15 |
| Pembayaran | 8 |

Komposisi pesanan: 4 `diterima`, 4 `diproses`, 5 `selesai`, 2 `dibatalkan`.

Dua hal sengaja ditanam di data demo supaya ada bahan penjelasan:

- **Satu pesanan `selesai` masih berstatus DP** (`LDR-20261108-0001`, total Rp 40.000, baru dibayar Rp 15.000).
  Dipakai untuk menunjukkan bahwa status pengerjaan dan status pelunasan adalah dua hal terpisah.
- **Satu pesanan `diterima` sudah DP hari ini** (`LDR-20261110-0002`, DP Rp 15.000).
  Ini satu-satunya sumber angka "Pendapatan hari ini" di Dashboard, supaya demo tidak dibuka dengan nol.

### 3. Jalankan dua server

Skenario ini membutuhkan **dua terminal** yang berjalan bersamaan.

Terminal 1, server utama untuk trafik browser:

```bash
php artisan serve --port=8000
```

Terminal 2, server khusus panggilan API internal:

```bash
php artisan serve --port=8011
```

Keduanya harus menampilkan tulisan `Server running on [http://127.0.0.1:xxxx]`.

**Kenapa perlu dua server?**
`php artisan serve` memproses satu request dalam satu waktu. Halaman Dashboard dan Laporan
memanggil API aplikasi sendiri lewat `Http::get()`. Kalau server hanya satu, server sedang sibuk
memproses request halaman sambil menunggu balasan dari request API yang tidak akan pernah diterima.
Halaman akan menggantung sampai timeout. Dengan port kedua, request API ditangani proses PHP lain
yang membaca database yang sama.

Pastikan `.env` berisi:

```env
APP_URL=http://127.0.0.1:8000
INTERNAL_API_URL=http://127.0.0.1:8011
```

### 4. Akun untuk login

| Role | Nama | Email | Password |
|---|---|---|---|
| Admin | Bambang Sutrisno | `admin@laundry.test` | `password` |
| Kasir | Siti Rahmawati | `kasir@laundry.test` | `password` |

Hafalkan kredensial ini supaya saat demo tidak perlu membuka database.

### 5. Beda hak akses Admin dan Kasir

Hafalkan tabel ini karena sering ditanyakan.

| Kemampuan | Kasir | Admin |
|---|---|---|
| Dashboard, Pelanggan, Pesanan, Pembayaran, Struk, Laporan | bisa | bisa |
| Ubah status `diterima → diproses → selesai` | bisa | bisa |
| Kelola master layanan (tambah, ubah harga, hapus, nonaktifkan) | **tidak bisa** | bisa |
| Batalkan pesanan | **tidak bisa** | bisa |

Alasannya: kasir menjalankan operasi harian, admin mengatur hal yang menyangkut uang dan kebijakan toko.
Harga layanan adalah kebijakan, bukan keputusan meja kasir. Pembatalan menghapus pesanan dari hitungan
kerja dan menyangkut uang yang sudah masuk, jadi butuh wewenang lebih tinggi.

---

## Urutan demo

### Langkah 1 - Login sebagai admin

1. Buka `http://127.0.0.1:8000/login`.
2. Masuk dengan `admin@laundry.test` / `password`.
3. Setelah berhasil, perhatikan:
   - redirect diarahkan ke halaman Dashboard (`/`), bukan ke `/orders`
   - muncul flash message "Login berhasil, selamat datang Bambang Sutrisno."
   - navbar menampilkan `Bambang Sutrisno` beserta chip role `admin`
   - menu **Layanan** muncul karena role-nya admin

**Yang dijelaskan:** redirect ke Dashboard berasal dari `AuthController@login` yang memakai
`route('dashboard')`. Menu Layanan dibungkus `@if (auth()->user()->isAdmin())` di
`partials/navbar.blade.php`, jadi menu itu tidak dirender untuk kasir.

### Langkah 2 - Tampilkan dashboard statistik

1. Pastikan berada di halaman Dashboard.
2. Tunjukkan angka berikut:

| Metrik | Nilai |
|---|---|
| Pendapatan hari ini | Rp 15.000 |
| Pesanan hari ini | 4 |
| Pesanan aktif | 8 |
| Total pelanggan | 12 |

**Yang dijelaskan:** angka ini tidak diambil dengan query Eloquent langsung. `DashboardController@index`
memanggil `Http::get()` ke `GET /api/stats`, lalu membaca hasil `json()` dan mengirimkannya ke
`dashboard.blade.php`. Kalau server port 8011 dimatikan, halaman tetap tampil dengan angka 0 karena
ada `try/catch ConnectionException`. Ini bisa didemokan sebagai bukti penanganan error.

### Langkah 3 - Tambah layanan baru, lalu buktikan kasir tidak bisa membukanya

1. Buka menu **Layanan** (atau `http://127.0.0.1:8000/services`).
2. Klik **Tambah Layanan**.
3. Isi:
   - Nama Layanan: `Cuci Express`
   - Satuan: `kg`
   - Harga: `12000`
   - Estimasi Hari: `1`
   - Status: `Aktif`
4. Klik **Simpan**.

**Hasil yang ditunjukkan:** kembali ke daftar layanan, muncul flash message
`Layanan "Cuci Express" berhasil ditambahkan.`, dan baris baru terlihat di tabel.

**Bukti pembatasan role:** buka jendela incognito, login sebagai `kasir@laundry.test`, lalu ketik
`http://127.0.0.1:8000/services` di address bar. Hasilnya halaman **403** dengan pesan
"Halaman ini hanya dapat diakses oleh Admin." Perhatikan juga bahwa menu Layanan tidak ada di navbar kasir.

**Yang dijelaskan:** route `services` dibungkus middleware `admin` (`CheckAdminRole`). Penjagaan ada di
server, bukan hanya menyembunyikan tombol, karena URL bisa diketik manual.

### Langkah 4 - Tambah pelanggan baru

1. Buka menu **Pelanggan** (atau `http://127.0.0.1:8000/customers`).
2. Klik **Tambah Pelanggan**.
3. Isi:
   - Nama: `Ayu Lestari`
   - Nomor Telepon: `081234567890`
   - Alamat: `Surabaya`
4. Klik **Simpan**.

**Hasil yang ditunjukkan:** pelanggan baru tampil di daftar dengan flash message sukses.

**Bukti validasi:** klik **Tambah Pelanggan** lagi, isi nomor telepon `12345` lalu simpan. Form kembali
muncul dengan pesan `Nomor telepon harus diawali 0 dan terdiri dari 9-14 digit angka.`
Ini membuktikan validasi `regex` di `StoreCustomerRequest` bekerja di sisi server, bukan hanya
atribut HTML.

### Langkah 5 - Buat pesanan multi item

1. Buka menu **Pesanan** (atau `http://127.0.0.1:8000/orders`), klik **Pesanan Baru**.
2. Pilih pelanggan `Ayu Lestari`.
3. Isi baris pertama: `Cuci Kering`, jumlah `3.5`.
4. Klik **+ Tambah baris**, isi baris kedua: `Setrika`, jumlah `3.5`.
5. Isi Diskon: `2000`.
6. Perhatikan ringkasan di layar berubah otomatis:

| Bagian | Nilai |
|---|---|
| Subtotal | Rp 42.000 |
| Diskon | Rp 2.000 |
| Total | Rp 40.000 |
| Estimasi selesai | 2 hari setelah hari ini |

7. Klik **Simpan Pesanan**.

**Hasil yang ditunjukkan:** kembali ke halaman detail dengan flash message
`Pesanan LDR-XXXXXXXX-0005 berhasil dibuat.`, status badge `Diterima`, dan badge pelunasan
`Belum Bayar`.

**Yang dijelaskan:**

- Ringkasan di layar hanya perkiraan. Angka yang disimpan adalah hasil hitung ulang
  `OrderService::buatPesanan()` di server. Kalau ada yang mengubah nilai total lewat DevTools,
  nilai itu diabaikan karena `StoreOrderRequest` tidak menerima input `total`.
- `kode_order` dibuat otomatis berformat `LDR-YYYYMMDD-NNNN`, unik per hari.
- Estimasi selesai diambil dari `estimasi_hari` terbesar di antara layanan yang dipesan.
- Petugas terisi otomatis dari sesi login, tidak ada dropdown petugas di form.

### Langkah 6 - Catat DP tunai dan tunjukkan kembalian

1. Pada halaman detail pesanan, scroll ke kartu **Pembayaran**.
2. Isi form:
   - Metode: `Tunai`
   - Jumlah Bayar: `20000`
   - Uang Diterima: `50000`
3. Perhatikan hint berubah menjadi **Kembalian Rp 30.000** sebelum tombol ditekan.
4. Klik **Catat Pembayaran**.

**Hasil yang ditunjukkan:**

- flash message `Pembayaran Rp 20.000 tercatat. Kembalian Rp 30.000.`
- badge pelunasan berubah dari `Belum Bayar` menjadi `DP`
- baris riwayat pembayaran menampilkan jumlah, uang diterima, dan kembalian
- keterangan `Sisa tagihan Rp 20.000.` di bawah kolom jumlah bayar

**Yang dijelaskan:** ada dua angka berbeda yang sengaja dipisah.

- `jumlah_bayar` adalah nominal yang dialokasikan ke tagihan, nilainya tidak pernah melebihi sisa.
- `uang_diterima` adalah uang fisik yang diserahkan pelanggan, hanya untuk tunai.

Kembalian dihitung server dari selisih keduanya. Pratinjau di layar hanya kenyamanan, angka yang
tersimpan tetap hasil hitung `PaymentService::catatPembayaran()`. Untuk transfer dan QRIS, kolom
Uang Diterima disembunyikan dan tidak dikirim.

**Bukti validasi uang:** isi lagi dengan Jumlah Bayar `5000` dan Uang Diterima `3000`, lalu simpan.
Muncul pesan `Uang diterima kurang dari jumlah bayar (Rp 5.000).` Pembayaran tidak tercatat.

### Langkah 7 - Buktikan status tidak bisa melompat

Pesanan masih berstatus `Diterima`. Form hanya menyediakan satu tombol, jadi tombol tidak bisa dipakai
untuk melompat. Kita akan memaksa lewat DevTools.

1. Tekan `F12`, buka tab **Elements**.
2. Cari input tersembunyi berikut di dalam form status:

```html
<input type="hidden" name="status" value="diproses">
```

3. Ubah `value="diproses"` menjadi `value="selesai"` lewat DevTools.
4. Klik tombol **Proses ke ...**.

**Hasil yang ditunjukkan:** muncul pesan error
`Status tidak boleh berpindah dari Diterima ke Selesai.` dan status pesanan **tidak berubah**.

**Yang dijelaskan:** tombol di layar memang hanya mengirim tujuan berikutnya, tetapi keputusan sah
atau tidaknya ada di `OrderService::ubahStatus()`. Ini membuktikan penjagaan tidak bergantung pada
HTML. Kalau dicek lewat Postman dengan `status=selesai`, hasilnya sama, ditolak.

### Langkah 8 - Majukan status ke Diproses

1. Klik tombol **Proses ke Diproses**.
2. Perhatikan flash message `Status pesanan diubah menjadi Diproses.`
3. Scroll ke kartu **Riwayat Status**.

**Hasil yang ditunjukkan:** badge berubah menjadi `Diproses`, dan riwayat status bertambah satu baris
berisi status lama, status baru, pelaku, dan waktu. Catatan opsional bisa diisi, misalnya
`sudah selesai diproses`.

**Yang dijelaskan:** perpindahan status dan penulisan log terjadi di dalam satu `DB::transaction()`.
Kalau penulisan log gagal, perubahan status ikut dibatalkan, jadi tidak mungkin ada status berubah
tanpa jejak.

### Langkah 9 - Admin membatalkan pesanan lain

1. Buka menu **Pesanan**, pilih satu pesanan berstatus `Diterima` yang **bukan** pesanan dari langkah 5.
   Contohnya `LDR-20261110-0004`.
2. Scroll ke kartu **Status Pesanan**.
3. Klik **Batalkan Pesanan**, lalu konfirmasi dialog.

**Hasil yang ditunjukkan:** flash message `Pesanan LDR-20261110-0004 dibatalkan.`, badge menjadi
`Dibatalkan`, dan tombol ubah status hilang karena statusnya sudah final.

**Yang dijelaskan:** pembatalan dijaga dua lapis. Route-nya ada di grup `admin`, dan
`OrderService::ubahStatus()` juga memeriksa `isAdmin()` sebelum mengizinkan tujuan `dibatalkan`.
Tombolnya pun tidak dirender untuk kasir. Kalau kasir memaksa lewat Postman, hasilnya **403**.
Pembatalan hanya boleh dari `diterima` atau `diproses`, tidak dari `selesai`.

### Langkah 10 - Pelunasan sisa tagihan

1. Buka kembali pesanan dari langkah 5.
2. Di kartu Pembayaran, isi:
   - Metode: `Tunai`
   - Jumlah Bayar: `20000` (otomatis terisi sebesar sisa)
   - Uang Diterima: `20000`
3. Klik **Catat Pembayaran**.

**Hasil yang ditunjukkan:**

- flash message `Pembayaran Rp 20.000 tercatat.` tanpa keterangan kembalian, karena kembaliannya nol
- badge pelunasan berubah menjadi `Lunas`
- **form pembayaran hilang**, diganti keterangan `Tagihan pesanan ini sudah lunas.`
- sisa menjadi Rp 0

**Yang dijelaskan:** status pelunasan bukan kolom tersimpan. Nilainya diturunkan dari
`SUM(payments.jumlah_bayar)` setiap kali dibaca. Karena itu tidak mungkin ada status `Lunas` yang
tidak sesuai dengan jumlah pembayaran sebenarnya.

**Bukti penolakan:** kalau dipaksa membayar lagi lewat Postman dengan nominal berapa pun, hasilnya
ditolak dengan pesan `Pesanan ini sudah lunas.`

### Langkah 11 - Cetak struk dan buktikan harga snapshot

1. Klik tombol **Struk** di halaman detail pesanan.
2. Perhatikan isi struk: kode, tanggal masuk, estimasi, pelanggan, kasir, status, daftar item,
   subtotal, diskon, total, dibayar, sisa, serta baris **Uang diterima** dan **Kembalian**.
3. Tekan **Cetak** untuk membuka dialog print browser. Tunjukkan bahwa navbar tidak ikut tercetak.

**Yang dijelaskan:** struk memakai `layouts/print.blade.php` yang terpisah dari layout aplikasi.
Tidak ada navbar dan tidak ada token warna dashboard, hanya tipografi dan angka tabular.
Aturan `@media print` menyembunyikan tombol sehingga yang tercetak hanya kertas struknya.

**Bukti harga snapshot:**

1. Buka menu **Layanan**, ubah harga `Cuci Kering` dari `7000` menjadi `20000`, simpan.
2. Buka lagi struk pesanan dari langkah 5.
3. Harga di struk **tetap Rp 7.000**, bukan Rp 20.000.

Alasannya, `order_items.harga_satuan` menyimpan salinan harga saat transaksi. Mengubah master layanan
tidak boleh mengubah nota lama. Setelah selesai, kembalikan harga `Cuci Kering` ke `7000`.

### Langkah 12 - Tampilkan halaman laporan

1. Buka menu **Laporan** (atau `http://127.0.0.1:8000/orders/report`).
2. Isi filter **Dari** dengan tanggal 7 hari lalu dan **Sampai** dengan hari ini, klik **Terapkan**.
3. Tunjukkan dua angka utama:

| Angka | Nilai setelah demo |
|---|---|
| Nilai pesanan periode ini | Rp 532.000 |
| Uang diterima | Rp 265.000 |
| Jumlah pesanan | 16 |
| Belum tertagih | Rp 267.000 |

4. Tunjukkan tabel berisi 15 baris dan navigasi halaman di bawah tabel, karena sekarang ada 16 pesanan
   dan `per_page` bernilai 15.
5. Klik halaman **2**. Tabel menampilkan 1 baris (`LDR-20261104-0001`), sementara **angka di atas tetap
   Rp 532.000 dan Rp 265.000**.

**Yang dijelaskan:**

- Halaman ini tidak memakai query Eloquent langsung. `OrderController@report` memanggil
  `GET /api/orders` lewat `Http::get()`, lalu mengirim `data` dan `meta` hasil JSON ke view.
- Karena sumbernya array biasa, view memakai notasi array dan membangun paginator manual dari `meta`.
- Dua angka dipisah dengan sengaja. **Nilai pesanan** menjumlah `orders.total`, yaitu nilai transaksi
  yang masuk periode itu. **Uang diterima** menjumlah `payments.jumlah_bayar`, yaitu uang yang
  benar-benar masuk. Pesanan bulan ini bisa dibayar bulan depan, atau belum dibayar sama sekali.
- Kedua angka dihitung **sebelum** `paginate()`, jadi nilainya tidak berubah saat pindah halaman.
  Ini yang membedakannya dari sekadar menjumlah baris yang tampil.
- Pesanan yang dibatalkan tetap ikut terhitung di nilai pesanan, karena angka itu mengukur nilai
  transaksi yang masuk, bukan realisasi pendapatan. Realisasinya terlihat di angka uang diterima.

**Demo bonus:** matikan Terminal 2 (port 8011), lalu refresh halaman ini. Tabel tampil kosong dengan
teks "Tidak ada pesanan pada periode ini, atau server API internal sedang tidak aktif." dan bukan
error 500. Nyalakan lagi server tersebut setelah selesai.

### Langkah 13 - Demo API di Postman

Buka Postman dan siapkan request berikut. Header `Accept: application/json` wajib diisi, kalau tidak
Laravel membalas HTML dan validasi gagal berubah jadi `302`, bukan `422`.

> Semua contoh memakai port `8000` karena API tersedia di server utama. `8011` hanya khusus panggilan
> internal Dashboard dan Laporan.

**A. `GET http://127.0.0.1:8000/api/services`**

Tunjukkan:

- response JSON dengan struktur `data`
- setiap layanan punya `nama_layanan`, `satuan`, `harga`, `estimasi_hari`, `status`
- sekarang ada 6 layanan karena langkah 3 menambah `Cuci Express`
- filter opsional: `?status=aktif` atau `?status=nonaktif`

**Yang dijelaskan:** bentuk response diatur `ServiceResource`, bukan `response()->json($service)` mentah.
Resource menyeragamkan format dan hanya mengirim kolom yang boleh dilihat klien.

**B. `GET http://127.0.0.1:8000/api/orders`**

Tunjukkan:

- struktur `data`, `links`, dan `meta`
- setiap pesanan punya objek `customer`, `petugas`, dan array `items`
- `meta.total` bernilai 16, `meta.total_nilai` bernilai 532000, `meta.total_dibayar` bernilai 265000
- `meta.total_nilai` sama dengan angka di halaman Laporan, membuktikan keduanya satu sumber

**C. Filter yang bisa dicoba langsung**

| Request | Yang ditunjukkan |
|---|---|
| `?status=diproses` | hanya pesanan diproses |
| `?dari=2026-11-01&sampai=2026-11-30` | pesanan dalam rentang, dua tanggal ikut dihitung |
| `?status=entah` | status tak dikenal diabaikan, bukan error, supaya halaman laporan tidak pecah karena URL salah ketik |
| `?dari=2026-11-10&sampai=2026-11-01` | `422`, karena rentang terbalik |
| `?per_page=5` | 5 baris per halaman, `meta.total_nilai` tetap angka periode penuh |

**D. `GET http://127.0.0.1:8000/api/orders/{id}`**

Ambil id dari response sebelumnya. Tunjukkan detail satu pesanan beserta item dan harga satuannya.

**E. `GET http://127.0.0.1:8000/api/orders/99999`**

Tunjukkan `404` dengan body JSON berisi `message`. Jangan lupa header `Accept: application/json`,
kalau tidak Laravel membalas halaman HTML.

**F. `GET http://127.0.0.1:8000/api/stats`**

```json
{
    "pesanan_hari_ini": 4,
    "pesanan_aktif": 8,
    "pendapatan_hari_ini": 15000,
    "total_pelanggan": 13
}
```

Angka `total_pelanggan` menjadi 13 karena langkah 4 menambah satu pelanggan. Nilai `pesanan_hari_ini`
dan `pesanan_aktif` berubah setelah langkah 5 sampai 10 dijalankan. Bandingkan angka ini dengan kartu
di Dashboard untuk membuktikan keduanya membaca sumber yang sama.

**G. `POST http://127.0.0.1:8000/api/orders`**

Body raw JSON:

```json
{
    "user_id": 2,
    "customer_id": 1,
    "items": [{ "service_id": 1, "qty": 2 }],
    "catatan": "Dibuat lewat Postman"
}
```

Hasilnya `201` dengan `kode_order` baru dan `total` 14000 (2 kg × 7000). **Yang dijelaskan:** body
tidak berisi `total` maupun `harga_satuan`, tetapi angkanya tetap benar karena dihitung `OrderService`.
Coba kirim `"total": 1` di body, hasilnya tetap 14000. Ini bukti angka uang tidak pernah dipercaya
dari klien.

`user_id: 2` adalah kasir. Karena API tidak punya sesi, pelaku dikirim eksplisit. Sebutkan juga
konsekuensinya: siapa pun yang bisa memanggil API dapat mengaku sebagai user lain, jadi API ini tidak
untuk dipublikasikan tanpa autentikasi.

**H. `PUT http://127.0.0.1:8000/api/orders/{id}/status`**

Body: `{"user_id": 1, "status": "selesai"}` untuk pesanan baru tadi yang masih `diterima`.
Hasilnya `422`:

```json
{
    "message": "Status tidak boleh berpindah dari Diterima ke Selesai.",
    "errors": { "status": ["Status tidak boleh berpindah dari Diterima ke Selesai."] }
}
```

Ganti jadi `{"user_id": 2, "status": "diproses"}`, hasilnya `200` dan `status` berubah. **Yang
dijelaskan:** state machine tidak hilang hanya karena pemanggilnya API. Aturan yang sama dipakai form
web, karena keduanya memanggil `OrderService::ubahStatus()`.

**I. `PUT .../status` dengan pembatalan oleh kasir**

Body: `{"user_id": 2, "status": "dibatalkan"}`. Hasilnya `403`:

```json
{ "message": "Pembatalan pesanan hanya dapat dilakukan oleh Admin." }
```

Ganti `user_id` menjadi `1` (admin), hasilnya `200`. Ini pembuktian paling kuat bahwa pembatasan role
ada di server, bukan di tombol yang disembunyikan.

**J. `POST http://127.0.0.1:8000/api/orders/{id}/payments`**

Body: `{"user_id": 2, "metode": "tunai", "jumlah_bayar": 10000, "uang_diterima": 20000}`.
Hasilnya `201` dengan `kembalian` 10000 dan `order.sisa_bayar` berkurang.

Lalu ulangi dengan `"jumlah_bayar": 999999`. Hasilnya `422` karena melebihi sisa tagihan.
**Yang dijelaskan:** `jumlah_bayar` dan `uang_diterima` dua hal berbeda. Yang pertama mengurangi
tagihan, yang kedua uang fisik. Kembalian dihitung server dari selisihnya.

**K. `DELETE http://127.0.0.1:8000/api/services/{id}`**

Hapus layanan yang baru dibuat di langkah 3 (`Cuci Express`) hanya kalau belum dipakai pesanan,
hasilnya `200`.

Coba hapus `Cuci Kering` (id `1`). Hasilnya `422` karena layanan itu sudah dipakai puluhan pesanan.
**Yang dijelaskan:** layanan yang punya riwayat tidak boleh dihapus, harus dinonaktifkan, supaya
snapshot harga di nota lama tetap utuh. Pesanan sendiri tidak punya endpoint hapus, karena
`cascadeOnDelete` akan ikut menghapus riwayat pembayaran.

Bentuk request dan response lengkap dari kedelapan endpoint ada di [`API.md`](./API.md).

### Langkah 14 - Tanya jawab konseptual

Dosen memilih beberapa pertanyaan. Ringkasan poin jawaban yang perlu disiapkan:

| No | Pertanyaan | Poin jawaban |
|---|---|---|
| 1 | Masalah kalau tanpa framework | Kode tersebar, tidak ada pemisahan tanggung jawab, perubahan kecil menyentuh banyak file. MVC memisahkan data, tampilan, dan alur. |
| 2 | Alur request saat menyimpan pesanan | Browser kirim POST, route mencocokkan ke `OrderController@store`, middleware `auth` memeriksa sesi, `StoreOrderRequest` memvalidasi, `OrderService` menghitung total di dalam `DB::transaction()`, lalu redirect ke halaman detail. |
| 3 | Kenapa validasi tetap di server | Atribut HTML dan JavaScript bisa dilewati lewat Postman atau DevTools. Langkah 7 dan 11 membuktikan ini langsung. Server adalah batas kepercayaan terakhir. |
| 4 | Kenapa total dihitung server, bukan dikirim dari form | Angka uang tidak boleh berasal dari input pengguna. Kalau klien mengirim total, harga bisa diubah lewat DevTools. Server menghitung dari `harga_satuan` dan `qty`. |
| 5 | Kenapa harga disimpan snapshot di `order_items` | Supaya nota lama tidak berubah saat admin menaikkan harga master. Ditunjukkan di langkah 11. Ini juga menjaga laporan historis tetap akurat. |
| 6 | Kenapa status pelunasan tidak disimpan sebagai kolom | Kolom tersimpan bisa tidak sinkron dengan tabel pembayaran. Dengan accessor, nilainya selalu diturunkan dari `SUM(payments.jumlah_bayar)`, jadi tidak mungkin berbohong. |
| 7 | Kenapa `jumlah_bayar` dan `uang_diterima` dipisah | Dua makna berbeda. Yang pertama dialokasikan ke tagihan dan dibatasi sisa. Yang kedua uang fisik yang diserahkan, bisa lebih besar. Kembalian lahir dari selisihnya. Kalau digabung, tidak bisa mencetak nota seperti aslinya. |
| 8 | Kenapa laporan menampilkan dua angka, bukan satu "pendapatan" | Satu angka menjadi ambigu. Nilai pesanan mengukur transaksi yang masuk periode itu, uang diterima mengukur realisasi kas. Pesanan bulan ini bisa dibayar bulan depan, atau belum dibayar. |
| 9 | Kenapa total periode dihitung sebelum `paginate` | Kalau dijumlah setelah paginate, angkanya hanya mewakili satu halaman dan berubah saat pindah halaman. Langkah 12 membuktikan angkanya tetap. |
| 10 | Kenapa `PaymentService` memakai `lockForUpdate` | Dua kasir bisa menekan tombol hampir bersamaan. Tanpa kunci, keduanya membaca sisa yang sama dan tagihan bisa terlunasi dua kali. Baris pesanan dikunci selama transaksi. |
| 11 | Kenapa ada pesanan selesai tapi masih DP | Status pengerjaan dan status pelunasan independen. Cucian bisa selesai dikerjakan sementara pelanggan mengambil dan melunasi nanti. Memaksa keduanya selalu sama justru menyembunyikan kenyataan operasional. |
| 12 | Migration vs kelola tabel manual | Struktur tabel jadi versi terkontrol di Git, bisa dijalankan ulang di mesin lain, dan perubahan schema tercatat urut. |
| 13 | N+1 Query Problem | Menampilkan 16 pesanan tanpa eager loading memicu satu query per relasi per baris. `with([...])` dan `withSum()` menggabungkannya jadi beberapa query tetap. |
| 14 | Relasi Model vs foreign key | Foreign key menjaga integritas di level database. Relasi Eloquent memberi cara memanggil data terkait dari kode. Keduanya berbeda lapisan. |
| 15 | Autentikasi vs otorisasi | Autentikasi memastikan siapa penggunanya, ditangani `AuthController` dan middleware `auth`. Otorisasi membatasi apa yang boleh dilakukan, ditangani `CheckAdminRole` dan pemeriksaan di `OrderService`. |
| 16 | Kenapa API mengembalikan JSON | Supaya klien non-browser (mobile, dashboard lain) bisa memakai data yang sama. HTML hanya berguna untuk browser. |
| 17 | Fungsi API Resource | Menyeragamkan bentuk response, menyembunyikan kolom sensitif, dan memisahkan cara penyajian dari Model. |
| 18 | Kenapa butuh dua server | `php artisan serve` satu request per waktu. Memanggil diri sendiri dari dalam request berjalan menyebabkan deadlock. Di produksi dengan Nginx dan PHP-FPM masalah ini tidak muncul. |
| 19 | Seeder vs Factory | Seeder mengisi data spesifik yang jumlahnya sedikit dan tetap. Factory menghasilkan banyak data acak lewat Faker. Dipisah karena tanggung jawabnya berbeda. |
| 20 | Kenapa dua branch | `dev` untuk pengembangan berjalan, `main` untuk kondisi stabil. Commit checkpoint per pertemuan membuat riwayat mudah ditelusuri. |

---

## Penutup demo

Sebelum meninggalkan sesi:

1. Pastikan kedua terminal server masih berjalan kalau dosen ingin mencoba sendiri.
2. Kembalikan harga `Cuci Kering` ke `7000` kalau sempat diubah di langkah 11.
3. Siapkan tautan repository GitHub yang bisa diakses publik.
4. Kalau ada langkah yang gagal, cek bagian troubleshooting di bawah sebelum menyimpulkan fitur tidak berfungsi.

---

## Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Dashboard menampilkan 0 semua | Server port 8011 tidak jalan | Nyalakan terminal kedua dengan `php artisan serve --port=8011` |
| Laporan kosong | Sama seperti di atas | Nyalakan server port 8011 |
| Halaman menggantung lama lalu gagal | `INTERNAL_API_URL` menunjuk ke port yang sama dengan server utama | Pastikan `.env` berisi `INTERNAL_API_URL=http://127.0.0.1:8011` lalu jalankan `php artisan config:clear` |
| Akses `/services` dapat 403 | Login sebagai kasir, bukan admin | Login ulang dengan `admin@laundry.test` |
| Menu Layanan tidak muncul | Sama seperti di atas | Role user di navbar harus `admin` |
| Laporan tidak punya halaman 2 | Data masih 15 pesanan, `per_page` bernilai 15 | Selesaikan langkah 5, pesanan ke-16 baru memunculkan pagination |
| Angka laporan berbeda dari dokumen ini | Database berisi sisa data uji coba | Jalankan `php artisan migrate:fresh --seed` lalu login ulang |
| Data dummy berbahasa Inggris | `APP_FAKER_LOCALE` masih `en_US` | Ubah ke `id_ID` di `.env`, lalu `php artisan config:clear` dan seed ulang |
| Error "Access is denied" saat `composer` atau `artisan` | File cache sementara terkunci | Hapus `bootstrap/cache/pac*.tmp` lalu jalankan `php artisan package:discover` |
