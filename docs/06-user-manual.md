# User Manual – POS Application

## 1. Pendahuluan

Manual ini ditujukan untuk pengguna aplikasi POS: Administrator, Manager, dan Cashier.

---

## 2. Login

1. Buka aplikasi di browser
2. Masukkan email dan password
3. Klik **Login**

| Role | Email Default | Password |
|------|---------------|----------|
| Administrator | admin@pos.local | Admin@123 |
| Manager | manager@pos.local | Manager@123 |
| Cashier | cashier@pos.local | Cashier@123 |

Setelah login, Cashier diarahkan ke halaman Transaksi. Admin/Manager ke Dashboard.

---

## 3. Dashboard (Admin & Manager)

Menampilkan:
- **Penjualan Hari Ini** – total revenue hari ini
- **Total Transaksi** – jumlah transaksi hari ini
- **Total Invoice** – jumlah invoice terbit
- **Revenue Bulan Ini** – akumulasi bulan berjalan
- **Produk Terlaris** – ranking produk hari ini
- **Statistik Pembayaran** – breakdown per metode

---

## 4. Transaksi Penjualan (Cashier)

### Alur Transaksi

1. **Pilih Produk** – klik produk dari grid
2. **Keranjang** – produk masuk ke keranjang di panel kanan
3. **Atur Quantity** – ubah jumlah di kolom Qty
4. **Total** – otomatis dihitung
5. **Metode Pembayaran:**
   - **Tunai:** masukkan uang diterima, sistem hitung kembalian
   - **Non-Tunai (QRIS/Transfer/E-Wallet):** isi provider dan nomor referensi
6. **Bayar & Cetak Invoice** – transaksi disimpan, invoice ditampilkan

### Catatan
- Stok otomatis berkurang setelah transaksi
- Setiap transaksi WAJIB menghasilkan invoice
- Invoice tersinkronisasi ke Odoo secara otomatis

---

## 5. Manajemen Produk (Admin)

- **Tambah Produk:** klik + Tambah Produk, isi form
- **Edit:** klik Edit pada baris produk
- **Hapus:** soft delete (produk tidak tampil tapi data historis tetap)
- **Cari:** gunakan search bar
- **Filter:** pilih kategori
- **Stok Rendah:** baris merah jika stok ≤ 10

---

## 6. Manajemen Kategori (Admin)

CRUD kategori produk untuk organisasi katalog.

---

## 7. Manajemen Pelanggan (Admin & Cashier)

- Tambah, edit, hapus data pelanggan
- Cari berdasarkan nama, telepon, atau email
- Pelanggan dapat dipilih saat transaksi (fitur lanjutan)

---

## 8. Invoice

### Melihat Invoice
Menu **Invoice** → klik **Detail** pada invoice yang diinginkan

### Mencetak Invoice
Di halaman detail invoice → klik **Cetak** → dialog print browser

### Download PDF
Klik **Download PDF** untuk unduh versi PDF

---

## 9. Laporan (Admin & Manager)

| Laporan | Deskripsi |
|---------|-----------|
| Penjualan Harian | Transaksi per tanggal |
| Penjualan Bulanan | Breakdown per hari dalam bulan |
| Penjualan Produk | Ranking produk by revenue |
| Laporan Pembayaran | Detail semua pembayaran |

Pilih filter tanggal → klik **Generate**

---

## 10. Manajemen Pengguna (Admin)

Kelola akun pengguna sistem dengan role:
- **Administrator** – akses penuh
- **Manager** – dashboard & laporan
- **Cashier** – transaksi & pelanggan

---

## 11. FAQ

**Q: Transaksi gagal "Insufficient stock"?**
A: Stok produk tidak cukup. Kurangi quantity atau update stok di menu Produk.

**Q: Invoice sync_status "failed"?**
A: Hubungi Administrator. Sync ke Odoo akan di-retry otomatis.

**Q: Lupa password?**
A: Hubungi Administrator untuk reset password.
