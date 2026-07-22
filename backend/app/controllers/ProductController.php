<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class ProductController extends BaseController
{
    public function index()
    {
        try {
            $filters = [
                'search'      => $this->request->getQuery('search'),
                'category_id' => $this->request->getQuery('category_id'),
                'low_stock'   => $this->request->getQuery('low_stock'),
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
            $product = $this->di->getShared('productService')->create($this->getJsonBody());
            return ResponseHelper::success($product, 'Product created');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $product = $this->di->getShared('productService')->update($id, $this->getJsonBody());
            return ResponseHelper::success($product, 'Product updated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $this->di->getShared('productService')->delete($id);
            return ResponseHelper::success(null, 'Product deleted');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
