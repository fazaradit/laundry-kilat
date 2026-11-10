# Laundry Kilat

Aplikasi manajemen operasional laundry berbasis **Laravel 12** — mencatat pesanan, melacak status pengerjaan, mencatat pembayaran bertahap (DP), dan menyajikan laporan harian.

Dibangun sebagai projek UAS mata kuliah Pemrograman Framework.

---

## Tabel Database (7 tabel)

| Tabel | Fungsi |
|---|---|
| `users` | Akun kasir & admin (yang login ke aplikasi) |
| `customers` | Data pelanggan laundry |
| `services` | Master layanan (cuci kering, setrika, dll.) |
| `orders` | Header transaksi pesanan |
| `order_items` | Detail layanan per pesanan (harga snapshot) |
| `payments` | Pembayaran, boleh bertahap (DP + pelunasan) |
| `order_status_logs` | Riwayat perpindahan status pesanan |

---

## Kebutuhan Sistem

| Software | Versi |
|---|---|
| PHP | >= 8.2 |
| Composer | 2.x |
| MySQL | 8.x |
| Node.js & NPM | 20+ (opsional, untuk build asset) |

---

## Cara Menjalankan

### 1. Clone & install dependency

```bash
git clone <url-repo> laundry-kilat
cd laundry-kilat
composer install
```

### 2. Siapkan file `.env`

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Buat database

```bash
mysql -u root -e "CREATE DATABASE laundry_kilat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` di `.env` bila perlu.

### 4. Migrasi & isi data demo

```bash
php artisan migrate:fresh --seed
```

### 5. Jalankan **dua** server

Dashboard dan halaman Laporan mengambil data lewat HTTP ke API internal aplikasi sendiri. Karena `php artisan serve` hanya memproses satu request dalam satu waktu, keduanya **wajib** dijalankan di port berbeda:

```bash
# Terminal 1 - trafik browser
php artisan serve --port=8000

# Terminal 2 - khusus panggilan API internal
php artisan serve --port=8011
```

Buka `http://127.0.0.1:8000`.

> Kalau hanya satu server yang jalan, Dashboard dan Laporan tetap tampil tetapi angkanya nol / tabel kosong (bukan error). Pastikan `INTERNAL_API_URL` di `.env` menunjuk ke port server kedua.

---

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin | `admin@laundry.test` | `password` |
| Kasir | `kasir@laundry.test` | `password` |

Perbedaan hak akses: menu **Layanan** hanya bisa dibuka Admin. Kasir yang mengakses langsung akan mendapat 403.

---

## Endpoint API

Base URL: `http://127.0.0.1:8011/api`

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/services` | Daftar layanan, bisa disaring `?status=aktif\|nonaktif` |
| GET | `/orders` | Daftar pesanan dengan pagination, bisa disaring `?status=&dari=&sampai=&per_page=` |
| GET | `/orders/{id}` | Detail satu pesanan beserta item |
| GET | `/stats` | Ringkasan jumlah pesanan & pendapatan hari ini |

`dari` dan `sampai` memakai format `Y-m-d` dan sama-sama inklusif. Respons `/orders` menyertakan `meta.total_nilai` dan `meta.total_dibayar` yang merupakan agregat seluruh periode filter, bukan hanya halaman yang sedang tampil.

Contoh:

```bash
curl "http://127.0.0.1:8011/api/orders?status=diproses&dari=2026-11-01&sampai=2026-11-30&per_page=5"
```

---

## Struktur Pengerjaan

| Fase | Cakupan | Status |
|---|---|---|
| **W0** | Scaffold Laravel 12, konfigurasi DB & `.env` | ✅ |
| **W1** | Migration, model, relasi, auth + middleware, CRUD layanan & pelanggan | ✅ |
| **W2** | Transaksi pesanan multi-item, perhitungan total, state machine status, `DB::transaction()` | ✅ |
| **W3** | Pembayaran bertahap + kembalian tunai, struk, REST API, Laporan | ✅ |
| **W4** | `DEMO.md`, finalisasi, merge `dev` → `main` | ⏳ |

---

## Aturan Bisnis Utama

1. Total pesanan **selalu dihitung server** dari item; input total dari client diabaikan.
2. `order_items.harga_satuan` adalah **snapshot** harga saat transaksi; mengubah harga master tidak mengubah struk lama.
3. Status pelunasan (Belum Bayar / DP / Lunas) **diturunkan** dari `SUM(payments.jumlah_bayar)`, bukan kolom tersimpan.
4. Status pesanan hanya boleh **maju satu tahap**: `diterima → diproses → selesai`.
5. Pesanan yang sudah `selesai` atau `dibatalkan` tidak bisa diubah lagi.
6. Pembatalan hanya boleh dilakukan Admin, dan hanya dari `diterima` atau `diproses`.
7. `payments.jumlah_bayar` adalah nominal yang dialokasikan ke tagihan dan tidak pernah melebihi sisa. Untuk tunai, `payments.uang_diterima` mencatat uang fisik yang diserahkan, dan `kembalian` dihitung server dari selisih keduanya.
