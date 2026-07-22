# System Architecture Document

## 1. Executive Summary

Aplikasi POS ini dirancang dengan **Clean Architecture** dan **Layered Architecture** untuk memisahkan concern bisnis, infrastruktur, dan presentasi. Integrasi Odoo Accounting dilakukan secara asynchronous melalui queue-based sync untuk memastikan transaksi POS tetap cepat meskipun Odoo sementara tidak tersedia.

---

## 2. High Level Architecture

```mermaid
flowchart TB
    subgraph Client["Client Layer"]
        Browser["Web Browser"]
        VueSPA["Vue.js SPA"]
    end

    subgraph API["Application Layer"]
        Nginx["Nginx Reverse Proxy"]
        Phalcon["Phalcon PHP REST API"]
    end

    subgraph Data["Data Layer"]
        MariaDB["MariaDB"]
        Redis["Redis Queue/Cache"]
    end

    subgraph ERP["ERP Layer"]
        OdooAPI["Odoo JSON-RPC / REST"]
        OdooDB["PostgreSQL Odoo"]
    end

    Browser --> VueSPA
    VueSPA -->|HTTPS REST JSON| Nginx
    Nginx --> Phalcon
    Phalcon --> MariaDB
    Phalcon --> Redis
    Phalcon -->|Async Sync| OdooAPI
    OdooAPI --> OdooDB
```

### Alasan Pemilihan

| Keputusan | Alasan |
|-----------|--------|
| Phalcon PHP | C-extension, performa tinggi untuk transaksi real-time POS |
| Vue.js SPA | Reactive UI, ecosystem matang (Pinia, Router) |
| MariaDB | ACID compliance, kompatibel MySQL, cocok untuk transaksi |
| Async Odoo Sync | POS tidak blocking saat Odoo down; retry otomatis |
| Redis Queue | Job queue ringan untuk sync invoice/payment |

---

## 3. Backend Architecture

```mermaid
flowchart LR
    subgraph Presentation
        Controllers["Controllers"]
        Middleware["Middleware"]
    end

    subgraph Application
        Services["Services"]
        Validators["Validators"]
    end

    subgraph Domain
        Models["Models"]
        Repositories["Repositories"]
    end

    subgraph Infrastructure
        DB["MariaDB"]
        Logger["Monolog"]
        OdooClient["Odoo Client"]
    end

    Controllers --> Middleware
    Middleware --> Controllers
    Controllers --> Validators
    Controllers --> Services
    Services --> Repositories
    Repositories --> Models
    Models --> DB
    Services --> OdooClient
    Services --> Logger
```

### Layer Responsibilities

| Layer | Folder | Tanggung Jawab |
|-------|--------|----------------|
| Presentation | `controllers/`, `middleware/` | HTTP request/response, auth gate |
| Application | `services/`, `validators/` | Business logic, orchestration |
| Domain | `models/`, `repositories/` | Data access abstraction |
| Infrastructure | `config/`, `helpers/` | External systems, logging |

### Keuntungan Clean Architecture

1. **Testability** – Services dapat di-unit test tanpa HTTP
2. **Maintainability** – Perubahan DB tidak mempengaruhi controller
3. **Scalability** – Service layer dapat di-extract ke microservice di masa depan

---

## 4. Frontend Architecture

```mermaid
flowchart TB
    subgraph Views
        Pages["Pages/Views"]
        Components["Reusable Components"]
    end

    subgraph State
        Pinia["Pinia Stores"]
    end

    subgraph Services
        Axios["Axios API Client"]
        Router["Vue Router"]
    end

    Pages --> Components
    Pages --> Pinia
    Pinia --> Axios
    Router --> Pages
    Axios -->|JWT Bearer| API["Backend API"]
```

### State Management (Pinia)

| Store | State | Actions |
|-------|-------|---------|
| `auth` | user, token, role | login, logout, refresh |
| `cart` | items, customer | addItem, removeItem, clear |
| `products` | list, filters | fetch, search |
| `dashboard` | metrics | fetchStats |

---

## 5. Database Architecture

```mermaid
erDiagram
    users ||--o{ invoices : "cashier"
    users ||--o{ audit_logs : "performs"
    customers ||--o{ invoices : "has"
    categories ||--o{ products : "contains"
    invoices ||--|{ invoice_details : "contains"
    invoices ||--o{ payments : "has"
    products ||--o{ invoice_details : "sold_in"

    users {
        int id PK
        string name
        string email
        string password
        enum role
    }

    products {
        int id PK
        string product_code
        string product_name
        decimal price
        int stock
        int category_id FK
    }

    invoices {
        int id PK
        string invoice_number
        int customer_id FK
        int cashier_id FK
        decimal total_amount
        enum payment_status
        enum sync_status
    }
```

**Normalisasi:** Database menggunakan **3NF (Third Normal Form)** – tidak ada transitive dependency, setiap non-key attribute dependen hanya pada primary key.

---

## 6. Odoo Integration Architecture

```mermaid
sequenceDiagram
    participant POS as POS Backend
    participant Queue as Sync Queue
    participant Worker as Sync Worker
    participant Odoo as Odoo ERP

    POS->>POS: Complete Transaction
    POS->>POS: Create Invoice (local)
    POS->>Queue: Enqueue sync job
    POS-->>Client: Return invoice (immediate)

    Worker->>Queue: Dequeue job
    Worker->>Odoo: POST account.move (invoice)
    Odoo-->>Worker: move_id
    Worker->>Odoo: POST account.payment
    Odoo-->>Worker: payment_id
    Worker->>POS: Update sync_status = synced
```

### Sync Strategy

| Status | Meaning |
|--------|---------|
| `pending` | Belum dikirim ke Odoo |
| `syncing` | Sedang proses sync |
| `synced` | Berhasil di Odoo |
| `failed` | Gagal, akan retry |
| `manual_review` | Gagal setelah max retry |

---

## 7. API Architecture

- **Style:** RESTful JSON
- **Versioning:** `/api/v1/...`
- **Auth:** JWT Bearer Token
- **Response Format:**

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "meta": { "page": 1, "total": 100 }
}
```

---

## 8. Deployment Architecture

```mermaid
flowchart TB
    subgraph Production
        LB["Load Balancer / Nginx"]
        App1["Phalcon App 1"]
        App2["Phalcon App 2"]
        Vue["Vue Static Files"]
        DB["MariaDB Primary"]
        DBR["MariaDB Replica"]
        RedisP["Redis"]
        OdooS["Odoo Server"]
    end

    Users["Users"] --> LB
    LB --> Vue
    LB --> App1
    LB --> App2
    App1 --> DB
    App2 --> DB
    DB --> DBR
    App1 --> RedisP
    App2 --> RedisP
    App1 --> OdooS
```

---

## 9. Component Diagram

```mermaid
flowchart TB
    subgraph POS System
        AuthMod["Auth Module"]
        ProductMod["Product Module"]
        CustomerMod["Customer Module"]
        TransactionMod["Transaction Module"]
        PaymentMod["Payment Module"]
        InvoiceMod["Invoice Module"]
        ReportMod["Report Module"]
        DashboardMod["Dashboard Module"]
        OdooSyncMod["Odoo Sync Module"]
    end

    TransactionMod --> ProductMod
    TransactionMod --> CustomerMod
    TransactionMod --> PaymentMod
    TransactionMod --> InvoiceMod
    InvoiceMod --> OdooSyncMod
    ReportMod --> InvoiceMod
    DashboardMod --> InvoiceMod
    AuthMod --> ProductMod
    AuthMod --> TransactionMod
```

---

## 10. Data Flow Diagram – Sales Transaction

```mermaid
flowchart LR
    A["Select Products"] --> B["Add to Cart"]
    B --> C["Set Quantity"]
    C --> D["Calculate Subtotal"]
    D --> E["Calculate Total"]
    E --> F["Select Payment Method"]
    F --> G{"Cash or Non-Cash?"}
    G -->|Cash| H["Input Amount Received"]
    G -->|Non-Cash| I["Input Provider & Reference"]
    H --> J["Calculate Change"]
    I --> J
    J --> K["Save Transaction DB"]
    K --> L["Generate Invoice"]
    L --> M["Update Stock"]
    M --> N["Queue Odoo Sync"]
    N --> O["Return Invoice to Client"]
```

---

## 11. Security Architecture

| Layer | Mechanism |
|-------|-----------|
| Transport | HTTPS/TLS 1.2+ |
| Authentication | JWT (HS256, 8h expiry) |
| Authorization | RBAC (admin, manager, cashier) |
| Input | Validator layer + prepared statements |
| Password | bcrypt (cost 12) |
| Audit | audit_logs table |
| API | Rate limiting via middleware |

---

## 12. Scalability Considerations

1. **Horizontal scaling** – Stateless API behind load balancer
2. **Read replica** – Reports query replica DB
3. **Caching** – Product catalog cached in Redis (TTL 5 min)
4. **Async sync** – Odoo integration tidak blocking checkout
5. **Indexed queries** – Composite index on `invoices(created_at, payment_status)`
