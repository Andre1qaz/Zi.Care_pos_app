# PANDUAN UPDATE KONFIGURASI ODOO

## Masalah
Sync invoice gagal karena konfigurasi Odoo di file `.env` salah (mis. nama database,
username, atau kredensial yang tidak dikenali Odoo).

## Prasyarat
- Dapatkan **API key** Odoo: di Odoo buka *Settings > Users & Companies > Users > prefensi
  user* → tab *Account Security* → *API Keys* → buat key baru (contoh:
  `8082cd1e...`). API key dipakai lewat JSON-RPC `common.authenticate` /
  `object.execute_kw` (endpoint `/web/session/authenticate` TIDAK menerima API key).
- `ODOO_DB` harus nama database yang ada di Odoo (biasanya `odoo` — verifikasi lewat
  halaman *Databases* Odoo), **bukan** nama database MySQL POS.

## Langkah-langkah Update

### 1. Stop Server Backend
Tekan `Ctrl+C` di terminal yang menjalankan backend untuk menghentikan server.

### 2. Edit `.env`
Buka `backend/.env` (file ini sudah di-gitignore; jangan pernah commit nilai aslinya).

```env
ODOO_URL=http://localhost:8069
ODOO_DB=odoo                  # database Odoo, bukan pos_db
ODOO_USERNAME=admin           # login user Odoo dengan akses Accounting
ODOO_API_KEY=...              # API key Odoo (preferred; isi di .env lokal saja)
ODOO_PASSWORD=                # fallback bila ODOO_API_KEY kosong (password user Odoo)
ODOO_SYNC_ENABLED=true
ODOO_SYNC_MAX_RETRY=3
ODOO_JOURNAL_CASH_CODE=CSH1   # kode jurnal kas yang dipakai sync
ODOO_JOURNAL_BANK_CODE=BNK1   # kode jurnal bank untuk pembayaran non-cash
```

> Jika `ODOO_API_KEY` diisi, `ODOO_PASSWORD` tidak wajib (API key lebih aman dan
> diprioritaskan oleh `OdooSyncService`).

### 3. Simpan & Restart
```bash
cd backend
php -S localhost:8090 -t public public/router.php
```

### 4. Verifikasi
Buat satu transaksi lunas di aplikasi POS, lalu cek:
- Di POS: `invoices.sync_status` menjadi `synced` dan `odoo_move_id` terisi.
- Di Odoo: muncul journal entry baru (Referensi `POS: <nomor_invoice>`).

Baris yang gagal sync dapat diproses ulang:
```bash
cd backend
php cli/sync_odoo.php
```

Log error ada di `backend/storage/logs/app.log` dan `odoo_sync_logs` (tabel).

## Troubleshooting

1. **`Auth failed`** → `ODOO_USERNAME`/`ODOO_API_KEY` salah atau user tidak punya akses; cek API key di *preferences* user.
2. **`Invalid database`** → `ODOO_DB` salah; pastikan itu nama database Odoo (`odoo`).
3. **Journal tidak ketemu** → set `ODOO_JOURNAL_CASH_CODE`/`ODOO_JOURNAL_BANK_CODE` sesuai kode jurnal yang benar.
4. **Double counting di dashboard Odoo** → pastikan invoice yang sudah `synced` tidak ditambahkan ulang; modul `pos_sales_dashboard` otomatis hanya menghitung invoice non-synced dari MariaDB.
5. **Cek log** → lihat `backend/storage/logs/app.log` untuk detail error.

## Konfigurasi Lengkap `.env` (Referensi)

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8090
APP_KEY=change-this-to-a-random-32-char-secret

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=pos_db
DB_USER=root
DB_PASS=

JWT_SECRET=your-jwt-secret-key-min-32-chars
JWT_EXPIRY=28800

ODOO_URL=http://localhost:8069
ODOO_DB=odoo
ODOO_USERNAME=admin
ODOO_API_KEY=
ODOO_PASSWORD=
ODOO_JOURNAL_CASH_CODE=CSH1
ODOO_JOURNAL_BANK_CODE=BNK1
ODOO_SYNC_ENABLED=true
ODOO_SYNC_MAX_RETRY=3

CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173

LOG_PATH=storage/logs/app.log
```

## Catatan Penting

- File `.env` berisi konfigurasi sensitif (API Key, password) → **jangan di-commit** (sudah di-gitignore).
- Selalu backup `.env` sebelum mengubah.
- Skrip debug one-off lama (`check_*`, `test_*`, `setup_*`) sudah dihapus; gunakan `cli/` yang di-maintain.