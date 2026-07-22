<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class CategoryController extends BaseController
{
    public function index()
    {
        try {
            $categories = $this->di->getShared('categoryService')->list();
            return ResponseHelper::success($categories);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id)
    {
        try {
            return ResponseHelper::success($this->di->getShared('categoryService')->get($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function create()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $category = $this->di->getShared('categoryService')->create($this->getJsonBody());
            return ResponseHelper::success($category, 'Category created');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $category = $this->di->getShared('categoryService')->update($id, $this->getJsonBody());
            return ResponseHelper::success($category, 'Category updated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $this->di->getShared('categoryService')->delete($id);
            return ResponseHelper::success(null, 'Category deleted');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
