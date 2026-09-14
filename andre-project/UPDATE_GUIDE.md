# PANDUAN UPDATE KONFIGURASI ODOO

## Masalah
Sync invoice tetap failed karena konfigurasi Odoo di file `.env` masih menggunakan konfigurasi lama yang salah.

## Langkah-langkah Update

### 1. Stop Server Backend
Tekan `Ctrl+C` di terminal yang menjalankan backend untuk menghentikan server.

### 2. Buka File .env
Buka file `backend/.env` dengan text editor (VS Code, Notepad, dll).

### 3. Update Konfigurasi Odoo
Cari bagian konfigurasi Odoo di file `.env` dan ganti dengan konfigurasi berikut:

```env
# Konfigurasi Odoo - UPDATE BAGIAN INI
ODOO_URL=http://localhost:8069
ODOO_DB=pos_db
ODOO_USERNAME=andre
ODOO_PASSWORD=314b26ce38b12a37b456478179c2666c1ef72dfd
ODOO_SYNC_ENABLED=true
ODOO_SYNC_MAX_RETRY=3
```

### 4. Simpan File
Simpan file `.env` setelah melakukan perubahan.

### 5. Restart Server Backend
Jalankan kembali server backend:
```bash
cd backend
php -S localhost:8080 -t public public/router.php
```

### 6. Verifikasi Konfigurasi
Jalankan script verifikasi untuk memastikan konfigurasi sudah benar:
```bash
cd backend
php check_config_and_test_sync.php
```

Output yang diharapkan:
```
✓ Konfigurasi sudah benar!
✓ Authentication berhasil! User ID: 2
```

### 7. Test Sync Invoice
Setelah konfigurasi benar, buat transaksi baru di aplikasi POS dan cek apakah sync berhasil.

## Troubleshooting

### Jika masih gagal setelah update:

1. **Restart server backend** - Pastikan server benar-benar berhenti dan restart
2. **Clear cache** - Hapus file cache jika ada
3. **Cek log** - Lihat file `backend/storage/logs/app.log` untuk error detail
4. **Test manual** - Jalankan `php test_odoo_sync.php` untuk test koneksi manual

## Konfigurasi Lengkap .env (Referensi)

Berikut konfigurasi lengkap yang seharusnya ada di file `backend/.env`:

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=change-this-to-a-random-32-char-secret

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=pos_db
DB_USER=root
DB_PASS=

JWT_SECRET=your-jwt-secret-key-min-32-chars
JWT_EXPIRY=28800

ODOO_URL=http://localhost:8069
ODOO_DB=pos_db
ODOO_USERNAME=andre
ODOO_PASSWORD=314b26ce38b12a37b456478179c2666c1ef72dfd
ODOO_SYNC_ENABLED=true
ODOO_SYNC_MAX_RETRY=3

LOG_PATH=storage/logs/app.log
```

## Catatan Penting

- File `.env` berisi konfigurasi sensitif (API Key, password)
- File ini tidak boleh di-commit ke git (sudah ada di .gitignore)
- Pastikan untuk membackup file `.env` sebelum mengubahnya
