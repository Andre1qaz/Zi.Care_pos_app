# POS Sales Dashboard (Odoo 19)

Modul custom untuk menambahkan dashboard BI di Odoo, menggabungkan data
penjualan dari MariaDB (database POS kamu) dengan data yang sudah
tersinkron di Odoo Accounting.

## 1. Instalasi

1. Copy folder `pos_sales_dashboard` ke folder addons custom Odoo kamu,
   sejajar dengan `pos_integration_manager`, misalnya:
   ```
   C:\odoo19\custom-addons\pos_sales_dashboard
   ```
2. Install library Python yang dibutuhkan (di virtualenv/environment yang
   dipakai Odoo):
   ```
   pip install pymysql
   ```
3. Buka Odoo -> Apps -> **Update Apps List** (aktifkan mode developer dulu
   kalau belum: tambahkan `?debug=1` di URL, seperti di screenshot kamu).
4. Cari "POS Sales Dashboard" di Apps, klik **Activate**.

## 2. Konfigurasi koneksi MariaDB

Buka menu **POS Dashboard > Konfigurasi Database** di sidebar Odoo,
buat record baru (atau edit yang otomatis dibuat pertama kali), isi
Host, Port, User, Password, dan Nama Database sesuai MariaDB yang
dipakai backend Phalcon kamu.

(Catatan: sebelumnya field ini rencananya ditaruh di halaman Settings
global Odoo, tapi struktur view Settings beda-beda tiap versi Odoo dan
menyebabkan ParseError saat install. Sekarang dipindah ke halaman
konfigurasi sendiri yang lebih stabil.)

## 3. Sesuaikan query dengan skema tabel asli

Query di `controllers/main.py` method `_fetch_external_sales_by_category`
memakai asumsi nama tabel: `transactions`, `transaction_items`,
`products`, `categories`. Sesuaikan nama tabel/kolom dengan skema MariaDB
project KP kamu yang sebenarnya.

## 4. Akses dashboard

Menu baru **POS Dashboard > Laporan Penjualan** akan muncul di backend
Odoo (`localhost:8069`).

## 5. Menyambungkan ke menu "Laporan" di frontend Vue POS kamu

Dashboard OWL ini hidup di Odoo, terpisah dari frontend Vue.js kamu
(`localhost:5173`). Ada 2 opsi kalau kamu mau chart yang sama juga
tampil di menu "Laporan" sisi Vue:

**Opsi A - Iframe (paling cepat)**
Embed halaman action Odoo langsung via iframe di komponen
`LaporanView.vue`, arahkan ke:
```
http://localhost:8069/odoo/action-<id_action>
```

**Opsi B - Fetch langsung dari Vue (lebih rapi, tanpa iframe)**
Panggil endpoint yang sama dari Vue pakai axios, lalu render chart pakai
Chart.js/ApexCharts yang sudah ada di stack Vue kamu:
```js
const res = await axios.post(
  'http://localhost:8069/pos_sales_dashboard/data',
  { jsonrpc: '2.0', params: { date_from, date_to } },
  { withCredentials: true }
);
```
Catatan: endpoint pakai `auth='user'`, jadi request dari Vue perlu sudah
login/punya session Odoo yang valid, atau kamu ganti ke `auth='public'`
dan tambahkan API key/token sendiri kalau POS dan Odoo dipisah domainnya.

## 6. Target Penjualan (Input + Olah Data)

Menu **POS Dashboard > Target Penjualan** memungkinkan input target
penjualan per kategori per periode. Sistem otomatis:
1. Ambil target yang berlaku untuk rentang tanggal yang sedang dilihat
   di dashboard (kalau ada beberapa target overlap, dijumlahkan)
2. Bandingkan dengan realisasi penjualan aktual (gabungan MariaDB + Odoo)
3. Hitung persentase pencapaian, ditampilkan dengan indikator warna:
   - Hijau: pencapaian >= 100%
   - Kuning: pencapaian 70-99%
   - Merah: pencapaian < 70%

**Catatan:** field `category_name` di form Target harus diisi PERSIS
sama dengan nama kategori yang ada di aplikasi POS (case-sensitive),
supaya sistem bisa mencocokkan target dengan data penjualan aktual.

## 7. Export CSV dan Cetak PDF

Di halaman **Laporan Penjualan**, ada 2 tombol baru di pojok kanan toolbar:

- **Export CSV** — download file `.csv` (delimiter `;`, encoding UTF-8 dengan
  BOM supaya karakter non-ASCII terbaca benar di Excel Windows), berisi
  rincian per kategori + baris TOTAL di akhir.
- **Cetak PDF** — generate laporan PDF format formal (Times New Roman,
  hitam-putih, tabel bergaris) memakai wkhtmltopdf bawaan Odoo.

**Prasyarat PDF:** fitur cetak PDF butuh `wkhtmltopdf` terinstall di sistem
kamu. Ini biasanya sudah otomatis ter-install bersama Odoo (dipakai juga
untuk cetak invoice/laporan bawaan Odoo). Kalau tombol "Cetak PDF" error,
kemungkinan `wkhtmltopdf` belum ada di PATH — cek dengan buka laporan
bawaan Odoo (misalnya cetak invoice dari Accounting), kalau itu juga
gagal, berarti wkhtmltopdf memang belum terinstall di sistem kamu.

Kedua tombol otomatis memakai rentang tanggal (Dari-Sampai) yang sedang
aktif di filter dashboard saat itu.
