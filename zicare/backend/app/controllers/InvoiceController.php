<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\ResponseHelper;

class InvoiceController extends BaseController
{
    public function index()
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);

            $filters = [
                'date_from'      => $this->request->getQuery('date_from'),
                'date_to'        => $this->request->getQuery('date_to'),
                'search'         => $this->request->getQuery('search'),
                'payment_status' => $this->request->getQuery('payment_status'),
                'sync_status'    => $this->request->getQuery('sync_status'),
            ];
            $page = (int) ($this->request->getQuery('page') ?? 1);
            $limit = (int) ($this->request->getQuery('limit') ?? 20);

            $result = $this->di->getShared('invoiceService')->list($filters, $page, $limit);
            return ResponseHelper::success($result['items'], 'Success', [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);
            return ResponseHelper::success($this->di->getShared('invoiceService')->get($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function pdf(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);
            $pdf = $this->di->getShared('invoiceService')->generatePdf($id);
            $response = new \Phalcon\Http\Response();
            $response->setContentType('application/pdf');
            $response->setHeader('Content-Disposition', "inline; filename=invoice-{$id}.pdf");
            $response->setContent($pdf);
            return $response;
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function addPayment(int $id)
    {
        try {
            // Perbaikan auth role dipindah ke dalam blok try
            $this->requireRole(['administrator', 'manager', 'cashier']);
            $authUser = $this->getAuthUser();
            
            $data = $this->request->getJsonRawBody(true);
            if (!$data) {
                $data = $this->request->getPost();
            }
            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }
            
            $validation = $this->validatePaymentData((array)$data);
            if (!$validation['valid']) {
                throw new ValidationException($validation['message']);
            }

            // Perbaikan pengambilan ID user
            $cashierId = (int) $authUser['sub'];
            $result = $this->di->getShared('invoiceService')->addPayment($id, (array)$data, $cashierId);

            return ResponseHelper::success($result, 'Payment added successfully');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function paymentHistory(int $id)
    {
        try {
            // Perbaikan auth role dipindah ke dalam blok try
            $this->requireRole(['administrator', 'manager', 'cashier']);
            
            $result = $this->di->getShared('invoiceService')->getPaymentHistory($id);
            return ResponseHelper::success($result);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Kirim / kirim ulang invoice ini ke modul Accounting Odoo.
     * Query/body: ?force=1 untuk menghapus move lama dan membuat ulang.
     */
    public function sync(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $force = filter_var(
                $this->request->getQuery('force') ?? ($this->getJsonBody()['force'] ?? false),
                FILTER_VALIDATE_BOOLEAN
            );

            $invoiceService = $this->di->getShared('invoiceService');
            // Pastikan invoice ada (lempar 404 bila tidak).
            $invoiceService->get($id);

            $ok = $this->di->getShared('odooSyncService')->retrySync($id, $force);
            $invoice = $invoiceService->get($id);
            $inv = $invoice['invoice'] ?? [];

            $this->logAudit(
                ($ok ? 'Synced' : 'Sync failed for') . ' invoice ' . ($inv['invoice_number'] ?? $id) . ' to Odoo',
                'invoice',
                $id
            );

            if (!$ok) {
                return ResponseHelper::error(
                    'Sinkronisasi ke Odoo gagal. Pastikan Odoo berjalan dan kredensial benar.',
                    502,
                    ['sync_status' => $inv['sync_status'] ?? null]
                );
            }

            return ResponseHelper::success([
                'sync_status'  => $inv['sync_status'] ?? null,
                'odoo_move_id' => $inv['odoo_move_id'] ?? null,
            ], 'Invoice berhasil disinkronkan ke Odoo');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}