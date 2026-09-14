# Software Design Document (SDD)

## 1. Purpose

Dokumen ini menjelaskan desain perangkat lunak aplikasi POS untuk developer, mahasiswa magang, dan stakeholder teknis.

---

## 2. Module Overview

| Module | Description | Primary Actors |
|--------|-------------|----------------|
| Auth | Login, logout, JWT, RBAC | All users |
| Products | CRUD produk, stock | Admin |
| Categories | CRUD kategori | Admin |
| Customers | CRUD pelanggan | Admin, Cashier |
| Transactions | Checkout flow | Cashier |
| Payments | Cash & non-cash | Cashier |
| Invoices | Generate, print, PDF | Cashier, Manager |
| Dashboard | KPI real-time | Manager, Admin |
| Reports | Sales & payment reports | Manager, Admin |
| Odoo Sync | ERP integration | System |

---

## 3. Backend Folder Structure

```
backend/
├── app/
│   ├── bootstrap/
│   │   └── Application.php       # DI container, service registration
│   ├── config/
│   │   ├── config.php            # App configuration
│   │   ├── routes.php            # API route definitions
│   │   └── services.php          # DI service definitions
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── CustomerController.php
│   │   ├── TransactionController.php
│   │   ├── PaymentController.php
│   │   ├── InvoiceController.php
│   │   ├── DashboardController.php
│   │   └── ReportController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── Invoice.php
│   │   ├── InvoiceDetail.php
│   │   ├── Payment.php
│   │   └── AuditLog.php
│   ├── repositories/
│   │   ├── BaseRepository.php
│   │   ├── UserRepository.php
│   │   ├── ProductRepository.php
│   │   ├── InvoiceRepository.php
│   │   └── ...
│   ├── services/
│   │   ├── AuthService.php
│   │   ├── ProductService.php
│   │   ├── TransactionService.php
│   │   ├── PaymentService.php
│   │   ├── InvoiceService.php
│   │   ├── DashboardService.php
│   │   ├── ReportService.php
│   │   └── OdooSyncService.php
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── validators/
│   │   ├── ProductValidator.php
│   │   ├── CustomerValidator.php
│   │   └── TransactionValidator.php
│   ├── exceptions/
│   │   ├── AppException.php
│   │   ├── ValidationException.php
│   │   └── NotFoundException.php
│   └── helpers/
│       ├── JwtHelper.php
│       ├── ResponseHelper.php
│       └── InvoiceNumberGenerator.php
├── public/
│   └── index.php                   # Entry point
├── storage/
│   └── logs/                       # Application logs
├── composer.json
└── .env.example
```

### Fungsi Setiap Folder

| Folder | Fungsi |
|--------|--------|
| `bootstrap/` | Inisialisasi aplikasi, dependency injection |
| `config/` | Konfigurasi app, routes, services |
| `controllers/` | Handle HTTP request, delegate ke service |
| `models/` | Phalcon ORM models (entity mapping) |
| `repositories/` | Data access layer, complex queries |
| `services/` | Business logic, transaction orchestration |
| `middleware/` | Cross-cutting: auth, CORS, logging |
| `validators/` | Input validation rules |
| `exceptions/` | Custom exception hierarchy |
| `helpers/` | Utility functions |

---

## 4. Frontend Folder Structure

```
frontend/
├── src/
│   ├── assets/              # CSS, images, fonts
│   ├── components/
│   │   ├── layout/          # Sidebar, Navbar, Footer
│   │   ├── common/          # Button, Modal, Table, Pagination
│   │   └── invoice/         # InvoicePrint, InvoicePDF
│   ├── views/
│   │   ├── auth/LoginView.vue
│   │   ├── dashboard/DashboardView.vue
│   │   ├── products/
│   │   ├── categories/
│   │   ├── customers/
│   │   ├── transactions/
│   │   ├── payments/
│   │   ├── invoices/
│   │   └── reports/
│   ├── stores/
│   │   ├── auth.js
│   │   ├── cart.js
│   │   ├── products.js
│   │   └── dashboard.js
│   ├── services/
│   │   └── api.js           # Axios instance + interceptors
│   ├── router/
│   │   └── index.js         # Routes + guards
│   ├── utils/
│   │   ├── formatters.js
│   │   └── permissions.js
│   ├── App.vue
│   └── main.js
├── index.html
├── vite.config.js
└── package.json
```

---

## 5. Design Patterns Used

| Pattern | Usage |
|---------|-------|
| Repository | Abstract database queries |
| Service Layer | Encapsulate business rules |
| Dependency Injection | Phalcon DI container |
| Middleware Chain | Auth, role check |
| DTO (array) | API request/response structure |
| Observer (Odoo) | Post-transaction sync trigger |

---

## 6. Transaction Business Process

### Step-by-Step Flow

1. **Cashier login** – JWT issued, role = cashier
2. **Open POS screen** – Load active products (cached)
3. **Select customer** – Optional; default walk-in customer
4. **Add products to cart** – Validate stock availability
5. **Adjust quantity** – Recalculate line subtotals
6. **Review total** – Sum of all line subtotals
7. **Choose payment method** – cash | qris | transfer | ewallet
8. **Process payment:**
   - Cash: validate `paid_amount >= total_amount`, compute change
   - Non-cash: capture provider, reference, set status pending/paid
9. **DB transaction (atomic):**
   - Insert invoice
   - Insert invoice_details
   - Insert payment(s)
   - Decrement product stock
   - Insert audit_log
10. **Generate invoice number** – Format: `INV-YYYYMMDD-XXXX`
11. **Queue Odoo sync job**
12. **Return invoice data** to frontend for display/print

### Database Transaction Boundary

Semua operasi step 9 dibungkus dalam `BEGIN ... COMMIT`. Jika gagal, `ROLLBACK` – tidak ada invoice partial.

---

## 7. Payment System Design

### Cash Payment

```
Input:  total_amount, paid_amount
Output: change_amount = paid_amount - total_amount
Rule:   paid_amount >= total_amount
Status: paid (immediate)
```

### Non-Cash Payment

| Method | Provider Examples | Required Fields |
|--------|-------------------|-----------------|
| QRIS | GoPay, OVO, Dana | provider, reference |
| Transfer | BCA, Mandiri | provider, reference |
| E-Wallet | ShopeePay | provider, reference |

**Status lifecycle:**

```
pending → paid (confirmed)
pending → failed
pending → cancelled
```

---

## 8. Invoice Generation Process

1. Transaction committed → invoice record exists
2. `InvoiceService::generateNumber()` – sequential daily counter
3. `InvoiceService::buildPayload()` – aggregate header + lines + payment
4. Frontend renders HTML template (`InvoiceView.vue`)
5. Print: `window.print()` on styled HTML
6. PDF: backend endpoint `/api/v1/invoices/{id}/pdf` using Dompdf

### Invoice Content

- Invoice Number, Date
- Cashier Name, Customer Name
- Product table (name, qty, unit price, subtotal)
- Total, Payment Method, Payment Status, Change (if cash)

---

## 9. Role-Based Access Control

| Resource | Admin | Manager | Cashier |
|----------|-------|---------|---------|
| Users CRUD | ✅ | ❌ | ❌ |
| Products CRUD | ✅ | ❌ | Read |
| Categories CRUD | ✅ | ❌ | Read |
| Customers CRUD | ✅ | ❌ | ✅ |
| Transactions | ✅ | ❌ | ✅ |
| Invoices | ✅ | Read | ✅ |
| Dashboard | ✅ | ✅ | ❌ |
| Reports | ✅ | ✅ | ❌ |
| Odoo Sync Admin | ✅ | ❌ | ❌ |

Implementation: `RoleMiddleware` checks `$user->role` against allowed roles per route.

---

## 10. Error Handling Strategy

```php
try {
    // business logic
} catch (ValidationException $e) {
    return 422 with errors array
} catch (NotFoundException $e) {
    return 404
} catch (AppException $e) {
    return 400 with message
} catch (\Exception $e) {
    log error
    return 500 generic message
}
```

---

## 11. Logging Strategy

| Level | Usage |
|-------|-------|
| INFO | Login, transaction complete, sync success |
| WARNING | Sync retry, low stock |
| ERROR | Sync failure, unhandled exception |
| DEBUG | Development only |

Log format: `[datetime] [level] [user_id] message {context}`
