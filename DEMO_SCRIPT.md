# SCRIPT DEMO APLIKASI ZI.CARE POS
## Enterprise Point of Sales System

---

## 📋 INFORMASI UMUM

**Nama Aplikasi**: Zi.Care POS Application  
**Versi**: 1.0.0  
**Teknologi**: 
- Backend: Phalcon PHP 5.x (REST API, port 8090)
- Frontend: Vue.js 3, Vue Router, Pinia (Vite, port 5173)
- Database: MariaDB 10.6+
- ERP Integration: Odoo 19 (Accounting)

**Kredensial Default**:
| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@pos.local | Admin@123 |
| Manager | manager@pos.local | Manager@123 |
| Cashier | cashier@pos.local | Cashier@123 |

---

## 🎯 TUJUAN DEMO

Demo ini bertujuan untuk menampilkan seluruh fitur dan alur kerja aplikasi POS Zi.Care secara menyeluruh, mencakup:
1. Sistem autentikasi dan role-based access control
2. Manajemen produk dan kategori
3. Manajemen pelanggan
4. Sistem transaksi penjualan (Point of Sale)
5. Manajemen invoice dan pembayaran
6. Pembayaran tertunda (outstanding payments)
7. Laporan dan analisis penjualan
8. Manajemen pengguna
9. Audit log dan tracking aktivitas
10. Integrasi dengan Odoo Accounting

---

## 📝 BAGIAN 1: SISTEM AUTENTIKASI

### 1.1 Login sebagai Administrator
**Langkah-langkah**:
1. Buka browser dan akses `http://localhost:5173`
2. Halaman login akan muncul dengan desain modern
3. Masukkan email: `admin@pos.local`
4. Masukkan password: `Admin@123`
5. Klik tombol "Masuk" atau tekan Enter

**Fitur yang ditampilkan**:
- Validasi email format
- Validasi password minimal 8 karakter
- Indikator kekuatan password
- Efek typing pada tagline ("efisien", "cepat", "akurat", "mudah")
- Efek parallax mouse movement pada background
- Tombol show/hide password
- Pesan error jika kredensial salah

**Hasil yang diharapkan**:
- Redirect otomatis ke Dashboard
- Token JWT tersimpan di localStorage
- User data tersimpan di auth store
- Menu navigasi muncul sesuai role Administrator

### 1.2 Login sebagai Manager
**Langkah-langkah**:
1. Logout dari Administrator (klik avatar user → Logout)
2. Login dengan email: `manager@pos.local`
3. Password: `Manager@123`

**Akses Manager**:
- ✅ Dashboard
- ✅ Products
- ✅ Categories
- ✅ Customers
- ✅ Invoices
- ✅ Outstanding Payments
- ✅ Reports
- ✅ Audit Logs
- ✅ Integration
- ❌ Users (hanya Administrator)
- ❌ Transactions (hanya Administrator & Cashier)

### 1.3 Login sebagai Cashier
**Langkah-langkah**:
1. Logout dari Manager
2. Login dengan email: `cashier@pos.local`
3. Password: `Cashier@123`

**Akses Cashier**:
- ✅ Transactions (Page utama setelah login)
- ✅ Customers
- ✅ Invoices
- ✅ Outstanding Payments
- ❌ Dashboard (redirect ke Transactions)
- ❌ Products (redirect ke Transactions)
- ❌ Categories (redirect ke Transactions)
- ❌ Reports (redirect ke Transactions)
- ❌ Users (redirect ke Transactions)
- ❌ Audit Logs (redirect ke Transactions)
- ❌ Integration (redirect ke Transactions)

---

## 📦 BAGIAN 2: DASHBOARD (Administrator & Manager)

### 2.1 Overview Dashboard
**Akses**: Menu Dashboard

**Fitur yang ditampilkan**:
1. **Statistik Cards** (4 kartu):
   - Penjualan (Hari Ini / 7 Hari / 30 Hari)
   - Total Transaksi
   - Total Produk
   - Revenue Bulan Ini

2. **Date Range Filter**:
   - Hari Ini
   - 7 Hari Terakhir
   - 30 Hari Terakhir

3. **Revenue Chart**:
   - Line chart menampilkan tren pendapatan 30 hari terakhir
   - Tooltip saat hover pada titik data
   - Responsif terhadap perubahan date range

4. **Produk Terlaris**:
   - Tabel 5 produk dengan penjualan tertinggi
   - Kolom: Nama Produk, Qty Terjual, Revenue

5. **Statistik Pembayaran**:
   - Doughnut chart distribusi metode pembayaran
   - Tabel breakdown: Cash, QRIS, Transfer, E-Wallet
   - Legend interaktif

**Demo Scenario**:
1. Buka Dashboard
2. Ubah date range ke "7 Hari Terakhir"
3. Perhatikan chart dan statistik yang berubah
4. Hover pada revenue chart untuk melihat detail
5. Klik legend pada payment chart untuk toggle
6. Scroll ke tabel produk terlaris

---

## 🛍️ BAGIAN 3: MANAJEMEN PRODUK

### 3.1 Daftar Produk
**Akses**: Menu Products (Administrator & Manager)

**Fitur yang ditampilkan**:
1. **Tabel Produk** dengan kolom:
   - Kode Produk
   - Nama Produk
   - Tipe (Barang/Jasa)
   - Kategori
   - Harga
   - Stok/Status
   - Aksi (Edit, Hapus, Kartu Stok)

2. **Filter & Search**:
   - Search by nama atau kode produk
   - Filter by kategori

3. **Pagination**:
   - 20 item per halaman
   - Navigasi Prev/Next

4. **Badges**:
   - Barang: Primary badge
   - Jasa: Warning badge
   - Stok ≤ 10: Danger badge (alert stok rendah)

**Demo Scenario**:
1. Buka halaman Products
2. Perhatikan daftar produk yang ada
3. Cari produk dengan keyword "minuman"
4. Filter by kategori
5. Navigasi pagination jika data banyak

### 3.2 Tambah Produk Baru
**Langkah-langkah**:
1. Klik tombol "Tambah Produk"
2. Isi form:
   - Kode Produk: `PROD-001`
   - Nama Produk: `Es Kopi Susu Gula Aren`
   - Tipe Produk: Barang
   - Harga: `25000`
   - Stok: `50`
   - Kategori: Pilih kategori (misal: Minuman)
3. Klik "Tambah Produk"

**Validasi**:
- Semua field required terisi
- Harga harus angka ≥ 0
- Stok harus angka ≥ 0
- Kode produk harus unique

**Hasil**:
- Toast success: "Produk berhasil ditambahkan"
- Produk muncul di tabel
- Auto-refresh list

### 3.3 Tambah Produk Jasa
**Langkah-langkah**:
1. Klik "Tambah Produk"
2. Isi form:
   - Kode Produk: `SERV-001`
   - Nama Produk: `Servis AC`
   - Tipe Produk: Jasa
   - Harga: `150000`
   - Status Ketersediaan: Tersedia
   - Kategori: (opsional)
3. Klik "Tambah Produk"

**Perbedaan Barang vs Jasa**:
- Barang: Input stok numeric
- Jasa: Dropdown status (Tersedia/Tidak Tersedia)
- Jasa tidak memiliki kartu stok

### 3.4 Edit Produk
**Langkah-langkah**:
1. Klik tombol Edit (ikon pensil) pada produk
2. Ubah harga dari 25000 menjadi 28000
3. Klik "Simpan Perubahan"

**Hasil**:
- Toast success: "Produk berhasil diperbarui"
- Data di tabel terupdate

### 3.5 Hapus Produk
**Langkah-langkah**:
1. Klik tombol Hapus (ikon sampah) pada produk
2. Konfirmasi dialog: "Hapus produk ini?"
3. Klik "Ya, Hapus"

**Hasil**:
- Toast success: "Produk berhasil dihapus"
- Produk hilang dari tabel

### 3.6 Kartu Stok (Stock Card)
**Akses**: Klik tombol "Kartu Stok" pada produk bertipe Barang

**Fitur yang ditampilkan**:
1. **Info Produk**:
   - Nama, kode, tipe, kategori, harga
   - Stok saat ini dengan indikator warna (merah jika 0)

2. **Ringkasan Pergerakan**:
   - Total Masuk
   - Total Keluar
   - Jumlah Pergerakan

3. **Filter Pergerakan**:
   - Jenis: Penjualan, Masuk, Retur, Penyesuaian, Opname
   - Date range filter

4. **Tabel Pergerakan Stok**:
   - Waktu
   - Jenis (dengan badge warna)
   - Perubahan (+/-)
   - Stok sebelum → sesudah
   - Alasan
   - Referensi (No. Invoice/dokumen)
   - Oleh (user yang melakukan)

5. **Sesuaikan Stok**:
   - Metode Opname: Input stok fisik hasil hitung
   - Metode Delta: Input perubahan (+/-)
   - Alasan wajib diisi
   - Referensi opsional

**Demo Scenario**:
1. Buka kartu stok produk
2. Perhatikan ringkasan pergerakan
3. Filter pergerakan jenis "Penjualan"
4. Filter date range (seminggu terakhir)
5. Klik "Sesuaikan Stok"
6. Pilih metode "Stok Opname"
7. Input stok fisik: 45
8. Alasan: "Hasil stok opname harian"
9. Klik "Simpan Penyesuaian"
10. Perhatikan pergerakan baru muncul di tabel

---

## 📂 BAGIAN 4: MANAJEMEN KATEGORI

### 4.1 Daftar Kategori
**Akses**: Menu Categories (Administrator & Manager)

**Fitur yang ditampilkan**:
- Tabel dengan kolom: ID, Nama Kategori, Deskripsi, Aksi
- Tombol Tambah Kategori
- Tombol Edit dan Hapus per kategori

**Demo Scenario**:
1. Buka halaman Categories
2. Perhatikan kategori yang sudah ada

### 4.2 Tambah Kategori
**Langkah-langkah**:
1. Klik "Tambah Kategori"
2. Isi form:
   - Nama: `Snack`
   - Deskripsi: `Makanan ringan dan snack`
3. Klik "Simpan"

**Hasil**:
- Toast success
- Kategori baru muncul di tabel
- Kategori tersedia di filter produk

### 4.3 Edit & Hapus Kategori
**Langkah-langkah**:
1. Edit kategori: Ubah deskripsi
2. Hapus kategori dengan konfirmasi dialog

---

## 👥 BAGIAN 5: MANAJEMEN PELANGGAN

### 5.1 Daftar Pelanggan
**Akses**: Menu Customers (Administrator, Manager, Cashier)

**Fitur yang ditampilkan**:
- Tabel dengan kolom: Nama, Telepon, Email, Alamat, Aksi
- Search by nama pelanggan
- Pagination
- Tombol: Edit, Riwayat Transaksi, Hapus

**Demo Scenario**:
1. Buka halaman Customers
2. Cari pelanggan dengan nama "Budi"
3. Perhatikan daftar pelanggan

### 5.2 Tambah Pelanggan
**Langkah-langkah**:
1. Klik "Tambah Pelanggan"
2. Isi form:
   - Nama: `Budi Santoso`
   - Telepon: `081234567890`
   - Email: `budi@email.com`
   - Alamat: `Jl. Merdeka No. 123, Jakarta`
3. Klik "Simpan"

**Hasil**:
- Toast success
- Pelanggan baru muncul di tabel
- Pelanggan tersedia di dropdown transaksi

### 5.3 Riwayat Transaksi Pelanggan
**Langkah-langkah**:
1. Klik tombol "Riwayat" (ikon history) pada pelanggan
2. Halaman Customer History terbuka

**Fitur yang ditampilkan**:
- Info pelanggan (nama, telepon, email)
- Tabel riwayat transaksi:
  - No. Invoice
  - Tanggal
  - Total
  - Status Pembayaran
  - Metode Pembayaran
- Total pembelanjaan

### 5.4 Edit & Hapus Pelanggan
**Langkah-langkah**:
1. Edit pelanggan: Ubah nomor telepon
2. Hapus pelanggan dengan konfirmasi
   - Note: Hanya Administrator & Manager yang bisa hapus

---

## 💳 BAGIAN 6: TRANSAKSI PENJUALAN (POINT OF SALE)

### 6.1 Halaman Transaksi
**Akses**: Menu Transactions (Administrator & Cashier)

**Layout Halaman**:
- **Kiri (60%)**: Daftar Produk
- **Kanan (40%)**: Keranjang & Pembayaran

### 6.2 Cari & Pilih Produk
**Langkah-langkah**:
1. Di panel kiri, gunakan search: "kopi"
2. Filter by kategori: "Minuman"
3. Klik produk untuk menambah ke keranjang

**Tampilan Produk**:
- Card dengan border gradient saat hover
- Badge tipe (Barang/Jasa)
- Badge kategori
- Nama produk
- Harga
- Stok (untuk barang) atau Status (untuk jasa)
- Disabled jika stok habis atau jasa tidak tersedia

### 6.3 Kelola Keranjang
**Fitur yang ditampilkan**:
1. **Pilih Pelanggan**:
   - Dropdown dengan daftar pelanggan
   - "Walk-in Customer (Umum)" sebagai default
   - Tombol "Pelanggan Baru" untuk quick add

2. **Item Keranjang**:
   - Nama produk
   - Harga per item
   - Kontrol quantity (- input +)
   - Tombol hapus item
   - Update otomatis total

3. **Tombol Tersimpan**:
   - Menampilkan jumlah transaksi yang di-hold
   - Buka modal untuk resume/delete

**Demo Scenario**:
1. Tambah 3 item ke keranjang:
   - Es Kopi Susu (qty 2)
   - Roti Bakar (qty 1)
   - Teh Manis (qty 3)
2. Ubah quantity Es Kopi Susu menjadi 3
3. Hapus Teh Manis dari keranjang
4. Pilih pelanggan "Budi Santoso"

### 6.4 Proses Pembayaran
**Langkah-langkah**:
1. Di panel kanan, scroll ke "Metode Pembayaran"
2. Pilih metode: "Cash"
3. Input jumlah: `60000`
4. Input uang diterima: `100000`
5. Sistem otomatis hitung kembalian: `40000`
6. Klik "Bayar & Cetak Invoice"

**Metode Pembayaran Tersedia**:
- **Cash**: Input uang diterima, hitung kembalian otomatis
- **QRIS**: Input provider, nomor referensi
- **Transfer**: Input provider, nomor referensi
- **E-Wallet**: Input provider, nomor referensi

**Multiple Payment Methods**:
- Klik "Tambah" untuk menambah entry pembayaran baru
- Bisa gabungkan metode (misal: 50% cash, 50% transfer)
- Total paid akan diakumulasi

### 6.5 Pembayaran Sebagian (Partial Payment)
**Langkah-langkah**:
1. Centang "Izinkan pembayaran sebagian"
2. Input pembayaran kurang dari total
3. Klik "Buat Invoice (Partial)"
4. Invoice dibuat dengan status "Partially Paid"
5. Sisa pembayaran dapat dilakukan di menu Outstanding Payments

**Demo Scenario**:
1. Total tagihan: `60000`
2. Bayar cash: `30000`
3. Centang izinkan partial
4. Klik "Buat Invoice (Partial)"
5. Invoice terbentuk dengan sisa `30000`

### 6.6 Tahan Transaksi (Hold/Resume)
**Fitur**:
- Simpan keranjang sementara
- Resume nanti untuk lanjut pembayaran
- Berguna untuk kasus antrian panjang

**Langkah-langkah**:
1. Isi keranjang dengan beberapa item
2. Klik "Tahan Transaksi"
3. Transaksi tersimpan di modal "Transaksi Tersimpan"
4. Bisa lanjutkan atau hapus

**Demo Scenario**:
1. Tambah item ke keranjang
2. Pilih pelanggan
3. Klik "Tahan Transaksi"
4. Buka modal "Tersimpan"
5. Klik "Lanjutkan" untuk restore keranjang
6. Lanjutkan proses pembayaran

### 6.7 Tambah Pelanggan Cepat dari Transaksi
**Langkah-langkah**:
1. Di keranjang, klik "Pelanggan Baru"
2. Isi form quick add:
   - Nama: `Siti Aminah`
   - Telepon: `089876543210`
   - Email & Alamat (opsional)
3. Klik "Simpan"
4. Pelanggan otomatis terpilih di dropdown

### 6.8 Checkout Lengkap
**Scenario Full Flow**:
1. Search produk: "minuman"
2. Tambah: Es Kopi Susu (qty 2), Jus Jeruk (qty 1)
3. Pilih pelanggan: "Budi Santoso"
4. Metode: Cash, Jumlah: 60000, Uang Diterima: 100000
5. Klik "Bayar & Cetak Invoice"
6. Invoice terbentuk dengan status "Paid"
7. Keranjang otomatis kosong
8. Toast success muncul

---

## 🧾 BAGIAN 7: MANAJEMEN INVOICE

### 7.1 Daftar Invoice
**Akses**: Menu Invoices (Administrator, Manager, Cashier)

**Fitur yang ditampilkan**:
- Tabel dengan kolom:
  - No. Invoice
  - Tanggal
  - Total
  - Metode Pembayaran
  - Status Pembayaran
  - Status Sync Odoo
  - Aksi (Sync ke Odoo, Detail)

- Filter:
  - Search by no. invoice
  - Filter by status (unpaid, partial, paid, overdue, cancelled)

- Pagination

**Badges Status**:
- Payment Status: unpaid (grey), partial (yellow), paid (green), overdue (red)
- Sync Status: pending (yellow), synced (green), failed (red)

**Demo Scenario**:
1. Buka halaman Invoices
2. Perhatikan daftar invoice
3. Filter status: "Lunas"
4. Cari invoice by number

### 7.2 Detail Invoice
**Langkah-langkah**:
1. Klik tombol "Detail" pada invoice
2. Halaman Invoice Detail terbuka

**Fitur yang ditampilkan**:
1. **Header Invoice**:
   - No. Invoice
   - Tanggal
   - Status Pembayaran
   - Status Sync Odoo

2. **Info Pelanggan**:
   - Nama, Telepon, Email, Alamat

3. **Daftar Item**:
   - Nama produk
   - Qty
   - Harga per item
   - Subtotal

4. **Ringkasan Pembayaran**:
   - Total Tagihan
   - Total Dibayar
   - Sisa (jika partial)
   - Kembalian (jika cash lunas)

5. **Riwayat Pembayaran**:
   - Tabel pembayaran dengan detail:
     - Metode
     - Provider
     - Nominal
     - Waktu
     - Referensi

6. **Aksi**:
   - Cetak Invoice
   - Sync ke Odoo (jika Admin/Manager)

### 7.3 Sync Invoice ke Odoo
**Langkah-langkah**:
1. Di daftar invoice, klik tombol "Sync" (ikon refresh)
2. Tunggu proses sinkronisasi
3. Status sync berubah menjadi "synced"

**Fitur**:
- Sync manual per invoice
- Force sync ulang (jika sudah synced)
- Status indikator dengan animasi loading

---

## 💰 BAGIAN 8: PEMBAYARAN TERTUNDA (OUTSTANDING PAYMENTS)

### 8.1 Overview Outstanding Payments
**Akses**: Menu Outstanding Payments (Administrator, Manager, Cashier)

**Fitur yang ditampilkan**:
1. **Statistik Cards** (4 kartu):
   - Total Tertunda (nominal)
   - Total Invoice
   - Overdue (jumlah invoice jatuh tempo)
   - Partial (jumlah invoice dibayar sebagian)

2. **Filter**:
   - Search by invoice atau pelanggan
   - Filter by status (unpaid, partial, overdue)

3. **Tabel Invoice**:
   - No. Invoice
   - Pelanggan
   - Tanggal
   - Jatuh Tempo (merah jika overdue)
   - Total
   - Dibayar
   - Sisa
   - Status
   - Tombol "Bayar"

**Demo Scenario**:
1. Buka halaman Outstanding Payments
2. Perhatikan statistik tertunda
3. Filter status: "Jatuh Tempo"
4. Cari invoice by pelanggan

### 8.2 Tambah Pembayaran
**Langkah-langkah**:
1. Klik tombol "Bayar" pada invoice
2. Modal pembayaran terbuka

**Modal Pembayaran**:
- Info invoice (No, Total, Sudah Dibayar, Sisa)
- Input jumlah pembayaran (maks: sisa)
- Pilih metode pembayaran
- Input provider & referensi (untuk non-cash)
- Catatan opsional

**Demo Scenario**:
1. Pilih invoice dengan sisa Rp 30.000
2. Input jumlah: 30000
3. Metode: Transfer
4. Provider: BCA
5. Referensi: TRX-12345
6. Catatan: "Pelunasan tagihan"
7. Klik "Simpan Pembayaran"

**Hasil**:
- Toast success
- Invoice status berubah menjadi "Lunas"
- Statistik tertunda terupdate
- Invoice hilang dari daftar outstanding

---

## 📊 BAGIAN 9: LAPORAN & ANALISIS

### 9.1 Halaman Laporan
**Akses**: Menu Reports (Administrator & Manager)

**Tabs Laporan**:
1. Penjualan Harian
2. Penjualan Bulanan
3. Penjualan Produk
4. Laporan Pembayaran

### 9.2 Laporan Penjualan Harian
**Langkah-langkah**:
1. Pilih tab "Penjualan Harian"
2. Pilih tanggal (default: hari ini)
3. Klik "Generate"

**Output**:
- **Ringkasan**:
  - Total Transaksi
  - Total Revenue

- **Daftar Transaksi**:
  - No Invoice
  - Tanggal
  - Kasir
  - Pelanggan
  - Metode Pembayaran
  - Total

### 9.3 Laporan Penjualan Bulanan
**Langkah-langkah**:
1. Pilih tab "Penjualan Bulanan"
2. Input tahun (default: tahun ini)
3. Input bulan (1-12)
4. Klik "Generate"

**Output**:
- **Ringkasan Bulanan**:
  - Total Transaksi
  - Total Revenue

- **Breakdown Harian**:
  - Tanggal
  - Jumlah Transaksi
  - Revenue per hari

### 9.4 Laporan Penjualan Produk
**Langkah-langkah**:
1. Pilih tab "Penjualan Produk"
2. Input date range (dari - sampai)
3. Klik "Generate"

**Output**:
- Tabel dengan kolom:
  - Kode Produk
  - Nama Produk
  - Qty Terjual
  - Total Revenue

### 9.5 Laporan Pembayaran
**Langkah-langkah**:
1. Pilih tab "Laporan Pembayaran"
2. Input date range
3. Klik "Generate"

**Output**:
- Tabel dengan kolom:
  - No Invoice
  - Metode
  - Provider
  - Referensi
  - Nominal
  - Status
  - Waktu

### 9.6 Export CSV
**Langkah-langkah**:
1. Generate laporan (salah satu tab)
2. Klik tombol "Export CSV"
3. File otomatis terdownload

**Filename**:
- Daily: `daily-sales-YYYY-MM-DD.csv`
- Monthly: `monthly-sales-YYYY-MM.csv`
- Product: `product-sales-from-to.csv`
- Payment: `payments-from-to.csv`

---

## 👤 BAGIAN 10: MANAJEMEN PENGGUNA

### 10.1 Daftar Pengguna
**Akses**: Menu Users (Hanya Administrator)

**Fitur yang ditampilkan**:
- Tabel dengan kolom:
  - Nama
  - Email
  - Role (Administrator/Manager/Cashier)
  - Status (Aktif/Nonaktif)
  - Aksi (Edit, Hapus)

- Badges Role:
  - Administrator: Primary badge
  - Manager: Warning badge
  - Cashier: Success badge

**Demo Scenario**:
1. Buka halaman Users
2. Perhatikan daftar pengguna
3. Role yang ada: admin, manager, cashier

### 10.2 Tambah Pengguna
**Langkah-langkah**:
1. Klik "Tambah Pengguna"
2. Isi form:
   - Nama: `Dewi Lestari`
   - Email: `dewi@pos.local`
   - Role: Cashier
   - Password: `Cashier@456`
   - Status: Aktif
3. Klik "Simpan"

**Validasi**:
- Nama required
- Email required & valid format
- Role required
- Password required (untuk user baru)
- Password minimal 8 karakter

**Hasil**:
- Toast success
- User baru muncul di tabel

### 10.3 Edit Pengguna
**Langkah-langkah**:
1. Klik tombol Edit pada user
2. Ubah role dari Cashier ke Manager
3. Password kosongkan jika tidak ingin diubah
4. Klik "Simpan"

**Hasil**:
- Role user terupdate
- Akses menu berubah sesuai role baru

### 10.4 Nonaktifkan Pengguna
**Langkah-langkah**:
1. Edit user
2. Ubah status ke "Nonaktif"
3. Klik "Simpan"

**Hasil**:
- User tidak bisa login
- Badge status berubah menjadi grey

### 10.5 Hapus Pengguna
**Langkah-langkah**:
1. Klik tombol Hapus pada user
2. Konfirmasi dialog: "Hapus pengguna ini?"
3. Klik "Ya, Hapus"

**Hasil**:
- User dihapus dari sistem
- Tidak bisa dipulihkan

---

## 📝 BAGIAN 11: AUDIT LOG HISTORY

### 11.1 Overview Audit Log
**Akses**: Menu Audit Logs (Administrator & Manager)

**Fitur yang ditampilkan**:
1. **Statistik**:
   - Total Logs
   - Logs Hari Ini

2. **Filter**:
   - User (dropdown semua user)
   - Activity (search text)
   - Entity Type (user, product, category, customer, invoice)
   - Date range (dari - sampai)

3. **Tabel Audit Log**:
   - Tanggal
   - User
   - Activity (login, create, update, delete, dll)
   - Entity (type + ID)
   - Tombol Detail

**Demo Scenario**:
1. Buka halaman Audit Logs
2. Perhatikan statistik
3. Filter by user: "admin"
4. Filter by entity type: "product"
5. Filter date range: hari ini

### 11.2 Detail Audit Log
**Langkah-langkah**:
1. Klik tombol "Detail" (ikon mata) pada log
2. Modal detail terbuka

**Informasi Detail**:
- ID Log
- Tanggal & waktu
- User (nama + email)
- Activity
- Entity (type + ID)
- Metadata (JSON format)

**Contoh Activity**:
- `user.login` - User login
- `product.create` - Produk dibuat
- `product.update` - Produk diupdate
- `product.delete` - Produk dihapus
- `invoice.create` - Invoice dibuat
- `payment.create` - Pembayaran dibuat
- `stock.adjustment` - Stok disesuaikan

---

## 🔗 BAGIAN 12: INTEGRASI ODOO

### 12.1 Status Koneksi Odoo
**Akses**: Menu Integration (Administrator & Manager)

**Fitur yang ditampilkan**:
1. **Status Koneksi**:
   - Icon terhubung/tidak terhubung
   - Status text (green/red)
   - Pesan detail

2. **Info Server**:
   - URL Odoo
   - Database Name
   - Versi Odoo
   - Journal Penjualan

3. **Tombol Aksi**:
   - "Sinkron Produk & Pelanggan" - Sync master data
   - "Uji Koneksi" - Test connection

**Demo Scenario**:
1. Buka halaman Integration
2. Perhatikan status koneksi
3. Klik "Uji Koneksi"
4. Perhatikan status berubah

### 12.2 Sinkronisasi Master Data
**Langkah-langkah**:
1. Klik "Sinkron Produk & Pelanggan"
2. Tunggu proses sync
3. Toast success dengan summary:
   - X/Y produk tersinkron
   - X/Y pelanggan tersinkron

**Apa yang disinkron**:
- Produk dari POS → Odoo (product.template)
- Pelanggan dari POS → Odoo (res.partner)

### 12.3 Invoice Perlu Sinkron
**Fitur yang ditampilkan**:
- Tabel invoice dengan sync status != synced
- Kolom: No. Invoice, Tanggal, Total, Status Odoo, Aksi
- Tombol "Sync Semua" untuk batch sync
- Tombol "Sync" per invoice

**Status Sync**:
- `pending` - Belum disinkron
- `syncing` - Sedang proses
- `failed` - Gagal sinkron
- `manual_review` - Perlu review manual

**Demo Scenario**:
1. Perhatikan daftar invoice pending
2. Klik "Sync" pada satu invoice
3. Perhatikan status berubah
4. Klik "Sync Semua" untuk sync batch

---

## 🎬 SCENARIO DEMO LENGKAP (FULL FLOW)

### Scenario 1: Alur Transaksi Harian (Cashier)
**Role**: Cashier

**Langkah-langkah**:
1. Login sebagai Cashier
2. Redirect ke halaman Transactions
3. Cari produk "kopi"
4. Tambah Es Kopi Susu (qty 2)
5. Tambah Roti Bakar (qty 1)
6. Pilih pelanggan "Walk-in Customer"
7. Metode pembayaran: Cash
8. Input uang diterima: 100000
9. Klik "Bayar & Cetak Invoice"
10. Invoice terbentuk, keranjang kosong
11. Buka menu Invoices untuk melihat invoice baru
12. Buka menu Outstanding Payments (kosong karena lunas)

### Scenario 2: Alur Manajemen Stok (Manager)
**Role**: Manager

**Langkah-langkah**:
1. Login sebagai Manager
2. Buka Dashboard, perhatikan statistik
3. Buka Products
4. Tambah produk baru "Snack Ciparat"
5. Buka kartu stok produk "Es Kopi Susu"
6. Perhatikan pergerakan stok
7. Sesuaikan stok via Opname
8. Buka Audit Logs, filter activity "stock.adjustment"
9. Buka Reports, generate laporan penjualan harian
10. Export laporan ke CSV

### Scenario 3: Alur Pembayaran Tertunda (Administrator)
**Role**: Administrator

**Langkah-langkah**:
1. Login sebagai Administrator
2. Buka Transactions
3. Buat transaksi dengan partial payment
4. Bayar 50% dari total
5. Invoice terbentuk dengan status "Partially Paid"
6. Buka Outstanding Payments
7. Perhatikan invoice di daftar
8. Tambah pembayaran untuk pelunasan
9. Status berubah menjadi "Lunas"
10. Buka Integration, sync invoice ke Odoo

### Scenario 4: Alur Manajemen User (Administrator)
**Role**: Administrator

**Langkah-langkah**:
1. Login sebagai Administrator
2. Buka Users
3. Tambah user baru dengan role Cashier
4. Edit user, ubah role ke Manager
5. Nonaktifkan user lama
6. Buka Audit Logs, filter entity "user"
7. Perhatikan activity tracking lengkap

---

## ⚠️ CATATAN PENTING

### Validasi & Error Handling
- Semua form memiliki validasi client-side
- Error message dari API ditampilkan via toast
- Loading indikator pada semua async operation
- Konfirmasi dialog untuk destructive action (delete)

### Role-Based Access Control
- Administrator: Full access
- Manager: Access kecuali Users & Transactions
- Cashier: Hanya Transactions, Customers, Invoices, Outstanding Payments
- Auto-redirect jika akses ditolak

### Security Features
- JWT token authentication
- Password hashing (bcrypt)
- Token refresh mechanism
- Audit logging untuk semua aktivitas
- Role check di backend & frontend

### Responsive Design
- Mobile-friendly layout
- Grid responsive untuk cards & tables
- Modal responsive
- Sidebar collapse di mobile

---

## 🎯 CHECKLIST DEMO

- [ ] Login sebagai 3 role berbeda
- [ ] Dashboard dengan semua filter
- [ ] CRUD Produk (Barang & Jasa)
- [ ] Kartu Stok dengan penyesuaian
- [ ] CRUD Kategori
- [ ] CRUD Pelanggan
- [ ] Riwayat transaksi pelanggan
- [ ] POS Transaction full flow
- [ ] Multiple payment methods
- [ ] Partial payment
- [ ] Hold/Resume transaction
- [ ] Daftar Invoice dengan filter
- [ ] Detail Invoice
- [ ] Outstanding Payments
- [ ] Tambah pembayaran tertunda
- [ ] 4 jenis laporan
- [ ] Export CSV
- [ ] CRUD User
- [ ] Audit Log dengan filter
- [ ] Detail audit log
- [ ] Status koneksi Odoo
- [ ] Sync master data
- [ ] Sync invoice ke Odoo

---

## 📞 DUKUNGAN & KONTAK

Jika ada pertanyaan atau kendala selama demo:
- Email: support@zicare.local
- Dokumentasi: `/docs` folder

---

**Script Demo Ini Disusun Oleh**: Devin AI Assistant  
**Tanggal**: 15 Oktober 2026  
**Versi Dokumen**: 1.0
