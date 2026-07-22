# API Documentation

Base URL: `http://localhost:8080/api/v1`

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

### POST /auth/login

**Request:**
```json
{
  "email": "cashier@pos.local",
  "password": "Cashier@123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "user": {
      "id": 3,
      "name": "Cashier",
      "email": "cashier@pos.local",
      "role": "cashier"
    }
  }
}
```

**Validation:**
- `email`: required, valid email
- `password`: required, min 6 chars

### POST /auth/logout

**Headers:** Authorization required

**Response:** `{ "success": true, "message": "Logout successful" }`

### GET /auth/me

**Response:** Current user profile

---

## Products Module

### GET /products

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| search | string | Search by name/code |
| category_id | int | Filter by category |
| low_stock | int | Filter stock <= value |
| page | int | Page number (default 1) |
| limit | int | Items per page (default 20) |

### POST /products

**Request:**
```json
{
  "product_code": "PRD-006",
  "product_name": "Es Jeruk",
  "description": "Es jeruk segar",
  "price": 8000,
  "stock": 100,
  "category_id": 2
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
{ "category_name": "Dessert", "description": "Makanan penutup" }
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
- `base_url`: http://localhost:8080/api/v1
- `token`: (set after login)
