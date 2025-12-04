🛍️ Sistem Toko Online Sederhana - Backend LaravelProyek ini adalah implementasi backend untuk sistem toko online sederhana dengan fokus pada pemisahan peran dan pengelolaan stok yang transactional, serta alur kerja verifikasi pembayaran manual oleh Customer Service (CS).🚀 Fitur Utama Berdasarkan PeranPeranFitur KunciLogika BisnisAdminCRUD Produk (Input Tunggal), Impor Massal Produk via Excel.Mengelola data produk dan stok master.PembeliMelihat Produk, Keranjang Belanja, Checkout, Unggah Bukti Pembayaran.Pemantauan status pesanan.CS Layer 1Antrian Verifikasi Pembayaran, Konfirmasi Pembayaran.Mengurangi stok secara atomic setelah verifikasi berhasil. Meneruskan ke CS L2.CS Layer 2Antrian Pemrosesan Pesanan, Mencatat Nomor Resi, Memperbarui Status Pengiriman.Memastikan fulfillment pesanan yang stoknya sudah terpotong.SistemPembatalan Otomatis (Cron Job).Membatalkan pesanan yang belum dibayar/diverifikasi setelah 1x24 jam dan mengembalikan stok (jika status sudah processed sebelum dibatalkan).⚙️ Struktur Folder AplikasiProyek ini menggunakan pemisahan controller dan service yang ketat:app/
├── Console/
│   └── Commands/
│       └── CancelPendingOrders.php   # Command untuk Pembatalan Otomatis 1x24 jam
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                  # Controllers untuk Admin (CRUD/Import)
│   │   ├── CustomerService/        # Controllers untuk CS L1 & CS L2
│   │   └── Web/                    # Controllers untuk Pembeli (Shop, Cart, Order)
│   ├── Requests/
│   │   └── Admin/                  # Form Request untuk Validasi
│   └── Middleware/
│       └── RoleMiddleware.php      # Filter akses berdasarkan peran (admin, cs_l1, dll.)
├── Models/                         # Model Eloquent (terhubung ke skema master/transactions)
├── Services/
│   └── OrderService.php            # CORE LOGIC: Pengurangan Stok & Pembatalan Transaksi
└── Imports/
    └── ProductsImport.php          # Class untuk memproses impor Excel (Maatwebsite/Excel)
💾 Panduan Deployment1. PrasyaratPHP 8.2+ComposerPostgreSQL atau MariaDB/MySQLServer Web (Apache/Nginx) atau menggunakan php artisan serve2. Setup DatabaseProyek ini memerlukan pembuatan skema dan tabel di PostgreSQL.A. Skema PostgreSQL:Jika Anda menggunakan PostgreSQL, buat database dan jalankan script SQL yang telah disediakan:SQL-- Di database Anda
CREATE SCHEMA master;
CREATE SCHEMA transactions;
-- ... Jalankan skrip CREATE TABLE untuk setiap skema.
B. Konfigurasi .env:Salin file .env.example menjadi .env dan konfigurasikan koneksi database Anda.VariabelNilai Contoh (PostgreSQL)KeteranganDB_CONNECTIONpgsqlAtau mysql jika Anda menggunakan MariaDB/MySQLDB_HOST127.0.0.1DB_PORT5432Atau 3306 untuk MySQLDB_DATABASEecommerce_dbDB_USERNAMEpostgresDB_PASSWORDyour_secret_password3. Instalasi Laravel dan DependenciesJalankan perintah berikut di terminal proyek:Bash# Instal dependencies PHP
composer install

# Buat App Key
php artisan key:generate

# Jalankan Migrasi (untuk membuat tabel jika tidak menggunakan script SQL manual)
# Catatan: Migrasi bawaan Laravel mungkin tidak otomatis membuat skema master/transactions.
# Dianjurkan menggunakan script SQL yang disediakan.
# php artisan migrate

# [Opsional] Instal package Maatwebsite/Excel untuk Impor
composer require maatwebsite/excel
4. Setup Cron Job (Wajib untuk Pembatalan Otomatis)Untuk memastikan pesanan dibatalkan secara otomatis setelah 1x24 jam, Anda harus mengatur Cron Job sistem untuk menjalankan scheduler Laravel setiap menit:Bash# Tambahkan baris ini ke crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
🧑‍💻 Panduan Pengguna (User Guide)1. Akun Pengujian (Diasumsikan sudah di-seeded atau dimasukkan via SQL)RoleEmailPasswordAdminadmin@toko.compassword_hash_admin (Ganti dengan hash yang benar)CS Layer 1cs1@toko.compassword_hash_cs1CS Layer 2cs2@toko.compassword_hash_cs2Pembelipembeli@toko.compassword_hash_pembeli2. Alur Kerja Kritis (Transaksi dan Stok)Pembeli login dan melakukan Checkout.Status Order: pending_payment. Stok TIDAK berkurang.Pembeli mengunggah bukti pembayaran via formulir.Status Order: waiting_verification. Stok TIDAK berkurang.CS Layer 1 login dan memeriksa antrian verifikasi.CS L1 mengklik "Konfirmasi Pembayaran".Saat ini, fungsi OrderService::confirmPayment dipicu: Stok barang LANGSUNG DIKURANGI secara transactional.Status Order: processed. (Diteruskan ke CS L2).CS Layer 2 login dan melihat antrian processed.CS L2 memproses pesanan dan menginput nomor resi.Status Order: shipped.Gagal Bayar/Verifikasi: Jika pesanan tetap pending_payment atau waiting_verification selama 24 jam, Cron Job akan membatalkannya.Status Order: cancelled. Stok TIDAK perlu dikembalikan (karena belum terpotong).Pembatalan CS L1/L2 setelah diproses: Jika status sudah processed dan CS L1/L2 membatalkan, fungsi OrderService::cancelOrder akan mengembalikan stok ke master.products.
