# Flow Diagrams - Zi.CARE POS

## 1. Overall System Flow

```mermaid
flowchart TB
    subgraph Client["Client Layer"]
        Browser["Web Browser"]
        VueSPA["Vue.js SPA<br/>Port 5173"]
    end

    subgraph API["Application Layer"]
        Nginx["Nginx/Proxy"]
        Phalcon["Phalcon PHP REST API<br/>Port 8090"]
        Controllers["Controllers"]
        Services["Services"]
        Repositories["Repositories"]
        Models["Models"]
    end

    subgraph Data["Data Layer"]
        MariaDB["MariaDB<br/>pos_db"]
    end

    subgraph ERP["ERP Layer"]
        OdooAPI["Odoo JSON-RPC<br/>Port 8069"]
        OdooDB["PostgreSQL<br/>Odoo DB"]
        OdooModule["Custom Module<br/>pos_sales_dashboard"]
    end

    subgraph CLI["CLI Tools"]
        SyncWorker["sync_odoo.php"]
        Migration["run_migration.php"]
        Seed["run_seed.php"]
    end

    Browser --> VueSPA
    VueSPA -->|HTTPS/REST/JSON| Nginx
    Nginx --> Phalcon
    Phalcon --> Controllers
    Controllers --> Services
    Services --> Repositories
    Repositories --> Models
    Models --> MariaDB
    Services -->|"JSON-RPC"| OdooAPI
    OdooAPI --> OdooDB
    OdooModule --> MariaDB
    OdooModule --> OdooDB
    Services -->|"Async"| SyncWorker
    SyncWorker --> OdooAPI
    CLI --> MariaDB
```

---

## 2. Authentication Flow

```mermaid
sequenceDiagram
    participant User
    participant Frontend as Vue.js Frontend
    participant Router as Vue Router
    participant AuthStore as Pinia Auth Store
    participant API as Backend API
    participant AuthController as AuthController
    participant AuthService as AuthService
    participant DB as MariaDB

    User->>Frontend: Input email & password
    Frontend->>AuthStore: login(email, password)
    AuthStore->>API: POST /api/v1/auth/login
    API->>AuthController: handle request
    AuthController->>AuthService: authenticate(email, password)
    AuthService->>DB: Query user by email
    DB-->>AuthService: User record
    AuthService->>AuthService: Verify password (bcrypt)
    AuthService->>AuthService: Generate JWT token
    AuthService-->>AuthController: Return token & user data
    AuthController-->>API: JSON response with token
    API-->>AuthStore: { token, user }
    AuthStore->>AuthStore: Store token in localStorage
    AuthStore->>AuthStore: Store user in localStorage
    AuthStore-->>Frontend: Login success
    Frontend->>Router: Navigate based on role
    Router->>Router: Check user role
    alt Role = Administrator/Manager
        Router->>Frontend: Redirect to /dashboard
    else Role = Cashier
        Router->>Frontend: Redirect to /transactions
    end
```

---

## 3. Transaction Flow

```mermaid
sequenceDiagram
    participant Cashier
    participant Frontend as TransactionView.vue
    participant API as Backend API
    participant TransactionService as TransactionService
    participant ProductRepo as ProductRepository
    participant StockRepo as StockMovementRepository
    participant DB as MariaDB
    participant OdooSync as OdooSyncService
    participant OdooWorker as sync_odoo.php

    Cashier->>Frontend: Add products to cart
    Frontend->>Frontend: Validate stock availability
    Cashier->>Frontend: Select customer (optional)
    Cashier->>Frontend: Select payment method
    Cashier->>Frontend: Input payment amount
    Frontend->>API: POST /api/v1/transactions
    API->>TransactionService: create(data, cashierId)

    TransactionService->>TransactionService: Validate items & payments
    TransactionService->>ProductRepo: Find products
    ProductRepo-->>TransactionService: Product data
    TransactionService->>TransactionService: Check stock availability
    TransactionService->>TransactionService: Calculate totals

    TransactionService->>DB: BEGIN TRANSACTION

    TransactionService->>DB: Create invoice record
    TransactionService->>DB: Create invoice_details records
    TransactionService->>DB: Create payment records

    loop For each item (barang type)
        TransactionService->>ProductRepo: Decrement stock
        TransactionService->>StockRepo: Record stock movement
        StockRepo->>DB: Insert stock_movement record
    end

    TransactionService->>DB: Create audit_log
    TransactionService->>DB: COMMIT TRANSACTION

    TransactionService->>OdooSync: queueSync(invoiceId)
    OdooSync->>OdooWorker: Spawn background process
    OdooWorker->>OdooWorker: Execute sync_odoo.php --id={invoiceId}

    TransactionService-->>API: Return invoice data
    API-->>Frontend: JSON response with invoice
    Frontend->>Cashier: Display invoice & print option

    Note over OdooWorker: Async process (non-blocking)
    OdooWorker->>OdooSync: syncInvoice(invoiceId)
    OdooSync->>OdooSync: Connect to Odoo JSON-RPC
    OdooSync->>OdooSync: Create account.move
    OdooSync->>OdooSync: Create account.payment
    OdooSync->>DB: Update sync_status = 'synced'
    OdooSync->>DB: Log sync activity
```

---

## 4. Odoo Sync Flow

```mermaid
flowchart TB
    Start([Transaction Completed]) --> InvoiceCreated[Invoice Created<br/>sync_status = 'pending']
    InvoiceCreated --> QueueSync[Queue Odoo Sync]
    QueueSync --> SpawnWorker[Spawn Background Worker]

    SpawnWorker --> CLIProcess[sync_odoo.php --id={invoiceId}]
    CLIProcess --> LoadInvoice[Load Invoice with Details]
    LoadInvoice --> ConnectOdoo[Connect to Odoo JSON-RPC]

    ConnectOdoo --> AuthOdoo[Authenticate with API Key]
    AuthOdoo --> CreateMove[Create account.move<br/>(Invoice)]
    CreateMove --> CreatePayment[Create account.payment]

    CreatePayment --> SyncSuccess{Sync Successful?}

    SyncSuccess -->|Yes| UpdateSynced[Update sync_status = 'synced'<br/>Save odoo_move_id]
    SyncSuccess -->|No| UpdateFailed[Update sync_status = 'failed'<br/>Log error]

    UpdateSynced --> LogSuccess[Log success in odoo_sync_logs]
    UpdateFailed --> LogError[Log error in odoo_sync_logs]

    LogSuccess --> End([Sync Complete])
    LogError --> RetryNeeded[Retry Available]

    RetryNeeded --> ManualRetry{Manual Retry?}
    ManualRetry -->|Yes| CLIProcess
    ManualRetry -->|No| End
```

---

## 5. Payment Flow (Partial & Outstanding)

```mermaid
flowchart TB
    Start([Transaction Initiated]) --> SelectProducts[Select Products]
    SelectProducts --> CalculateTotal[Calculate Total Amount]
    CalculateTotal --> SelectPayment[Select Payment Method]

    SelectPayment --> PaymentType{Payment Type?}

    PaymentType -->|Cash| InputCash[Input Cash Amount]
    PaymentType -->|Non-Cash| InputProvider[Input Provider & Reference]

    InputCash --> ValidateCash{Paid >= Total?}
    InputProvider --> SetPending[Set payment_status = 'pending']

    ValidateCash -->|Yes| CalculateChange[Calculate Change]
    ValidateCash -->|No| ErrorInsufficient[Error: Insufficient Amount]

    CalculateChange --> SetPaid[Set payment_status = 'paid']
    SetPending --> CreatePayment[Create Payment Record]

    SetPaid --> CreatePayment
    CreatePayment --> CreateInvoice[Create Invoice]

    CreateInvoice --> CheckPartial{Paid < Total?}

    CheckPartial -->|Yes| SetPartial[Set payment_status = 'partial'<br/>outstanding_balance > 0]
    CheckPartial -->|No| SetFullPaid[Set payment_status = 'paid'<br/>outstanding_balance = 0]

    SetPartial --> SaveInvoice[Save Invoice]
    SetFullPaid --> SaveInvoice

    SaveInvoice --> StockUpdate[Update Stock]
    StockUpdate --> QueueOdoo[Queue Odoo Sync]

    QueueOdoo --> CheckSync{Fully Paid?}
    CheckSync -->|Yes| SyncOdoo[Sync to Odoo]
    CheckSync -->|No| SkipSync[Skip Odoo Sync]

    SyncOdoo --> End([Transaction Complete])
    SkipSync --> End

    ErrorInsufficient --> End
```

---

## 6. Outstanding Payment Flow

```mermaid
sequenceDiagram
    participant Cashier
    participant Frontend as OutstandingPaymentView.vue
    participant API as Backend API
    participant PaymentService as PaymentService
    participant InvoiceRepo as InvoiceRepository
    participant DB as MariaDB
    participant OdooSync as OdooSyncService

    Cashier->>Frontend: Open Outstanding Payments
    Frontend->>API: GET /api/v1/outstanding-payments
    API->>InvoiceRepo: Find invoices with outstanding_balance > 0
    InvoiceRepo-->>API: List of invoices
    API-->>Frontend: JSON response
    Frontend->>Cashier: Display outstanding invoices

    Cashier->>Frontend: Select invoice to pay
    Cashier->>Frontend: Input payment amount
    Frontend->>API: POST /api/v1/payments
    API->>PaymentService: addPayment(data)

    PaymentService->>DB: BEGIN TRANSACTION
    PaymentService->>DB: Create payment record
    PaymentService->>DB: Update invoice.paid_amount
    PaymentService->>DB: Recalculate outstanding_balance

    PaymentService->>CheckFullyPaid{Fully Paid?}
    CheckFullyPaid -->|Yes| PaymentService->>DB: Set payment_status = 'paid'
    CheckFullyPaid -->|No| PaymentService->>DB: Keep payment_status = 'partial'

    PaymentService->>DB: Create audit_log
    PaymentService->>DB: COMMIT TRANSACTION

    CheckFullyPaid -->|Yes| PaymentService->>OdooSync: queueSync(invoiceId)
    CheckFullyPaid -->|No| SkipSync

    PaymentService-->>API: Return updated invoice
    API-->>Frontend: JSON response
    Frontend->>Cashier: Display updated status

    Note over OdooSync: Async process (non-blocking)
```

---

## 7. Stock Management Flow

```mermaid
flowchart TB
    Start([Stock Operation]) --> OperationType{Operation Type?}

    OperationType -->|Initial Stock| AddProduct[Add Product]
    OperationType -->|Stock In| StockIn[Stock In / Restock]
    OperationType -->|Sale| SaleStock[Sale / Transaction]
    OperationType -->|Return| ReturnStock[Customer Return]
    OperationType -->|Adjustment| AdjustStock[Manual Adjustment]
    OperationType -->|Opname| OpnameStock[Stock Opname]

    AddProduct --> LoadProduct[Load Product Record]
    StockIn --> LoadProduct
    SaleStock --> LoadProduct
    ReturnStock --> LoadProduct
    AdjustStock --> LoadProduct
    OpnameStock --> LoadProduct

    LoadProduct --> GetStock[Get Current Stock]
    GetStock --> CalculateNew[Calculate New Stock]

    AddProduct --> RecordInitial[Record Initial Stock]
    StockIn --> RecordIn[Record Stock In]
    SaleStock --> RecordSale[Record Sale]
    ReturnStock --> RecordReturn[Record Return]
    AdjustStock --> RecordAdjust[Record Adjustment]
    OpnameStock --> RecordOpname[Record Opname]

    RecordInitial --> CreateMovement[Create stock_movement]
    RecordIn --> CreateMovement
    RecordSale --> CreateMovement
    RecordReturn --> CreateMovement
    RecordAdjust --> CreateMovement
    RecordOpname --> CreateMovement

    CreateMovement --> SetMovementType[Set movement_type]
    RecordInitial --> SetMovementType
    RecordIn --> SetMovementType
    RecordSale --> SetMovementType
    RecordReturn --> SetMovementType
    RecordAdjust --> SetMovementType
    RecordOpname --> SetMovementType

    SetMovementType --> SaveMovement[Save to Database]
    SaveMovement --> UpdateProduct[Update Product Stock]
    UpdateProduct --> End([Operation Complete])
```

---

## 8. Dashboard Data Flow

```mermaid
flowchart TB
    Start([User Opens Dashboard]) --> Frontend[DashboardView.vue]
    Frontend --> APIRequest[GET /api/v1/dashboard]
    APIRequest --> DashboardService[DashboardService.php]

    DashboardService --> CalculateToday[Calculate Today's Sales]
    DashboardService --> CalculateMonthly[Calculate Monthly Revenue]
    DashboardService --> GetTopProducts[Get Top Selling Products]
    DashboardService --> GetPaymentStats[Get Payment Statistics]
    DashboardService --> GetChartData[Get Chart Data]

    CalculateToday --> QueryToday[Query invoices<br/>WHERE DATE(created_at) = CURDATE()]
    CalculateMonthly --> QueryMonthly[Query invoices<br/>WHERE MONTH(created_at) = CURRENT_MONTH]
    GetTopProducts --> QueryProducts[Query invoice_details<br/>GROUP BY product_id<br/>ORDER BY SUM(quantity)]
    GetPaymentStats --> QueryPayments[Query payments<br/>GROUP BY payment_method]
    GetChartData --> QueryChart[Query invoices<br/>GROUP BY DATE(created_at)]

    QueryToday --> Aggregate[Aggregate Results]
    QueryMonthly --> Aggregate
    QueryProducts --> Aggregate
    QueryPayments --> Aggregate
    QueryChart --> Aggregate

    Aggregate --> BuildResponse[Build Response JSON]
    BuildResponse --> ReturnResponse[Return to Frontend]
    ReturnResponse --> Frontend
    Frontend --> RenderCharts[Render Charts & KPIs]
    RenderCharts --> End([Dashboard Displayed])
```

---

## 9. Report Generation Flow

```mermaid
flowchart TB
    Start([User Opens Reports]) --> Frontend[ReportView.vue]
    Frontend --> SelectReport{Select Report Type}

    SelectReport -->|Daily Sales| Daily[GET /reports/daily-sales]
    SelectReport -->|Monthly Sales| Monthly[GET /reports/monthly-sales]
    SelectReport -->|Product Sales| Product[GET /reports/product-sales]
    SelectReport -->|Payments| Payment[GET /reports/payments]
    SelectReport -->|Customer Transactions| Customer[GET /reports/customer-transactions]

    Daily --> ReportService[ReportService.php]
    Monthly --> ReportService
    Product --> ReportService
    Payment --> ReportService
    Customer --> ReportService

    ReportService --> ValidateParams[Validate Date Parameters]
    ValidateParams --> BuildQuery[Build SQL Query]
    BuildQuery --> ExecuteQuery[Execute Query on MariaDB]
    ExecuteQuery --> ProcessResults[Process Results]
    ProcessResults --> FormatData[Format Data for Display]
    FormatData --> ReturnData[Return to Frontend]

    ReturnData --> Frontend
    Frontend --> RenderTable[Render Data Table]
    Frontend --> ExportOption[Export Option]

    ExportOption --> ExportPDF{Export Format?}
    ExportPDF -->|PDF| GeneratePDF[Generate PDF]
    ExportPDF -->|Excel| GenerateExcel[Generate Excel]
    ExportPDF -->|CSV| GenerateCSV[Generate CSV]

    GeneratePDF --> End([Report Exported])
    GenerateExcel --> End
    GenerateCSV --> End
```

---

## 10. Audit Log Flow

```mermaid
sequenceDiagram
    participant User
    participant Controller as Controller
    participant Service as Service
    participant AuditService as AuditLogService
    participant DB as MariaDB

    User->>Controller: Perform Action (e.g., Update Product)
    Controller->>Service: Execute business logic
    Service->>DB: Perform database operation

    Service->>AuditService: logActivity(userId, activity, entityType, entityId, metadata)
    AuditService->>AuditService: Create AuditLog record
    AuditService->>DB: INSERT INTO audit_logs

    alt Success
        DB-->>AuditService: Success
        AuditService-->>Service: Success
        Service-->>Controller: Success
        Controller-->>User: Action completed
    else Failure
        DB-->>AuditService: Error
        AuditService-->>Service: Error
        Service->>DB: ROLLBACK (if in transaction)
        Service-->>Controller: Error
        Controller-->>User: Error message
    end

    Note over User, DB: Audit log persists even if main operation fails
```

---

## 11. Error Handling Flow

```mermaid
flowchart TB
    Start([Request Received]) --> Controller[Controller handles request]
    Controller --> Validate[Validate Input]
    Validate --> ValidationOK{Validation OK?}

    ValidationOK -->|No| Return422[Return 422 with errors]
    ValidationOK -->|Yes| Service[Call Service]

    Service --> BusinessLogic[Execute Business Logic]
    BusinessLogic --> CheckException{Exception?}

    CheckException -->|ValidationException| Return422
    CheckException -->|NotFoundException| Return404[Return 404 Not Found]
    CheckException -->|AppException| Return400[Return 400 with message]
    CheckException -->|Other Exception| LogError[Log Error]
    CheckException -->|No Exception| Return200[Return 200 with data]

    LogError --> Return500[Return 500 Internal Server Error]

    Return422 --> End([Response Sent])
    Return404 --> End
    Return400 --> End
    Return500 --> End
    Return200 --> End
```

---

## 12. Frontend Router Guard Flow

```mermaid
flowchart TB
    Start([Route Navigation]) --> CheckAuth{Requires Auth?}

    CheckAuth -->|No| CheckGuest{Guest Only?}
    CheckAuth -->|Yes| IsAuthenticated{Is Authenticated?}

    CheckGuest -->|Yes| IsAuthenticated
    CheckGuest -->|No| CheckRoles

    IsAuthenticated -->|No| RedirectLogin[Redirect to /login]
    IsAuthenticated -->|Yes| HasUser{Has User Data?}

    HasUser -->|No| FetchMe[Fetch /auth/me]
    HasUser -->|Yes| CheckGuest

    FetchMe --> FetchSuccess{Fetch Success?}
    FetchSuccess -->|No| RedirectLogin
    FetchSuccess -->|Yes| CheckGuest

    CheckGuest -->|Yes| IsAuthenticated{Is Authenticated?}
    CheckGuest -->|No| CheckRoles

    CheckRoles --> HasRoles{Has Role Requirements?}
    HasRoles -->|No| AllowAccess[Allow Access]
    HasRoles -->|Yes| CheckUserRole{User Has Required Role?}

    CheckUserRole -->|No| RedirectFallback[Redirect to Fallback Route]
    CheckUserRole -->|Yes| AllowAccess

    RedirectLogin --> End([Navigation Complete])
    RedirectFallback --> End
    AllowAccess --> End
```

---

## 13. Database Migration Flow

```mermaid
flowchart TB
    Start([Run Migration]) --> CLI[php cli/run_migration.php]
    CLI --> CheckMigrations[Check migrations folder]
    CheckMigrations --> ListFiles[List SQL files in order]
    ListFiles --> CheckMigrationTable{Migration Table Exists?}

    CheckMigrationTable -->|No| CreateTable[Create migrations table]
    CheckMigrationTable -->|Yes| GetExecuted[Get executed migrations]

    CreateTable --> GetExecuted
    GetExecuted --> FilterPending[Filter pending migrations]

    FilterPending --> HasPending{Has Pending?}
    HasPending -->|No| Complete([No migrations to run])
    HasPending -->|Yes| ExecuteInOrder[Execute in order]

    ExecuteInOrder --> ReadSQL[Read SQL file]
    ReadSQL --> ExecuteSQL[Execute SQL on MariaDB]
    ExecuteSQL --> Success{Success?}

    Success -->|Yes| RecordMigration[Record in migrations table]
    Success -->|No| Rollback[Rollback changes]
    RecordMigration --> NextMigration{More migrations?}
    Rollback --> Error([Migration Failed])

    NextMigration -->|Yes| ExecuteInOrder
    NextMigration -->|No| Complete
```

---

## 14. Invoice Generation Flow

```mermaid
flowchart TB
    Start([Transaction Complete]) --> GenerateNumber[Generate Invoice Number]
    GenerateNumber --> FormatNumber[Format: INV-YYYYMMDD-XXXX]
    FormatNumber --> BuildPayload[Build Invoice Payload]

    BuildPayload --> GatherHeader[Gather Header Data]
    BuildPayload --> GatherLines[Gather Line Items]
    BuildPayload --> GatherPayments[Gather Payments]

    GatherHeader --> BuildPayload
    GatherLines --> BuildPayload
    GatherPayments --> BuildPayload

    BuildPayload --> SaveInvoice[Save to Database]
    SaveInvoice --> InvoiceRecord[Invoice Record Created]

    InvoiceRecord --> ReturnToFrontend[Return to Frontend]
    ReturnToFrontend --> RenderHTML[Render HTML Template]
    RenderHTML --> DisplayInvoice[Display Invoice]

    DisplayInvoice --> PrintOption{Print Option?}
    PrintOption -->|Yes| WindowPrint[window.print()]
    PrintOption -->|No| PDFOption{PDF Option?}

    PDFOption -->|Yes| GeneratePDF[Generate PDF via Dompdf]
    PDFOption -->|No| End([Invoice Ready])

    WindowPrint --> End
    GeneratePDF --> DownloadPDF[Download PDF]
    DownloadPDF --> End
```

---

## 15. Multi-Tier Architecture Flow

```mermaid
flowchart TB
    subgraph Presentation["Presentation Layer"]
        HTTP["HTTP Request"]
        Controller["Controller"]
        Middleware["Middleware"]
    end

    subgraph Application["Application Layer"]
        Service["Service"]
        Validator["Validator"]
        BusinessLogic["Business Logic"]
    end

    subgraph Domain["Domain Layer"]
        Repository["Repository"]
        Model["Model"]
        Entity["Entity"]
    end

    subgraph Infrastructure["Infrastructure Layer"]
        DB["Database"]
        ExternalAPI["External API"]
        Logger["Logger"]
        Cache["Cache"]
    end

    HTTP --> Middleware
    Middleware --> Controller
    Controller --> Validator
    Validator --> Service
    Service --> BusinessLogic
    BusinessLogic --> Repository
    Repository --> Model
    Model --> Entity
    Entity --> DB
    Service --> ExternalAPI
    Service --> Logger
    Repository --> Cache

    DB --> Model
    ExternalAPI --> Service
    Logger --> Service
    Cache --> Repository
```

---

## Summary

Dokumen ini berisi 15 flow diagram yang mencakup seluruh aspek aplikasi Zi.CARE POS:

1. **Overall System Flow** - Arsitektur high-level sistem
2. **Authentication Flow** - Proses login dan autentikasi
3. **Transaction Flow** - Proses transaksi penjualan
4. **Odoo Sync Flow** - Sinkronisasi dengan Odoo ERP
5. **Payment Flow** - Proses pembayaran (parsial & outstanding)
6. **Outstanding Payment Flow** - Pembayaran hutang pelanggan
7. **Stock Management Flow** - Manajemen stok dan movement
8. **Dashboard Data Flow** - Pengambilan data dashboard
9. **Report Generation Flow** - Pembuatan laporan
10. **Audit Log Flow** - Pencatatan aktivitas
11. **Error Handling Flow** - Penanganan error
12. **Frontend Router Guard Flow** - Proteksi route berdasarkan role
13. **Database Migration Flow** - Proses migrasi database
14. **Invoice Generation Flow** - Pembuatan invoice
15. **Multi-Tier Architecture Flow** - Arsitektur berlapis

Flow diagram ini membantu memahami alur kerja aplikasi secara visual dan memudahkan developer dalam maintenance dan pengembangan.
