# API Documentation

Base URL: `http://localhost:8090/api/v1`

## Authentication

Semua endpoint (kecuali login) memerlukan header:

```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

---

## Response Format

### Success

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "meta": { "page": 1, "limit": 20, "total": 100 }
}
```

### Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": "Email is required"
  }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

## Auth Module

Endpoint publik (tanpa token): `POST /auth/login`, `POST /auth/verify-2fa`,
`POST /auth/forgot-password`, `POST /auth/reset-password`, `GET /auth/status`.
Seluruh endpoint lain memerlukan `Authorization: Bearer <token>`.

### POST /auth/login

**Request:**
```json
{
  "email": "cashier@pos.local",
  "password": "Cashier@123",
  "remember": false
}
```

`remember` (opsional, default `false`): bila `true`, token umur panjang
(~30 hari, `JWT_REMEMBER_EXPIRY`) diterbitkan dan disimpan klien di localStorage;
bila `false`, token sesi (8 jam) diterbitkan.

**Response (sukses, tanpa 2FA):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "expires_in": 28800,
    "remember": false,
    "user": {
      "id": 3,
      "name": "Cashier",
      "email": "cashier@pos.local",
      "role": "cashier"
    },
    "last_login": {
      "at": "2026-01-10 08:15:00",
      "ip": "127.0.0.1",
      "user_agent": "Mozilla/5.0 ...",
      "first_time": false
    }
  }
}
```

`last_login` berisi info login *sebelum* yang sekarang (untuk ditampilkan ke
user). `first_time: true` bila ini login pertama.

**Response (2FA aktif — tahap satu):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "requires_2fa": true,
    "challenge_token": "eyJhbGciOiJIUzI1NiIs...",
    "message": "Masukkan kode 6 digit dari aplikasi authenticator Anda."
  }
}
```

**Error responses:**

| Status | Kondisi | `errors` |
|--------|---------|----------|
| 401 | Email/password salah | `{ "attempts_remaining": 3 }` |
| 423 | Akun dikunci (terlalu banyak percobaan gagal) | — |
| 503 | Mode pemeliharaan (`APP_MAINTENANCE=true`) | — |

**Validation:**
- `email`: required, valid email
- `password`: required, min 6 chars

### POST /auth/verify-2fa

Tahap kedua login ketika Two-Factor Authentication aktif.

**Request:**
```json
{ "challenge_token": "eyJhbGciOiJIUzI1NiIs...", "code": "123456" }
```

**Response:** struktur sama dengan login sukses (`token`, `user`, `last_login`).

### GET /auth/status

**Publik.** Status sistem untuk halaman login (skeleton loading, banner
pemeliharaan, versi & environment indicator).

```json
{
  "success": true,
  "data": {
    "app_name": "Zi.CARE POS",
    "version": "1.0.0",
    "environment": "development",
    "maintenance": false,
    "two_factor_available": true
  }
}
```

### POST /auth/refresh

**Headers:** Authorization required.

**Request:** `{ "remember": true }`

**Response:** `{ "token": "...", "expires_in": 28800, "user": { ... } }`

Dipakai peringatan *session timeout* untuk memperpanjang sesi yang masih aktif.

### POST /auth/forgot-password

**Request:** `{ "email": "admin@pos.local" }`

**Response:** selalu sukses (anti user-enumeration):

```json
{
  "success": true,
  "message": "Jika email terdaftar, kami telah membuat tautan reset password.",
  "data": { "expires_in": 3600, "reset_token": "...", "reset_url": "..." }
}
```

`reset_token`/`reset_url` hanya dikembalikan saat `APP_DEBUG=true` (tanpa
server email). Tautan reset juga dicatat ke `LOG_PATH`.

### POST /auth/reset-password

**Request:** `{ "token": "<reset_token>", "password": "<password baru>" }`

**Response:** `{ "success": true, "message": "Password berhasil diubah..." }`

**Validation (422):** password minimal 8 karakter dan harus memuat huruf + angka.
Token yang tidak valid/kedaluwarsa → `422` dengan pesan "Tautan reset tidak valid atau sudah kedaluwarsa".

### POST /auth/2fa/setup

**Headers:** Authorization required.

**Response:**
```json
{
  "success": true,
  "data": {
    "secret": "GEZDGNBVGY3TQOJQ...",
    "otpauth_url": "otpauth://totp/Zi.CARE%20POS%3Aadmin%40pos.local?...",
    "issuer": "Zi.CARE POS"
  }
}
```

Membuat rahasia TOTP baru (belum aktif). Pindai `otpauth_url` di aplikasi
authenticator (Google Authenticator, Authy, dll) atau masukkan `secret` manual.

### POST /auth/2fa/enable

**Headers:** Authorization required.

**Request:** `{ "code": "123456" }` — kode TOTP yang valid untuk memverifikasi setup.

### POST /auth/2fa/disable

**Headers:** Authorization required.

**Request:** `{ "code": "123456" }` — kode TOTP, **atau** password akun.

### POST /auth/logout

**Headers:** Authorization required

**Response:** `{ "success": true, "message": "Logout successful" }`

### GET /auth/me

**Response:** Current user profile (termasuk `two_factor_enabled` dan `last_login_at`)

---

## Products Module

### GET /products

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| search | string | Search by name/code |
| category_id | int | Filter by category |
| product_type | string | Filter by type: `barang` or `jasa` |
| service_status | string | Filter jasa by status: `tersedia` or `tidak_tersedia` |
| low_stock | int | Filter stock <= value |
| page | int | Page number (default 1) |
| limit | int | Items per page (default 20) |

### POST /products

**Request:**
```json
{
  "product_code": "PRD-006",
  "product_name": "Stetoskop Anak",
  "description": "Stetoskop akustik ukuran anak",
  "price": 230000,
  "stock": 100,
  "category_id": 1
}
```

### PUT /products/{id}

### DELETE /products/{id}

Soft delete (sets is_active = 0)

---

## Categories Module

### GET /categories
### GET /categories/{id}
### POST /categories

```json
{ "category_name": "Alat Diagnostik", "description": "Peralatan untuk pemeriksaan & diagnostik pasien" }
```

### PUT /categories/{id}
### DELETE /categories/{id}

---

## Customers Module

### GET /customers?search=budi&page=1&limit=20
### POST /customers

```json
{
  "customer_name": "John Doe",
  "phone": "08123456789",
  "email": "john@email.com",
  "address": "Jl. Example No. 1"
}
```

---

## Transactions Module

### POST /transactions

**Request (Cash):**
```json
{
  "customer_id": 2,
  "payment_method": "cash",
  "paid_amount": 100000,
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```

**Request (Non-Cash QRIS):**
```json
{
  "customer_id": null,
  "payment_method": "qris",
  "paid_amount": 55000,
  "provider": "GoPay",
  "payment_reference": "QRIS-20240618-001",
  "payment_status": "paid",
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Transaction completed successfully",
  "data": {
    "invoice": {
      "id": 1,
      "invoice_number": "INV-20240618-0001",
      "total_amount": 55000,
      "paid_amount": 100000,
      "change_amount": 45000,
      "payment_status": "paid",
      "sync_status": "pending"
    },
    "details": [...],
    "payments": [...],
    "cashier": { "name": "Cashier" },
    "customer": { "customer_name": "Budi Santoso" }
  }
}
```

**Validation:**
- `items`: required, min 1 item
- `payment_method`: cash | qris | transfer | ewallet
- Cash: `paid_amount >= total`
- Non-cash: `provider` and `payment_reference` required

---

## Invoices Module

### GET /invoices?date_from=2024-06-01&date_to=2024-06-30
### GET /invoices/{id}
### GET /invoices/{id}/pdf

Returns PDF binary (Content-Type: application/pdf)

---

## Dashboard Module

### GET /dashboard

**Response:**
```json
{
  "data": {
    "total_sales_today": 1500000,
    "total_transactions": 25,
    "total_invoices": 25,
    "total_revenue": 1500000,
    "monthly_revenue": 45000000,
    "top_selling_products": [...],
    "payment_statistics": [...],
    "monthly_revenue_chart": [...]
  }
}
```

---

## Reports Module

### GET /reports/daily-sales?date=2024-06-18
### GET /reports/monthly-sales?year=2024&month=6
### GET /reports/product-sales?date_from=2024-06-01&date_to=2024-06-30
### GET /reports/payments?date_from=2024-06-01&date_to=2024-06-30
### GET /reports/customer-transactions?customer_id=2&date_from=2024-06-01&date_to=2024-06-30

---

## Users Module (Admin Only)

### GET /users
### POST /users

```json
{
  "name": "New Cashier",
  "email": "newcashier@pos.local",
  "password": "SecurePass123",
  "role_id": 3
}
```

Role IDs: 1=administrator, 2=manager, 3=cashier

---

## Postman Collection

Import file: `docs/postman/POS-API.postman_collection.json` (create if needed)

Environment variables:
- `base_url`: http://localhost:8090/api/v1
- `token`: (set after login)
