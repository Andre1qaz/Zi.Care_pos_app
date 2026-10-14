# POS Application – Enterprise Point of Sales

python odoo-bin -r odoo -w odoo -d odoo

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Phalcon PHP 5.x (REST API, port default **8090**) |
| Frontend | Vue.js 3, Vue Router, Pinia, Axios (Vite, port **5173**) |
| Database | MariaDB 10.6+ (XAMPP/phpMyAdmin) |
| ERP | Odoo 19 (Accounting, port **8069**) |

## Prerequisites

- PHP 8.1+ dengan ekstensi Phalcon
- Composer
- Node.js 18+
- MariaDB 10.6+ (XAMPP)
- Odoo 19 (opsional, untuk ERP sync)

## Quick Start

### 1. Database

Jalankan seluruh migrasi berurutan, lalu seed:

```bash
mysql -u root -p < database/migrations/001_initial_schema.sql
mysql -u root -p < database/migrations/002_add_product_type.sql
mysql -u root -p < database/migrations/003_fix_service_stock.sql
mysql -u root -p < database/migrations/004_partial_payment_system.sql
mysql -u root -p < database/migrations/005_fix_user_passwords.sql
mysql -u root -p < database/migrations/006_stock_movements.sql
mysql -u root -p < database/seeds/001_seed_data.sql   # idempoten, boleh dijalankan ulang
```

> Keenam file migrasi di atas adalah set lengkap di folder `database/migrations/`.

### 2. Backend

```bash
cd backend
composer install
copy .env.example .env        # Windows — lalu isi DB + JWT + kredensial Odoo
```

Jalankan dev server:

```bash
php -S localhost:8090 -t public public/router.php
```

### 3. Frontend

```bash
cd frontend
npm install
copy .env.example .env
npm run dev
```

Frontend memakai proxy Vite ke `http://localhost:8090` (lihat `frontend/vite.config.js`).
Buka aplikasi di `http://localhost:5173`.

### 4. Odoo custom module (opsional)

Module dashboard POS ada di `odoo_addons/pos_sales_dashboard/`. Tambahkan path tersebut
ke `addons_path` Odoo lalu instal modulnya. Kredensial koneksi MariaDB POS dikonfigurasi
di modul (halaman *Dashboard POS*), sedangkan kredensial API Odoo untuk sinkronisasi
dari backend POS ada di `backend/.env` (`ODOO_API_KEY` / `ODOO_PASSWORD`).

## Sinkronisasi Odoo ↔ POS

- POS → Odoo: setiap transaksi/invoice yang lunas otomatis dibuatkan `account.move` (out_invoice) di Odoo melalui JSON-RPC dengan API key.
- Odoo → POS: modul `pos_sales_dashboard` membaca data penjualan dari MariaDB `pos_db` dan membandingkan dengan `account.move` Odoo. Invoice yang `sync_status = 'synced'` tidak dihitung ganda.
- Sinkronisasi ulang manual untuk invoice pending/failed:
  ```bash
  cd backend
  php cli/sync_odoo.php
  ```

## Project Structure

```
zicare/
├── backend/          # Phalcon PHP REST API (port 8090)
│   ├── app/          # controllers, services, repositories, models, middleware
│   ├── cli/          # tool maintenance: migrasi, seed, sync Odoo
│   └── public/       # entry point + router dev server
├── frontend/         # Vue.js SPA (port 5173)
├── database/         # SQL migrations & seeds
├── odoo_addons/      # Modul Odoo tambahan (pos_sales_dashboard)
└── docs/             # Dokumentasi teknis
```

## Documentation

| Document | Path |
|----------|------|
| System Architecture | [docs/01-system-architecture.md](docs/01-system-architecture.md) |
| Software Design | [docs/02-software-design.md](docs/02-software-design.md) |
| Database Design | [docs/03-database-design.md](docs/03-database-design.md) |
| API Documentation | [docs/04-api-documentation.md](docs/04-api-documentation.md) |
| Technical Documentation | [docs/05-technical-documentation.md](docs/05-technical-documentation.md) |
| User Manual | [docs/06-user-manual.md](docs/06-user-manual.md) |
| Deployment Guide | [docs/07-deployment-guide.md](docs/07-deployment-guide.md) |

## Default Credentials (Development)

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@pos.local | Admin@123 |
| Manager | manager@pos.local | Manager@123 |
| Cashier | cashier@pos.local | Cashier@123 |

## License

Proprietary – Internal Use Only