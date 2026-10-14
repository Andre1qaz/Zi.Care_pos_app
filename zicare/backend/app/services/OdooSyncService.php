<?php

declare(strict_types=1);

namespace App\Services;

use Phalcon\Di\Di;

class OdooSyncService
{
    private array $config;
    private $logger;
    private ?int $uid = null;

    public function __construct()
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $this->config = $config['odoo'];
        $this->logger = Di::getDefault()->getShared('logger');
    }

    /**
     * Dipanggil saat transaksi selesai. Secara default TIDAK memblokir respons
     * checkout: sinkronisasi dijalankan di proses worker terpisah (detached),
     * karena proses sync bisa memakan beberapa detik. Invoice sudah tersimpan
     * dengan sync_status='pending' sehingga worker/CLI/ tombol manual bisa
     * mencoba ulang bila gagal.
     */
    public function queueSync(int $invoiceId): void
    {
        if (!$this->config['enabled']) return;

        if (empty($this->config['async'])) {
            // Mode sinkron (berguna untuk pengujian / server dengan worker).
            try {
                $this->syncInvoice($invoiceId);
            } catch (\Throwable $e) {
                $this->logger->error("Odoo sync error: " . $e->getMessage());
            }
            return;
        }

        if (!$this->spawnBackgroundSync($invoiceId)) {
            $this->logger->warning("Odoo: gagal menjalankan worker latar untuk invoice #{$invoiceId}; akan dicoba oleh CLI/tombol manual.");
        }
    }

    /**
     * Jalankan `cli/sync_odoo.php --id=NN` sebagai proses terpisah tanpa
     * menunggu selesai, agar checkout POS tetap cepat.
     */
    private function spawnBackgroundSync(int $invoiceId): bool
    {
        $cli = BASE_PATH . '/cli/sync_odoo.php';
        if (!is_file($cli)) {
            return false;
        }

        $php = $_ENV['PHP_CLI_BINARY'] ?? (PHP_BINARY ?: 'php');

        try {
            if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
                $cmd = 'start /B "" "' . $php . '" "' . $cli . '" --id=' . $invoiceId . ' > NUL 2>&1';
                $handle = @popen($cmd, 'r');
                if ($handle === false) {
                    return false;
                }
                @pclose($handle);
                return true;
            }

            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($cli) . ' --id=' . $invoiceId . ' > /dev/null 2>&1 &';
            $handle = @popen($cmd, 'r');
            if ($handle === false) {
                return false;
            }
            @pclose($handle);
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Odoo: gagal spawn worker: ' . $e->getMessage());
            return false;
        }
    }

    public function retrySync(int $invoiceId, bool $force = false): bool
    {
        if (!$this->config['enabled']) return false;
        return $this->syncInvoice($invoiceId, $force);
    }

    /**
     * Uji koneksi ke Odoo (modul Accounting). Dipakai oleh endpoint status
     * integrasi dan CLI agar konektivitas bisa diverifikasi tanpa membuat
     * invoice baru.
     */
    public function testConnection(): array
    {
        if (!$this->config['enabled']) {
            return ['enabled' => false, 'connected' => false, 'message' => 'Sinkronisasi Odoo dimatikan (ODOO_SYNC_ENABLED=false).'];
        }

        try {
            $uid = $this->authenticate();
            $version = $this->jsonRpc('common', 'version', []);

            $saleJournal = $this->jsonRpc('object', 'execute_kw', [
                $this->config['db'], $uid, $this->authCredential(),
                'account.journal', 'search_count', [[['type', '=', 'sale']]],
            ]);

            return [
                'enabled'        => true,
                'connected'      => true,
                'url'            => $this->config['url'],
                'database'       => $this->config['db'],
                'username'       => $this->config['username'],
                'uid'            => $uid,
                'server_version' => $version['server_version'] ?? null,
                'sale_journals'  => $saleJournal,
                'message'        => 'Terhubung ke Odoo Accounting.',
            ];
        } catch (\Throwable $e) {
            return [
                'enabled'   => true,
                'connected' => false,
                'url'       => $this->config['url'],
                'database'  => $this->config['db'],
                'username'  => $this->config['username'],
                'message'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim SEMUA produk & pelanggan POS ke Odoo (satu arah POS → Odoo).
     * Berguna supaya master data tercermin di Odoo walau belum pernah dipakai
     * bertransaksi. Idempoten: item yang sudah ada tidak diduplikasi (dicari
     * berdasarkan SKU / email / nama).
     *
     * @return array Ringkasan hasil per kategori.
     */
    public function syncAllMaster(): array
    {
        if (!$this->config['enabled']) {
            return [
                'enabled'   => false,
                'message'   => 'Sinkronisasi Odoo dimatikan (ODOO_SYNC_ENABLED=false).',
                'products'  => ['synced' => 0, 'failed' => 0, 'total' => 0],
                'customers' => ['synced' => 0, 'failed' => 0, 'total' => 0],
            ];
        }

        $db = Di::getDefault()->getShared('db');

        $products = $db->query(
            "SELECT product_code, product_name, price, product_type FROM products WHERE is_active = 1"
        )->fetchAll();

        $customers = $db->query(
            "SELECT customer_name, email, phone FROM customers"
        )->fetchAll();

        $prodOk = 0;
        $prodFail = 0;
        foreach ($products as $p) {
            try {
                $this->syncProduct([
                    'name'         => $p['product_name'],
                    'price'        => (float) $p['price'],
                    'sku'          => $p['product_code'] ?: 'SKU-000',
                    'product_type' => $p['product_type'] ?? 'barang',
                ]);
                $prodOk++;
            } catch (\Throwable $e) {
                $prodFail++;
                $this->logger->warning("Odoo master sync produk {$p['product_code']} gagal: " . $e->getMessage());
            }
        }

        $custOk = 0;
        $custFail = 0;
        foreach ($customers as $c) {
            try {
                $name = trim((string) ($c['customer_name'] ?? '')) !== ''
                    ? $c['customer_name']
                    : 'Walk-in Customer';
                $email = !empty($c['email']) && $c['email'] !== 'walkin@pos.com' ? $c['email'] : '';

                $this->syncCustomer([
                    'name'  => $name,
                    'email' => $email,
                    'phone' => $c['phone'] ?? '',
                ]);
                $custOk++;
            } catch (\Throwable $e) {
                $custFail++;
                $this->logger->warning("Odoo master sync pelanggan {$c['customer_name']} gagal: " . $e->getMessage());
            }
        }

        return [
            'enabled'   => true,
            'message'   => 'Sinkronisasi master data selesai.',
            'products'  => ['synced' => $prodOk, 'failed' => $prodFail, 'total' => count($products)],
            'customers' => ['synced' => $custOk, 'failed' => $custFail, 'total' => count($customers)],
        ];
    }

    public function syncInvoice(int $invoiceId, bool $force = false): bool
    {
        $db = Di::getDefault()->getShared('db');
        $invoiceRepo = Di::getDefault()->getShared('invoiceRepository');
        $data = $invoiceRepo->findWithDetails($invoiceId);
        if (!$data) return false;

        // Idempotent: jangan buat duplikat di Odoo kalau invoice ini sudah
        // pernah tersinkron (kecuali diminta paksa / re-sync).
        $existingMoveId = $data['invoice']['odoo_move_id'] ?? null;
        if (!$force && !empty($existingMoveId) && ($data['invoice']['sync_status'] ?? '') === 'synced') {
            return true;
        }

        try {
            $uid = $this->authenticate();

            // Re-sync paksa: hapus dulu move lama di Odoo agar tidak dobel.
            if ($force && !empty($existingMoveId)) {
                try {
                    $this->deleteMove((int) $existingMoveId);
                } catch (\Throwable $inner) {
                    $this->logger->warning("Gagal menghapus move lama #{$existingMoveId}: " . $inner->getMessage());
                }
            }

            // Customer data lives on the related `customers` record; the invoices
            // table has no customer_name/email/phone columns.
            $customer = $data['customer'] ?? [];

            $customerName = trim((string) ($customer['customer_name'] ?? '')) !== ''
                ? $customer['customer_name']
                : 'Walk-in Customer';
            $customerEmail = !empty($customer['email']) && $customer['email'] !== 'walkin@pos.com'
                ? $customer['email']
                : '';
            $customerPhone = !empty($customer['phone']) ? $customer['phone'] : '';

            $partnerId = $this->syncCustomer([
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone
            ]);

            $lines = [];
            foreach ($data['details'] as $item) {
                $productId = $this->syncProduct([
                    'name' => $item['product_name'],
                    'price' => (float) $item['price'],
                    'sku' => $item['product_code'] ?? 'SKU-000'
                ]);

                $lines[] = [0, 0, [
                    'name' => $item['product_name'],
                    'product_id' => $productId,
                    'quantity' => (int) $item['quantity'],
                    'price_unit' => (float) $item['price'],
                    // Harga POS sudah final (tanpa perhitungan pajak terpisah).
                    // Bersihkan pajak bawaan Odoo agar total invoice sama persis.
                    'tax_ids' => [[6, 0, []]],
                ]];
            }

            $authCredential = $this->authCredential();

            // Get sale journal (type 'sale' fallback ke code 'SALE')
            $saleJournal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['type', '=', 'sale']]], ['limit' => 1]]);
            if (empty($saleJournal)) {
                $saleJournal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['code', '=', 'SALE']]], ['limit' => 1]]);
            }
            $journalId = !empty($saleJournal) ? (int)$saleJournal[0] : 7; // Fallback ke journal default jika tidak ditemukan
            
            $moveId = $this->toId($this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'create', [[
                'move_type' => 'out_invoice',
                'partner_id' => $partnerId,
                'invoice_date' => date('Y-m-d', strtotime($data['invoice']['created_at'])),
                'ref' => 'POS: ' . $data['invoice']['invoice_number'],
                'journal_id' => $journalId,
                'invoice_line_ids' => $lines,
            ]]]));

            if ($moveId > 0) {
                $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'action_post', [[$moveId]]]);

                $db->execute("UPDATE invoices SET sync_status = 'synced', odoo_move_id = ? WHERE id = ?", [$moveId, $invoiceId]);

                $appliedAmount = (float) $data['invoice']['total_amount'] - (float) $data['invoice']['outstanding_balance'];
                if ($appliedAmount > 0) {
                    $this->syncPayment($moveId, $appliedAmount, $data['invoice']['payment_method'] ?? 'cash');
                }
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Detail Odoo Sync Error: " . $e->getMessage());

            try {
                $db->execute("UPDATE invoices SET sync_status = 'failed' WHERE id = ?", [$invoiceId]);
            } catch (\Throwable $inner) {
                // abaikan
            }

            return false;
        }
    }

    public function syncCustomer(array $data): int
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();

        $domain = [];
        if (!empty($data['email'])) {
            $domain[] = ['email', '=', $data['email']];
        } else {
            $domain[] = ['name', '=', $data['name']];
        }

        $existing = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'res.partner', 'search', [$domain], ['limit' => 1]]);

        if (!empty($existing)) return (int) $existing[0];

        return $this->toId($this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'res.partner', 'create', [[
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
        ]]]));
    }

    public function syncProduct(array $data): int
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();
        $existing = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'product.product', 'search', [[['default_code', '=', $data['sku']]]], ['limit' => 1]]);

        if (!empty($existing)) {
            // Produk sudah ada: perbarui nama & harga agar master POS tetap
            // tercermin di Odoo (idempotent, bukan hanya create-if-missing).
            $existingId = (int) $existing[0];
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'product.product', 'write', [[$existingId], [
                'name'       => $data['name'],
                'list_price' => $data['price'],
            ]]]);

            return $existingId;
        }

        // Barang -> 'consu', Jasa -> 'service'
        $odooType = ($data['product_type'] ?? 'barang') === 'jasa' ? 'service' : 'consu';

        return $this->toId($this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'product.product', 'create', [[
            'name' => $data['name'],
            'list_price' => $data['price'],
            'default_code' => $data['sku'],
            'type' => $odooType,
            'sale_ok' => true,
            // Produk POS tidak memakai pajak default Odoo; total invoice POS
            // sudah final. Kosongkan pajak agar harga tidak bertambah.
            'taxes_id' => [[6, 0, []]],
            'supplier_taxes_id' => [[6, 0, []]],
        ]]]));
    }

    public function syncPayment(int $moveId, float $amount, string $method): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();

        $isCash = in_array(strtolower($method), ['cash', 'tunai']);
        $journalCode = $isCash ? $this->config['journal_cash_code'] : $this->config['journal_bank_code'];

        $journal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['code', '=', $journalCode]]], ['limit' => 1]]);

        if (empty($journal)) {
            // Untuk pembayaran tunai, utamakan journal tipe "cash"; jika tidak
            // ada, baru pakai bank.
            $types = $isCash ? [['type', '=', 'cash']] : [['type', 'in', ['bank', 'cash']]];
            $journal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [$types], ['limit' => 1]]);
        }
        if (empty($journal)) {
            $journal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['type', 'in', ['cash', 'bank']]]], ['limit' => 1]]);
        }
        $journalId = !empty($journal) ? (int)$journal[0] : 1;

        // Odoo 19 hanya membuat jurnal pembayaran bila payment method line punya
        // akun outstanding. Tanpa ini, pembayaran "in_process" tanpa jurnal dan
        // invoice tetap outstanding. Lengkapi otomatis bila diizinkan.
        if (!empty($this->config['auto_configure'])) {
            $this->ensureInboundOutstandingAccount($journalId);
        }

        $wizardId = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment.register', 'create', [[
            'amount' => $amount,
            'journal_id' => $journalId,
            'payment_date' => date('Y-m-d'),
        ]], [
            'context' => [
                'active_model' => 'account.move',
                'active_ids' => [$moveId]
            ]
        ]]);
        $wizardId = $this->toId($wizardId);

        if ($wizardId > 0) {
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment.register', 'action_create_payments', [[$wizardId]], [
                'context' => [
                    'active_model' => 'account.move',
                    'active_ids' => [$moveId]
                ]
            ]]);
        } else {
            $this->logger->warning("Odoo: gagal membuat payment register untuk move #{$moveId}.");
        }
    }

    /**
     * Pastikan setiap inbound payment method line pada journal memiliki akun
     * "Outstanding Receipts". Odoo 19 memerlukan akun ini agar pembayaran
     * menghasilkan jurnal dan merekonsiliasi invoice. Idempoten: hanya mengisi
     * yang masih kosong.
     */
    private function ensureInboundOutstandingAccount(int $journalId): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();

        try {
            $lines = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment.method.line', 'search_read', [[
                ['journal_id', '=', $journalId],
                ['payment_type', '=', 'inbound'],
            ]], ['fields' => ['name', 'payment_account_id']]]);

            if (empty($lines)) {
                return;
            }

            $missing = array_values(array_filter($lines, fn($l) => empty($l['payment_account_id'])));
            if (empty($missing)) {
                return;
            }

            $account = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.account', 'search', [[
                ['name', 'ilike', 'Outstanding Receipts'],
                ['account_type', '=', 'asset_current'],
            ]], ['limit' => 1]]);

            if (empty($account)) {
                $account = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.account', 'search', [[
                    ['name', 'ilike', 'Outstanding'],
                    ['account_type', '=', 'asset_current'],
                ]], ['limit' => 1]]);
            }

            if (empty($account)) {
                $this->logger->warning('Odoo: akun "Outstanding Receipts" tidak ditemukan; pembayaran mungkin tidak terposting ke Accounting.');
                return;
            }
            $accountId = (int) $account[0];

            foreach ($missing as $line) {
                $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment.method.line', 'write', [[
                    (int) $line['id'],
                ], [
                    'payment_account_id' => $accountId,
                ]]]);
                $this->logger->info("Odoo: mengisi outstanding account #{$accountId} pada payment method line #{$line['id']} (journal #{$journalId}).");
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan sync hanya karena auto-configure tidak berhasil.
            $this->logger->warning('Odoo: gagal memastikan outstanding account: ' . $e->getMessage());
        }
    }

    /**
     * Normalisasi hasil `create` Odoo: sebagian versi mengembalikan id (int),
     * sebagian lain daftar id ([id]).
     */
    private function toId($value): int
    {
        if (is_array($value)) {
            $value = $value[0] ?? 0;
        }
        return (int) $value;
    }

    public function syncCreditNote(int $originalMoveId, array $refundLines): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();

        $invoice = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'read', [[$originalMoveId]], ['fields' => ['partner_id']]]);
        if (empty($invoice)) return;
        $partnerId = $invoice[0]['partner_id'][0];

        $lines = [];
        foreach ($refundLines as $item) {
            $productId = $this->syncProduct([
                'name' => $item['product_name'],
                'price' => (float) $item['price'],
                'sku' => $item['product_code']
            ]);

            $lines[] = [0, 0, [
                'name' => $item['product_name'] ?? '',
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                'price_unit' => (float) $item['price'],
                'tax_ids' => [[6, 0, []]],
            ]];
        }

        $creditNoteId = $this->toId($this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'create', [[
            'move_type' => 'out_refund',
            'partner_id' => $partnerId,
            'invoice_date' => date('Y-m-d'),
            'ref' => 'Retur dari Invoice Odoo #' . $originalMoveId,
            'invoice_line_ids' => $lines
        ]]]));

        if ($creditNoteId > 0) {
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'action_post', [[$creditNoteId]]]);
        }
    }

    /**
     * Hapus move (invoice) di Odoo. Dipakai saat re-sync paksa supaya tidak
     * meninggalkan invoice duplikat di modul Accounting. Pembayaran yang
     * mereferensikan move ini juga dibatalkan/dihapus lebih dulu karena Odoo
     * menolak menghapus invoice yang sudah direkonsiliasi dengan pembayaran.
     */
    public function deleteMove(int $moveId): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->authCredential();

        // 1) Batalkan & hapus payment yang terkait move ini.
        try {
            $payments = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment', 'search', [[
                ['reconciled_invoice_ids', 'in', [$moveId]],
            ]]]);

            foreach ((array) $payments as $paymentId) {
                $paymentId = (int) $paymentId;

                // Hapus dulu jurnal (move) milik payment bila ada.
                try {
                    $payment = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment', 'read', [[
                        $paymentId,
                    ]], ['fields' => ['move_id']]]);
                    $paymentMoveId = $payment[0]['move_id'][0] ?? null;
                    if ($paymentMoveId) {
                        try {
                            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'button_draft', [[(int) $paymentMoveId]]]);
                        } catch (\Throwable $e) {
                        }
                        try {
                            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'unlink', [[(int) $paymentMoveId]]]);
                        } catch (\Throwable $e) {
                        }
                    }
                } catch (\Throwable $e) {
                    // lanjut
                }

                try {
                    $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment', 'action_cancel', [[$paymentId]]]);
                } catch (\Throwable $e) {
                }
                try {
                    $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment', 'unlink', [[$paymentId]]]);
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
            // lanjut ke penghapusan move
        }

        // 2) Batalkan & hapus move-nya.
        try {
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'button_draft', [[$moveId]]]);
        } catch (\Throwable $e) {
            try {
                $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'button_cancel', [[$moveId]]]);
            } catch (\Throwable $e2) {
            }
        }

        $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'unlink', [[$moveId]]]);
    }

    private function authenticate(): int
    {
        // Cache UID per request: one authenticate round-trip per sync operation
        // instead of one per line/product/customer/payment.
        if ($this->uid !== null) {
            return $this->uid;
        }

        $uid = $this->jsonRpc('common', 'authenticate', [$this->config['db'], $this->config['username'], $this->authCredential(), []]);
        if (!$uid) throw new \RuntimeException('Auth failed');

        $this->uid = (int) $uid;
        return $this->uid;
    }

    /**
     * Kredensial untuk autentikasi Odoo.
     * Odoo menerima API key pada parameter password saat authenticate,
     * jadi kita prioritaskan ODOO_API_KEY dan fallback ke ODOO_PASSWORD.
     */
    private function authCredential(): string
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        return $apiKey !== '' ? $apiKey : (string) $this->config['password'];
    }

    private function jsonRpc(string $service, string $method, array $params)
    {
        $payload = json_encode(['jsonrpc' => '2.0', 'method' => 'call', 'params' => ['service' => $service, 'method' => $method, 'args' => $params], 'id' => random_int(1, 999999)]);

        $ch = curl_init(rtrim($this->config['url'], '/') . '/jsonrpc');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);

        $rawResponse = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false || $curlErrno !== 0) {
            throw new \RuntimeException(
                'Gagal menghubungi server Odoo: ' . ($curlError ?: 'unknown curl error') .
                ' (curl errno: ' . $curlErrno . ')'
            );
        }

        $response = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Respons Odoo bukan JSON valid: ' . json_last_error_msg());
        }

        if (isset($response['error'])) {
            $msg = $response['error']['message'] ?? 'Odoo returned an unspecified error';
            if (!empty($response['error']['data']['message'])) {
                $msg .= ' | ' . $response['error']['data']['message'];
            }
            throw new \RuntimeException($msg);
        }

        return $response['result'] ?? null;
    }
}