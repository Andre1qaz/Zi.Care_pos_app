# Analisis Project Zi.CARE POS

## 1. Overview Project

**Nama Project**: Zi.CARE POS (Point of Sales)
**Tujuan**: Aplikasi Point of Sales enterprise-ready dengan integrasi Odoo Accounting
**Tech Stack**:
- Backend: Phalcon PHP 5.x (REST API, port 8090)
- Frontend: Vue.js 3 + Pinia + Vue Router (port 5173)
- Database: MariaDB 10.6+ (pos_db)
- ERP: Odoo 19 (Accounting, port 8069)

---

## 2. Arsitektur Sistem

### 2.1 High Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                            │
│                  Web Browser (Vue.js SPA)                    │
└────────────────────┬────────────────────────────────────────┘
                     │ HTTPS/REST/JSON
┌────────────────────▼────────────────────────────────────────┐
│                  APPLICATION LAYER                          │
│              Phalcon PHP REST API (8090)                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │Controllers│→│Services  │→│Repositories│→│Models   │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
┌───────▼──────┐ ┌──▼──────────┐ └────────────┐
│   DATA LAYER │ │  ERP LAYER  │              │
│  MariaDB     │ │  Odoo ERP   │              │
│  (pos_db)    │ │  (8069)     │              │
└──────────────┘ └─────────────┘              │
                                              │
                    CLI Tools                 │
              (sync_odoo.php, etc.)           │
                                              │
        ┌─────────────────────────────────────┘
        │
        └── Odoo Custom Module (pos_sales_dashboard)
```

### 2.2 Backend Architecture (Clean Architecture)

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│  Controllers (HTTP Request/Response Handling)                │
│  Middleware (Auth, CORS, Logging)                            │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                   APPLICATION LAYER                          │
│  Services (Business Logic & Orchestration)                  │
│  - AuthService, ProductService, TransactionService          │
│  - InvoiceService, OdooSyncService, etc.                    │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                      DOMAIN LAYER                           │
│  Models (Phalcon ORM Entities)                              │
│  Repositories (Data Access Abstraction)                     │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                  INFRASTRUCTURE LAYER                        │
│  Database (MariaDB), Logger (Monolog), Odoo Client         │
│  Config, Helpers (JWT, Response, Invoice Generator)         │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Frontend Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        VIEWS LAYER                           │
│  Pages (Dashboard, Transactions, Products, etc.)           │
│  Components (Reusable UI Components)                         │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                      STATE LAYER                            │
│  Pinia Stores (auth, cart, products, dashboard)             │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                      SERVICES LAYER                          │
│  Axios API Client (HTTP requests with JWT)                  │
│  Vue Router (Navigation & Guards)                           │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                    BACKEND API                               │
│              Phalcon REST API (8090)                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Struktur Project

### 3.1 Backend Structure

```
backend/
├── app/
│   ├── bootstrap/
│   │   └── Application.php          # DI Container & Service Registration
│   ├── config/
│   │   ├── config.php              # App Configuration
│   │   ├── routes.php              # API Route Definitions
│   │   └── services.php            # DI Service Definitions
│   ├── controllers/                # HTTP Request Handlers
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── CustomerController.php
│   │   ├── TransactionController.php
│   │   ├── InvoiceController.php
│   │   ├── OutstandingPaymentController.php
│   │   ├── UserController.php
│   │   ├── AuditLogController.php
│   │   ├── DashboardController.php
│   │   ├── ReportController.php
│   │   └── IntegrationController.php
│   ├── models/                     # Phalcon ORM Entities
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── Invoice.php
│   │   ├── InvoiceDetail.php
│   │   ├── Payment.php
│   │   ├── AuditLog.php
│   │   └── StockMovement.php
│   ├── repositories/               # Data Access Layer
│   │   ├── BaseRepository.php
│   │   ├── UserRepository.php
│   │   ├── ProductRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── CustomerRepository.php
│   │   ├── InvoiceRepository.php
│   │   └── StockMovementRepository.php
│   ├── services/                   # Business Logic Layer
│   │   ├── AuthService.php
│   │   ├── ProductService.php
│   │   ├── CategoryService.php
│   │   ├── CustomerService.php
│   │   ├── TransactionService.php
│   │   ├── PaymentService.php
│   │   ├── InvoiceService.php
│   │   ├── OutstandingPaymentService.php
│   │   ├── DashboardService.php
│   │   ├── ReportService.php
│   │   ├── AuditLogService.php
│   │   └── OdooSyncService.php
│   ├── middleware/                 # Cross-cutting Concerns
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── exceptions/                 # Custom Exceptions
│   │   ├── AppException.php
│   │   ├── ValidationException.php
│   │   └── NotFoundException.php
│   └── helpers/                   # Utility Functions
│       ├── JwtHelper.php
│       ├── ResponseHelper.php
│       └── InvoiceNumberGenerator.php
├── public/
│   └── index.php                   # Entry Point
├── cli/                           # CLI Tools
│   ├── sync_odoo.php              # Odoo Sync Worker
│   ├── run_migration.php          # Database Migration
│   ├── run_seed.php               # Database Seeding
│   ├── fix_passwords.php          # Password Utility
│   ├── list_users.php             # User Listing
│   └── test_login.php             # Auth Testing
├── storage/
│   └── logs/                      # Application Logs
├── composer.json
└── .env.example
```

### 3.2 Frontend Structure

```
frontend/
├── src/
│   ├── assets/                    # Static Assets
│   │   ├── main.css
│   │   └── zicare-logo.jpg
│   ├── components/
│   │   ├── layout/                # Layout Components
│   │   │   └── AppLayout.vue      # Sidebar & Main Layout
│   │   └── common/                # Reusable Components
│   ├── views/                     # Page Components
│   │   ├── auth/
│   │   │   └── LoginView.vue
│   │   ├── dashboard/
│   │   │   └── DashboardView.vue
│   │   ├── products/
│   │   │   ├── ProductListView.vue
│   │   │   └── StockCardView.vue
│   │   ├── categories/
│   │   │   └── CategoryListView.vue
│   │   ├── customers/
│   │   │   ├── CustomerListView.vue
│   │   │   └── CustomerHistoryView.vue
│   │   ├── transactions/
│   │   │   └── TransactionView.vue
│   │   ├── invoices/
│   │   │   ├── InvoiceListView.vue
│   │   │   └── InvoiceDetailView.vue
│   │   ├── outstanding-payments/
│   │   │   └── OutstandingPaymentView.vue
│   │   ├── reports/
│   │   │   └── ReportView.vue
│   │   ├── users/
│   │   │   └── UserListView.vue
│   │   ├── audit/
│   │   │   └── AuditLogView.vue
│   │   └── integration/
│   │       └── IntegrationView.vue
│   ├── stores/                    # Pinia State Management
│   │   └── auth.js
│   ├── services/                  # API Client
│   │   └── api.js                 # Axios Instance
│   ├── router/                    # Vue Router
│   │   └── index.js               # Route Definitions & Guards
│   ├── App.vue                    # Root Component
│   └── main.js                    # Entry Point
├── index.html
├── vite.config.js
├── tailwind.config.js
└── package.json
```

### 3.3 Database Structure

```
database/
├── migrations/
│   ├── 001_initial_schema.sql        # Create all tables
│   ├── 002_add_product_type.sql      # Product type & service status
│   ├── 003_fix_service_stock.sql     # Fix service stock logic
│   ├── 004_partial_payment_system.sql # Partial payment support
│   ├── 005_fix_user_passwords.sql    # Password hash normalization
│   └── 006_stock_movements.sql       # Stock movements table
└── seeds/
    └── 001_seed_data.sql             # Initial data seeding
```

---

## 4. Database Schema

### 4.1 Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐
│    roles    │1     N│    users    │
└──────┬──────┘       └──────┬──────┘
       │                     │
       │                     │1     N
       │               ┌─────▼──────┐
       │               │  invoices  │
       │               └─────┬──────┘
       │                     │
       │              ┌──────┴──────┐
       │              │             │
       │              │1           1│N
       │              │             │
       │         ┌────▼─────┐  ┌────▼─────┐
       │         │customers │  │audit_logs│
       │         └──────────┘  └──────────┘
       │
       │              │1           N│
       │         ┌────▼─────┐  ┌────▼─────┐
       │         │categories│  │payments  │
       │         └────┬─────┘  └──────────┘
       │              │
       │              │1     N
       │         ┌────▼─────┐
       │         │ products │
       │         └────┬─────┘
       │              │
       │              │1     N
       │         ┌────▼─────┐
       │         │invoice_  │
       │         │details   │
       │         └──────────┘
       │
       │         ┌─────────────┐
       │         │odoo_sync_   │
       │         │logs         │
       │         └─────────────┘
       │
       │         ┌─────────────┐
       │         │stock_       │
       │         │movements    │
       │         └─────────────┘
```

### 4.2 Key Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `roles` | User roles | id, name (administrator, manager, cashier) |
| `users` | User accounts | id, name, email, password (bcrypt), role_id |
| `categories` | Product categories | id, category_name, description |
| `products` | Product catalog | id, product_code, product_name, price, stock, category_id, product_type (barang/jasa) |
| `customers` | Customer data | id, customer_name, phone, email, address |
| `invoices` | Sales invoices | id, invoice_number, customer_id, cashier_id, total_amount, payment_status, sync_status |
| `invoice_details` | Invoice line items | id, invoice_id, product_id, quantity, price, subtotal |
| `payments` | Payment records | id, invoice_id, payment_method, provider, amount, payment_status |
| `audit_logs` | Activity tracking | id, user_id, activity, entity_type, entity_id, metadata |
| `odoo_sync_logs` | Odoo sync history | id, invoice_id, sync_type, status, request_payload, response_payload |
| `stock_movements` | Stock movement history | id, product_id, movement_type, quantity, stock_before, stock_after, reason |

---

## 5. Flow Utama Aplikasi

### 5.1 Flow Login & Authentication

```
┌─────────────┐
│   User      │
└──────┬──────┘
       │
       │ 1. Input email & password
       ▼
┌─────────────────────┐
│   LoginView.vue     │
│   (Frontend)        │
└──────┬──────────────┘
       │
       │ 2. POST /api/v1/auth/login
       ▼
┌─────────────────────┐
│  AuthController.php │
│  (Backend)          │
└──────┬──────────────┘
       │
       │ 3. Validate credentials
       ▼
┌─────────────────────┐
│  AuthService.php    │
│  - Verify password  │
│  - Generate JWT     │
└──────┬──────────────┘
       │
       │ 4. Return JWT token & user data
       ▼
┌─────────────────────┐
│  Pinia Auth Store   │
│  - Store token      │
│  - Store user       │
└──────┬──────────────┘
       │
       │ 5. Redirect based on role
       ▼
┌─────────────────────┐
│  Router Guard       │
│  - Admin → /dashboard│
│  - Cashier → /transactions│
└─────────────────────┘
```

### 5.2 Flow Transaksi Penjualan

```
┌─────────────┐
│   Cashier   │
└──────┬──────┘
       │
       │ 1. Open Transaction View
       ▼
┌─────────────────────┐
│ TransactionView.vue │
│ - Load products     │
│ - Load customers    │
└──────┬──────────────┘
       │
       │ 2. Add products to cart
       ▼
┌─────────────────────┐
│  Validate Stock     │
│  - Check availability│
└──────┬──────────────┘
       │
       │ 3. Select customer (optional)
       ▼
┌─────────────────────┐
│  Select Payment     │
│  - Cash / QRIS /    │
│    Transfer / E-Wallet│
└──────┬──────────────┘
       │
       │ 4. Process payment
       ▼
┌─────────────────────┐
│ POST /transactions  │
│ with items & payments│
└──────┬──────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│         TransactionService.php          │
│  ┌───────────────────────────────────┐  │
│  │ 1. Validate items & payments      │  │
│  │ 2. Calculate totals                │  │
│  │ 3. BEGIN DATABASE TRANSACTION      │  │
│  │ 4. Create invoice                  │  │
│  │ 5. Create invoice_details          │  │
│  │ 6. Create payments                 │  │
│  │ 7. Update stock (if barang)        │  │
│  │ 8. Record stock movements         │  │
│  │ 9. Create audit log                │  │
│  │ 10. COMMIT                         │  │
│  └───────────────────────────────────┘  │
└──────┬──────────────────────────────────┘
       │
       │ 5. Queue Odoo Sync (async)
       ▼
┌─────────────────────┐
│ OdooSyncService.php │
│ - spawnBackground   │
│   Sync()            │
└──────┬──────────────┘
       │
       │ 6. Return invoice data
       ▼
┌─────────────────────┐
│  Display Invoice    │
│  - Print option     │
└─────────────────────┘
```

### 5.3 Flow Sinkronisasi Odoo

```
┌─────────────────────┐
│ TransactionService │
│   queueSync()       │
└──────┬──────────────┘
       │
       │ 1. Spawn background worker
       ▼
┌─────────────────────┐
│ sync_odoo.php --id │
│  (CLI Process)     │
└──────┬──────────────┘
       │
       │ 2. Call OdooSyncService
       ▼
┌─────────────────────────────────────────┐
│         OdooSyncService.php            │
│  ┌───────────────────────────────────┐  │
│  │ 1. Load invoice with details      │  │
│  │ 2. Connect to Odoo JSON-RPC       │  │
│  │ 3. Create account.move (invoice)  │  │
│  │ 4. Create account.payment         │  │
│  │ 5. Update sync_status = 'synced' │  │
│  │ 6. Log sync activity              │  │
│  └───────────────────────────────────┘  │
└──────┬──────────────────────────────────┘
       │
       │ 3. If failed → sync_status = 'failed'
       ▼
┌─────────────────────┐
│  Manual Retry       │
│  - CLI: sync_odoo.php│
│  - UI: Retry button  │
└─────────────────────┘
```

### 5.4 Flow Pembayaran Parsial & Outstanding

```
┌─────────────┐
│   Cashier   │
└──────┬──────┘
       │
       │ 1. Create transaction with partial payment
       ▼
┌─────────────────────┐
│ TransactionService │
│ - paid_amount < total_amount│
│ - payment_status = 'partial'│
│ - outstanding_balance > 0   │
└──────┬──────────────┘
       │
       │ 2. Invoice created with partial status
       ▼
┌─────────────────────┐
│  OutstandingPayment │
│  View               │
│  - List invoices    │
│    with balance     │
└──────┬──────────────┘
       │
       │ 3. Add payment to outstanding invoice
       ▼
┌─────────────────────┐
│ POST /payments     │
│ - Update invoice    │
│ - Add payment record│
│ - Recalculate balance│
└──────┬──────────────┘
       │
       │ 4. If fully paid → payment_status = 'paid'
       ▼
┌─────────────────────┐
│  Sync to Odoo       │
│  (if fully paid)    │
└─────────────────────┘
```

### 5.5 Flow Manajemen Stok

```
┌─────────────┐
│   Admin     │
└──────┬──────┘
       │
       │ 1. Add/Update product
       ▼
┌─────────────────────┐
│ ProductController   │
│  (Backend)         │
└──────┬──────────────┘
       │
       │ 2. ProductService
       ▼
┌─────────────────────┐
│  Validate & Save    │
│  - Product data    │
│  - Initial stock   │
└──────┬──────────────┘
       │
       │ 3. Record initial stock
       ▼
┌─────────────────────┐
│ StockMovementRepository│
│ - movement_type = 'in'│
│ - reason = 'Initial stock'│
└──────┬──────────────┘
       │
       │ 4. On sale → movement_type = 'sale'
       ▼
┌─────────────────────┐
│  Stock decreases   │
│  - Stock after = before - qty│
└──────┬──────────────┘
       │
       │ 5. View stock card
       ▼
┌─────────────────────┐
│ StockCardView.vue  │
│  - Show history     │
│  - Current balance  │
└─────────────────────┘
```

---

## 6. Modul-Modul Utama

### 6.1 Authentication Module
- **Backend**: AuthController, AuthService, JwtHelper
- **Frontend**: LoginView, Auth Store
- **Features**: Login, logout, JWT generation, RBAC
- **Roles**: Administrator, Manager, Cashier

### 6.2 Product Management Module
- **Backend**: ProductController, ProductService, ProductRepository
- **Frontend**: ProductListView, StockCardView
- **Features**: CRUD products, stock management, categories
- **Product Types**: Barang (physical), Jasa (service)

### 6.3 Transaction Module
- **Backend**: TransactionController, TransactionService
- **Frontend**: TransactionView
- **Features**: POS checkout, cart management, payment processing
- **Payment Methods**: Cash, QRIS, Transfer, E-Wallet

### 6.4 Invoice Module
- **Backend**: InvoiceController, InvoiceService
- **Frontend**: InvoiceListView, InvoiceDetailView
- **Features**: Invoice generation, PDF export, printing
- **Invoice Format**: INV-YYYYMMDD-XXXX

### 6.5 Payment Module
- **Backend**: PaymentService, OutstandingPaymentController
- **Frontend**: OutstandingPaymentView
- **Features**: Payment processing, partial payments, outstanding balance tracking

### 6.6 Dashboard Module
- **Backend**: DashboardController, DashboardService
- **Frontend**: DashboardView
- **Features**: Real-time KPI, sales charts, top products, payment statistics

### 6.7 Reports Module
- **Backend**: ReportController, ReportService
- **Frontend**: ReportView
- **Features**: Daily sales, monthly sales, product sales, payment reports

### 6.8 Odoo Integration Module
- **Backend**: OdooSyncService, sync_odoo.php CLI
- **Frontend**: IntegrationView
- **Features**: Sync invoices to Odoo Accounting, retry failed syncs, connection testing

### 6.9 Audit Log Module
- **Backend**: AuditLogController, AuditLogService
- **Frontend**: AuditLogView
- **Features**: Activity tracking, user actions, entity changes

### 6.10 Stock Movement Module
- **Backend**: StockMovementRepository
- **Frontend**: StockCardView
- **Features**: Stock history, movement types (sale, in, return, adjustment, opname)

---

## 7. API Endpoints

### 7.1 Authentication
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/me` - Get current user

### 7.2 Products
- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product

### 7.3 Categories
- `GET /api/v1/categories` - List categories
- `POST /api/v1/categories` - Create category
- `PUT /api/v1/categories/{id}` - Update category
- `DELETE /api/v1/categories/{id}` - Delete category

### 7.4 Customers
- `GET /api/v1/customers` - List customers
- `POST /api/v1/customers` - Create customer
- `PUT /api/v1/customers/{id}` - Update customer
- `DELETE /api/v1/customers/{id}` - Delete customer

### 7.5 Transactions
- `POST /api/v1/transactions` - Create transaction

### 7.6 Invoices
- `GET /api/v1/invoices` - List invoices
- `GET /api/v1/invoices/{id}` - Get invoice detail
- `GET /api/v1/invoices/{id}/pdf` - Download PDF

### 7.7 Payments
- `GET /api/v1/outstanding-payments` - List outstanding payments
- `POST /api/v1/payments` - Add payment to invoice

### 7.8 Dashboard
- `GET /api/v1/dashboard` - Get dashboard stats

### 7.9 Reports
- `GET /api/v1/reports/daily-sales` - Daily sales report
- `GET /api/v1/reports/monthly-sales` - Monthly sales report
- `GET /api/v1/reports/product-sales` - Product sales report
- `GET /api/v1/reports/payments` - Payment statistics

### 7.10 Users (Admin Only)
- `GET /api/v1/users` - List users
- `POST /api/v1/users` - Create user
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

### 7.11 Audit Logs
- `GET /api/v1/audit-logs` - List audit logs

### 7.12 Integration
- `GET /api/v1/integration/status` - Check Odoo connection
- `POST /api/v1/integration/sync/{id}` - Manual sync invoice

---

## 8. Security & Authorization

### 8.1 Authentication
- JWT Bearer Token (HS256, 8h expiry)
- Password hashing with bcrypt (cost 12)
- Token refresh mechanism

### 8.2 Authorization (RBAC)
| Role | Products | Customers | Transactions | Dashboard | Reports | Users |
|------|----------|-----------|--------------|-----------|---------|-------|
| Administrator | Full | Full | Full | ✅ | ✅ | ✅ |
| Manager | Read | Read | ❌ | ✅ | ✅ | ❌ |
| Cashier | Read | Full | ✅ | ❌ | ❌ | ❌ |

### 8.3 Security Measures
- HTTPS/TLS 1.2+ required
- Input validation at controller level
- Prepared statements for SQL queries
- CORS middleware
- Rate limiting (configurable)
- Audit logging for all actions

---

## 9. Deployment Architecture

### 9.1 Development Environment
```
┌─────────────────────────────────────────┐
│         Local Machine                   │
│  ┌──────────┐  ┌──────────┐              │
│  │Frontend  │  │Backend   │              │
│  │:5173     │  │:8090     │              │
│  └──────────┘  └──────────┘              │
│       │            │                     │
│       └────────────┼────────────┐        │
│                    │            │        │
│              ┌─────▼─────┐  ┌──▼──────┐ │
│              │  XAMPP    │  │ Odoo    │ │
│              │  MariaDB  │  │ :8069   │ │
│              └───────────┘  └─────────┘ │
└─────────────────────────────────────────┘
```

### 9.2 Production Environment
```
┌─────────────────────────────────────────┐
│           Load Balancer                  │
│              (Nginx)                     │
└──────┬──────────────────────┬───────────┘
       │                      │
┌──────▼──────┐        ┌──────▼──────┐
│   Frontend  │        │   Backend   │
│   (Static)  │        │  (Phalcon)  │
└─────────────┘        └──────┬──────┘
                              │
                    ┌─────────┴─────────┐
                    │                   │
              ┌─────▼─────┐       ┌─────▼─────┐
              │ MariaDB   │       │   Odoo    │
              │ Primary   │       │  Server   │
              └───────────┘       └───────────┘
                    │
              ┌─────▼─────┐
              │ MariaDB   │
              │ Replica   │
              └───────────┘
```

---

## 10. Key Features & Capabilities

### 10.1 Core Features
1. **Multi-role authentication** (Admin, Manager, Cashier)
2. **Real-time POS transactions** with stock validation
3. **Multiple payment methods** (Cash, QRIS, Transfer, E-Wallet)
4. **Partial payment support** with outstanding balance tracking
5. **Invoice generation** with PDF export
6. **Stock management** with movement history (stock card)
7. **Product catalog** with categories
8. **Customer management** with transaction history
9. **Dashboard** with real-time KPIs
10. **Comprehensive reports** (sales, payments, products)

### 10.2 Advanced Features
1. **Odoo Accounting integration** (two-way sync)
2. **Async sync worker** for non-blocking transactions
3. **Retry mechanism** for failed syncs
4. **Audit logging** for compliance
5. **Stock movement tracking** (sale, in, return, adjustment, opname)
6. **Service vs Product** distinction
7. **JWT-based authentication** with refresh

---

## 11. Design Patterns Used

| Pattern | Implementation |
|---------|----------------|
| Repository Pattern | Repositories abstract data access |
| Service Layer Pattern | Services contain business logic |
| Dependency Injection | Phalcon DI container |
| Middleware Pattern | Auth, CORS, logging middleware |
| Observer Pattern | Odoo sync trigger after transaction |
| DTO Pattern | Array-based request/response |
| Factory Pattern | Invoice number generator |

---

## 12. Technology Choices Rationale

| Technology | Reason |
|------------|--------|
| Phalcon PHP | C-extension, high performance for real-time POS |
| Vue.js 3 | Reactive UI, mature ecosystem, fast development |
| Pinia | Modern state management, TypeScript-ready |
| MariaDB | ACID compliance, MySQL compatible, transaction-safe |
| Odoo | Enterprise ERP, mature accounting module |
| JWT | Stateless authentication, mobile-friendly |
| Tailwind CSS | Utility-first CSS, rapid UI development |

---

## 13. Future Enhancements

1. **Mobile App** (React Native / Flutter)
2. **Offline mode** with PWA support
3. **Barcode scanner integration**
4. **Receipt printer integration**
5. **Multi-location support**
6. **Advanced analytics** with data visualization
7. **Customer loyalty program**
8. **Promotions & discounts**
9. **Email notifications** for invoices
10. **Multi-currency support**

---

## 14. Conclusion

Project Zi.CARE POS adalah aplikasi Point of Sales enterprise-ready dengan arsitektur yang solid, clean architecture, dan integrasi Odoo yang handal. Aplikasi ini dirancang untuk skalabilitas, maintainability, dan performance dengan memisahkan concern secara jelas antara layer-layer aplikasi.

Flow aplikasi mencakup:
1. Authentication & Authorization dengan RBAC
2. Transaction processing dengan validasi stok real-time
3. Invoice generation dengan sinkronisasi Odoo async
4. Stock management dengan tracking movement
5. Reporting & Dashboard untuk manajemen
6. Audit logging untuk compliance

Project ini siap untuk deployment dan dapat dikembangkan lebih lanjut sesuai kebutuhan bisnis.
