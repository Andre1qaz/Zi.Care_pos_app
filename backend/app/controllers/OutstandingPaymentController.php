<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\InvoiceService;
use Phalcon\Di\Di;

class OutstandingPaymentController extends BaseController
{
    private InvoiceService $invoiceService;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->invoiceService = Di::getDefault()->getShared('invoiceService');
    }

    /**
     * GET /api/outstanding-payments
     * List all outstanding invoices with filters
     */
    public function listAction()
    {
        $this->requireAuth(['administrator', 'manager', 'cashier']);

        $page = (int) ($this->request->getQuery('page', 'int', 1));
        $limit = (int) ($this->request->getQuery('limit', 'int', 20));
        $search = $this->request->getQuery('search', 'string', '');
        $status = $this->request->getQuery('status', 'string', '');

        $filters = [];
        
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        
        if (!empty($status) && in_array($status, ['unpaid', 'partial', 'overdue'])) {
            $filters['payment_status'] = $status;
        }

        $result = $this->invoiceService->getOutstandingInvoices($filters, $page, $limit);

        return $this->success($result);
    }

    /**
     * GET /api/outstanding-payments/{id}
     * Get outstanding invoice details with payment history
     */
    public function getAction(int $id)
    {
        $this->requireAuth(['administrator', 'manager', 'cashier']);

        $data = $this->invoiceService->getPaymentHistory($id);

        return $this->success($data);
    }

    /**
     * POST /api/outstanding-payments/{id}/payment
     * Add a payment to an outstanding invoice
     */
    public function addPaymentAction(int $id)
    {
        $this->requireAuth(['administrator', 'manager', 'cashier']);

        $data = $this->request->getJsonRawBody(true);
        
        $validation = $this->validatePaymentData($data);
        if (!$validation['valid']) {
            throw new ValidationException($validation['message']);
        }

        $cashierId = $this->auth->user['id'];
        $result = $this->invoiceService->addPayment($id, $data, $cashierId);

        return $this->success($result, 'Payment added successfully');
    }

    /**
     * GET /api/outstanding-payments/stats
     * Get statistics for outstanding payments
     */
    public function statsAction()
    {
        $this->requireAuth(['administrator', 'manager']);

        $filters = ['payment_status' => ['unpaid', 'partial', 'overdue']];
        $result = $this->invoiceService->getOutstandingInvoices($filters, 1, 1000);

        $stats = [
            'total_outstanding' => 0,
            'total_invoices' => count($result['data'] ?? []),
            'by_status' => [
                'unpaid' => ['count' => 0, 'amount' => 0],
                'partial' => ['count' => 0, 'amount' => 0],
                'overdue' => ['count' => 0, 'amount' => 0]
            ]
        ];

        foreach ($result['data'] ?? [] as $invoice) {
            $status = $invoice['payment_status'];
            $outstanding = (float) $invoice['outstanding_balance'];
            
            $stats['total_outstanding'] += $outstanding;
            $stats['by_status'][$status]['count']++;
            $stats['by_status'][$status]['amount'] += $outstanding;
        }

        return $this->success($stats);
    }

    private function validatePaymentData(array $data): array
    {
        if (empty($data['payment_method'])) {
            return ['valid' => false, 'message' => 'Payment method is required'];
        }

        $validMethods = ['cash', 'qris', 'transfer', 'ewallet'];
        if (!in_array($data['payment_method'], $validMethods)) {
            return ['valid' => false, 'message' => 'Invalid payment method'];
        }

        if (!isset($data['amount']) || $data['amount'] <= 0) {
            return ['valid' => false, 'message' => 'Payment amount must be greater than 0'];
        }

        // For non-cash payments, require provider and reference
        if ($data['payment_method'] !== 'cash') {
            if (empty($data['provider'])) {
                return ['valid' => false, 'message' => 'Provider is required for non-cash payments'];
            }
            if (empty($data['payment_reference'])) {
                return ['valid' => false, 'message' => 'Payment reference is required for non-cash payments'];
            }
        }

        return ['valid' => true];
    }
}
