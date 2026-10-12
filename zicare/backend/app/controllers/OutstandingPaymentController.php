<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\ResponseHelper;

class OutstandingPaymentController extends BaseController
{
    /**
     * GET /api/v1/outstanding-payments
     * List all outstanding invoices with filters.
     */
    public function list()
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);

            $page = (int) ($this->request->getQuery('page', 'int', 1));
            $limit = (int) ($this->request->getQuery('limit', 'int', 20));
            $search = (string) ($this->request->getQuery('search', 'string', ''));
            $status = (string) ($this->request->getQuery('status', 'string', ''));

            $filters = [];

            if ($search !== '') {
                $filters['search'] = $search;
            }

            if ($status !== '' && in_array($status, ['unpaid', 'partial', 'overdue'], true)) {
                $filters['payment_status'] = $status;
            }

            $result = $this->di->getShared('invoiceService')
                ->getOutstandingInvoices($filters, $page, $limit);

            return ResponseHelper::success($result['items'] ?? [], 'Success', [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/v1/outstanding-payments/{id}
     * Get outstanding invoice details with payment history.
     */
    public function get(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);

            $data = $this->di->getShared('invoiceService')->getPaymentHistory($id);

            return ResponseHelper::success($data);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/outstanding-payments/{id}/payment
     * Add a payment to an outstanding invoice.
     */
    public function addPayment(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);

            $data = $this->request->getJsonRawBody(true);
            if (!$data) {
                $data = $this->request->getPost();
            }
            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }
            $data = (array) $data;

            $validation = $this->validatePaymentData($data);
            if (!$validation['valid']) {
                throw new ValidationException($validation['message']);
            }

            $authUser = $this->getAuthUser();
            $cashierId = (int) ($authUser['sub'] ?? 0);

            $result = $this->di->getShared('invoiceService')->addPayment($id, $data, $cashierId);

            return ResponseHelper::success($result, 'Payment added successfully');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/v1/outstanding-payments/stats
     * Get statistics for outstanding payments.
     */
    public function stats()
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $filters = ['payment_status' => ['unpaid', 'partial', 'overdue']];
            $result = $this->di->getShared('invoiceService')
                ->getOutstandingInvoices($filters, 1, 1000);

            $stats = [
                'total_outstanding' => 0,
                'total_invoices'    => count($result['items'] ?? []),
                'by_status'         => [
                    'unpaid'  => ['count' => 0, 'amount' => 0],
                    'partial' => ['count' => 0, 'amount' => 0],
                    'overdue' => ['count' => 0, 'amount' => 0],
                ],
            ];

            foreach ($result['items'] ?? [] as $invoice) {
                $status = $invoice['payment_status'] ?? 'unpaid';
                $outstanding = (float) ($invoice['outstanding_balance'] ?? 0);

                $stats['total_outstanding'] += $outstanding;

                if (!isset($stats['by_status'][$status])) {
                    $stats['by_status'][$status] = ['count' => 0, 'amount' => 0];
                }

                $stats['by_status'][$status]['count']++;
                $stats['by_status'][$status]['amount'] += $outstanding;
            }

            return ResponseHelper::success($stats);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function validatePaymentData(array $data): array
    {
        if (empty($data['payment_method'])) {
            return ['valid' => false, 'message' => 'Payment method is required'];
        }

        $validMethods = ['cash', 'qris', 'transfer', 'ewallet'];
        if (!in_array($data['payment_method'], $validMethods, true)) {
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
