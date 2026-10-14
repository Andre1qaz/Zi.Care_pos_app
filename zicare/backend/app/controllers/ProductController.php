<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class ProductController extends BaseController
{
    public function index()
    {
        try {
            $this->requireRole(['administrator', 'manager', 'cashier']);
            $filters = [
                'search'         => $this->request->getQuery('search'),
                'category_id'    => $this->request->getQuery('category_id'),
                'low_stock'      => $this->request->getQuery('low_stock'),
                'product_type'   => $this->request->getQuery('product_type'),
                'service_status' => $this->request->getQuery('service_status'),
            ];
            $page = (int) ($this->request->getQuery('page') ?? 1);
            $limit = (int) ($this->request->getQuery('limit') ?? 20);

            $result = $this->di->getShared('productService')->list($filters, $page, $limit);
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
            $product = $this->di->getShared('productService')->get($id);
            return ResponseHelper::success($product);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function create()
    {
        try {
            $this->requireRole(['administrator']);
            $userId = (int) ($this->getAuthUser()['sub'] ?? 0) ?: null;
            $product = $this->di->getShared('productService')->create($this->getJsonBody(), $userId);
            $this->logAudit("Created product {$product['product_name']}", 'product', isset($product['id']) ? (int) $product['id'] : null);
            return ResponseHelper::success($product, 'Product created');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $userId = (int) ($this->getAuthUser()['sub'] ?? 0) ?: null;
            $product = $this->di->getShared('productService')->update($id, $this->getJsonBody(), $userId);
            $this->logAudit("Updated product {$product['product_name']}", 'product', $id);
            return ResponseHelper::success($product, 'Product updated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/v1/products/{id}/stock-movements
     * Kartu stok untuk satu produk.
     */
    public function stockMovements(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $filters = [
                'type'      => $this->request->getQuery('type'),
                'date_from' => $this->request->getQuery('date_from'),
                'date_to'   => $this->request->getQuery('date_to'),
            ];
            $page = (int) ($this->request->getQuery('page') ?? 1);
            $limit = (int) ($this->request->getQuery('limit') ?? 20);

            $result = $this->di->getShared('productService')->stockMovements($id, $filters, $page, $limit);

            return ResponseHelper::success($result['items'], 'Success', [
                'page'    => $page,
                'limit'   => $limit,
                'total'   => $result['total'],
                'product' => $result['product'],
                'summary' => $result['summary'],
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/products/{id}/stock-adjustments
     * Stok opname / koreksi stok manual.
     */
    public function adjustStock(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $userId = (int) ($this->getAuthUser()['sub'] ?? 0) ?: null;
            $result = $this->di->getShared('productService')->adjustStock($id, $this->getJsonBody(), $userId);

            $productName = $result['product']['product_name'] ?? $id;
            $summary = $result['movement']['stock_before'] . ' -> ' . $result['movement']['stock_after'];
            $this->logAudit("Adjusted stock for {$productName} ({$summary})", 'product', $id);

            return ResponseHelper::success($result, 'Stok berhasil disesuaikan');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $this->di->getShared('productService')->delete($id);
            $this->logAudit("Deleted product ({$id})", 'product', $id);
            return ResponseHelper::success(null, 'Product deleted');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
