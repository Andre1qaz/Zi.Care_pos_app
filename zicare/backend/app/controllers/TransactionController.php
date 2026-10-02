<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class TransactionController extends BaseController
{
    public function create()
    {
        try {
            $this->requireRole(['administrator', 'cashier']);
            $authUser = $this->getAuthUser();
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true);
            
            if (!is_array($data) || empty($data)) {
                $data = $this->request->getPost();
            }

            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }

            $result = $this->di->getShared('transactionService')->create(
                (array) $data,
                (int) $authUser['sub']
            );
            
            return ResponseHelper::success($result, 'Transaction completed successfully');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $invoice = $this->di->getShared('invoiceService')->get($id);
            return ResponseHelper::success($invoice);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}