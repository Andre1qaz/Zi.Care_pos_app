<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class CustomerController extends BaseController
{
    public function index()
    {
        try {
            $filters = ['search' => $this->request->getQuery('search')];
            $page = (int) ($this->request->getQuery('page') ?? 1);
            $limit = (int) ($this->request->getQuery('limit') ?? 20);

            $result = $this->di->getShared('customerService')->list($filters, $page, $limit);
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
            return ResponseHelper::success($this->di->getShared('customerService')->get($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function create()
    {
        try {
            $customer = $this->di->getShared('customerService')->create($this->getJsonBody());
            return ResponseHelper::success($customer, 'Customer created');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $customer = $this->di->getShared('customerService')->update($id, $this->getJsonBody());
            return ResponseHelper::success($customer, 'Customer updated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->di->getShared('customerService')->delete($id);
            return ResponseHelper::success(null, 'Customer deleted');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
