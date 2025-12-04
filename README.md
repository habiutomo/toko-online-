# 🛍️ Sistem Toko Online Sederhana — Backend Laravel

Proyek ini adalah implementasi **backend untuk sistem toko online sederhana** dengan fokus pada:

- Pemisahan peran pengguna (Admin, Pembeli, CS Layer 1 & CS Layer 2)
- Manajemen stok yang **transactional & atomic**
- Alur kerja **manual payment verification oleh Customer Service**
- **Pembatalan otomatis** pesanan lewat Cron Job

---

## 🚀 Fitur Utama Berdasarkan Peran

| Peran         | Fitur Utama | Logika Bisnis |
|--------------|-------------|---------------|
| **Admin**     | CRUD Produk (Input tunggal), Impor massal via Excel | Menangani produk & stok master |
| **Pembeli**   | Lihat produk, keranjang, checkout, unggah bukti pembayaran | Pemantauan status pesanan |
| **CS Layer 1**| Antrian verifikasi pembayaran | Konfirmasi pembayaran → **stok dikurangi secara atomic** & diteruskan ke CS L2 |
| **CS Layer 2**| Pemrosesan pesanan, input nomor resi, update status pengiriman | Fulfillment pesanan (stok telah terpotong) |
| **Sistem**   | Pembatalan otomatis via Cron | Membatalkan pesanan belum dibayar / diverifikasi **1×24 jam** |

---




---

## 💾 Panduan Deployment

### 1️⃣ Prasyarat
- PHP **8.2+**
- Composer
- PostgreSQL atau MariaDB/MySQL
- Apache / Nginx atau `php artisan serve`

### 2️⃣ Setup Database

#### A. PostgreSQL (Direkomendasikan)
```sql
CREATE SCHEMA master;
CREATE SCHEMA transactions;
-- Jalankan script CREATE TABLE sesuai skema
B. Konfigurasi .env
Variabel	Contoh	Keterangan
DB_CONNECTION	pgsql	Atau mysql
DB_HOST	127.0.0.1	—
DB_PORT	5432	3306 untuk MySQL
DB_DATABASE	ecommerce_db	—
DB_USERNAME	postgres	—
DB_PASSWORD	your_secret_password	—

3️⃣ Instal Dependencies
bash
Salin kode
composer install
php artisan key:generate
# php artisan migrate   # jika tidak menggunakan SQL manual
composer require maatwebsite/excel   # Opsional
4️⃣ Setup Cron Job (WAJIB)
Tambahkan scheduler Laravel agar pembatalan otomatis berjalan:

bash
Salin kode
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
🧑‍💻 Panduan Pengguna
Akun Pengujian
Role	Email	Password
Admin	admin@toko.com	password_hash_admin
CS Layer 1	cs1@toko.com	password_hash_cs1
CS Layer 2	cs2@toko.com	password_hash_cs2
Pembeli	pembeli@toko.com	password_hash_pembeli

🔁 Alur Kerja Transaksi & Stok (Penting!)
Pembeli checkout → status: pending_payment (stok belum berkurang)

Pembeli upload bukti pembayaran → status: waiting_verification (stok belum berkurang)

CS Layer 1 klik Konfirmasi Pembayaran
→ OrderService::confirmPayment() dipicu
→ stok berkurang transactional & atomic
→ status: processed → diteruskan ke CS Layer 2

CS Layer 2 memproses pesanan & input resi → status: shipped

Pembatalan otomatis jika pending_payment / waiting_verification > 24 jam
→ status: cancelled
→ stok tidak perlu dikembalikan

Jika pembatalan oleh CS setelah status processed
→ OrderService::cancelOrder()
→ stok dikembalikan ke master.products

📌 Catatan Tambahan
Sistem ini tidak mengurangi stok saat checkout, hanya setelah pembayaran diverifikasi oleh CS L1.

Arsitektur service menjamin idempotensi & keamanan race condition saat update stok.

📄 Lisensi
MIT License — Bebas digunakan & dikembangkan.

⭐ Suka proyek ini?
Silakan diberi star di GitHub agar makin banyak developer terbantu 😊

yaml
Salin kode

---

Jika ingin, saya juga bisa:

🔹 Buatkan **diagram alur transaksi / ERD**  
🔹 Buatkan **postman collection**  
🔹 Buatkan **API documentation (Swagger / Slate / HTML markdown)**

Cukup bilang **"lanjutkan"** 🚀
