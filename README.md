# POS Application – Enterprise Point of Sales

Aplikasi Point of Sales (POS) enterprise-ready dengan integrasi Odoo Accounting.

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Phalcon PHP 5.x |
| Frontend | Vue.js 3, Vue Router, Pinia, Axios |
| Database | MariaDB 10.6+ |
| ERP | Odoo 17 (Accounting) |
| API | REST JSON |

## Quick Start

python odoo-bin -r odoo -w admin --db_host=127.0.0.1 --db_port=5432
python odoo-bin -r odoo -w admin --db_host=127.0.0.1 --db_port=5432

### Prerequisites

- PHP 8.1+ with Phalcon extension
- Composer
- Node.js 18+
- MariaDB 10.6+
- Odoo 17 (optional, for ERP sync)

### Backend

```bash
cd backend
composer install
cp .env.example .env
# Edit .env with database credentials
php public/index.php  # or configure nginx/apache
php -S localhost:8080 -t public public/router.php
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

### Database

```bash
mysql -u root -p < database/migrations/001_initial_schema.sql
mysql -u root -p < database/seeds/001_seed_data.sql
```

## Project Structure

```
POS/
├── backend/          # Phalcon PHP REST API
├── frontend/         # Vue.js SPA
├── database/         # SQL migrations & seeds
├── odoo/             # Odoo custom module
└── docs/             # Enterprise documentation
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
| Odoo Integration Guide | [docs/08-odoo-integration-guide.md](docs/08-odoo-integration-guide.md) |
| Odoo Custom Module Guide | [docs/09-odoo-custom-module-guide.md](docs/09-odoo-custom-module-guide.md) |

## Default Credentials (Development)

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@pos.local | Admin@123 |
| Manager | manager@pos.local | Manager@123 |
| Cashier | cashier@pos.local | Cashier@123 |

## License

Proprietary – Internal Use Only

cd "C:\proyek\POS APP\backend\public"
php -S localhost:8080

