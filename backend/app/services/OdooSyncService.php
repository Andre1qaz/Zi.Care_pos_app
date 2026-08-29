<?php

declare(strict_types=1);

namespace App\Services;

use Phalcon\Di\Di;

class OdooSyncService
{
    private array $config;
    private $logger;

    public function __construct()
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $this->config = $config['odoo'];
        $this->logger = Di::getDefault()->getShared('logger');
    }

    public function queueSync(int $invoiceId): void
    {
        if (!$this->config['enabled']) return;
        try {
            $this->syncInvoice($invoiceId);
        } catch (\Throwable $e) {
            $this->logger->error("Odoo sync error: " . $e->getMessage());
        }
    }

    public function syncInvoice(int $invoiceId): bool
    {
        $db = Di::getDefault()->getShared('db');
        $invoiceRepo = Di::getDefault()->getShared('invoiceRepository');
        $data = $invoiceRepo->findWithDetails($invoiceId);
        if (!$data) return false;

        try {
            $uid = $this->authenticate();

            $customerName = !empty($data['invoice']['customer_name']) ? $data['invoice']['customer_name'] : 'Walk-in Customer';
            $customerEmail = !empty($data['invoice']['customer_email']) && $data['invoice']['customer_email'] !== 'walkin@pos.com' ? $data['invoice']['customer_email'] : '';
            $customerPhone = !empty($data['invoice']['customer_phone']) ? $data['invoice']['customer_phone'] : '';

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
                    'product_id' => $productId,
                    'quantity' => (int) $item['quantity'],
                    'price_unit' => (float) $item['price']
                ]];
            }

            $authCredential = $this->config['api_key'] ?? $this->config['password'];
            $moveId = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'create', [[
                'move_type' => 'out_invoice',
                'partner_id' => $partnerId,
                'invoice_date' => date('Y-m-d', strtotime($data['invoice']['created_at'])),
                'ref' => 'POS: ' . $data['invoice']['invoice_number'],
                'invoice_line_ids' => $lines,
            ]]]);

            if ($moveId) {
                $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'action_post', [[(int) $moveId]]]);

                $db->execute("UPDATE invoices SET sync_status = 'synced', odoo_move_id = ? WHERE id = ?", [$moveId, $invoiceId]);

                if ((float)$data['invoice']['paid_amount'] > 0) {
                    $this->syncPayment((int)$moveId, (float)$data['invoice']['paid_amount'], $data['invoice']['payment_method'] ?? 'cash');
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
        $authCredential = $this->config['api_key'] ?? $this->config['password'];
        
        $domain = [];
        if (!empty($data['email'])) {
            $domain[] = ['email', '=', $data['email']];
        } else {
            $domain[] = ['name', '=', $data['name']];
        }

        $existing = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'res.partner', 'search', [$domain], ['limit' => 1]]);

        if (!empty($existing)) return (int) $existing[0];

        return (int) $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'res.partner', 'create', [[
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
        ]]]);
    }

    public function syncProduct(array $data): int
    {
        $uid = $this->authenticate();
        $authCredential = $this->config['api_key'] ?? $this->config['password'];
        $existing = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'product.product', 'search', [[['default_code', '=', $data['sku']]]], ['limit' => 1]]);

        if (!empty($existing)) return (int) $existing[0];

        return (int) $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'product.product', 'create', [[
            'name' => $data['name'],
            'list_price' => $data['price'],
            'default_code' => $data['sku'],
            'type' => 'consu',
            'sale_ok' => true
        ]]]);
    }

    public function syncPayment(int $moveId, float $amount, string $method): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->config['api_key'] ?? $this->config['password'];

        $isCash = in_array(strtolower($method), ['cash', 'tunai']);
        $journalCode = $isCash ? $this->config['journal_cash_code'] : $this->config['journal_bank_code'];

        $journal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['code', '=', $journalCode]]], ['limit' => 1]]);

        if (empty($journal)) {
            $journal = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.journal', 'search', [[['type', 'in', ['cash', 'bank']]]], ['limit' => 1]]);
        }
        $journalId = !empty($journal) ? (int)$journal[0] : 1;

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

        if ($wizardId) {
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.payment.register', 'action_create_payments', [[$wizardId]]]);
        }
    }

    public function syncCreditNote(int $originalMoveId, array $refundLines): void
    {
        $uid = $this->authenticate();
        $authCredential = $this->config['api_key'] ?? $this->config['password'];

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
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                'price_unit' => (float) $item['price']
            ]];
        }

        $creditNoteId = $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'create', [[
            'move_type' => 'out_refund',
            'partner_id' => $partnerId,
            'invoice_date' => date('Y-m-d'),
            'ref' => 'Retur dari Invoice Odoo #' . $originalMoveId,
            'invoice_line_ids' => $lines
        ]]]);

        if ($creditNoteId) {
            $this->jsonRpc('object', 'execute_kw', [$this->config['db'], $uid, $authCredential, 'account.move', 'action_post', [[(int)$creditNoteId]]]);
        }
    }

    private function authenticate(): int
    {
        // Use API key if available, otherwise fall back to password
        $authCredential = $this->config['api_key'] ?? $this->config['password'];
        $uid = $this->jsonRpc('common', 'authenticate', [$this->config['db'], $this->config['username'], $authCredential, []]);
        if (!$uid) throw new \RuntimeException('Auth failed');
        return (int) $uid;
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
            throw new \RuntimeException($response['error']['message'] ?? 'Odoo returned an unspecified error');
        }

        return $response['result'] ?? null;
    }
}