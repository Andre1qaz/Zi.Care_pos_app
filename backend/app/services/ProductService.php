<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Phalcon\Di\Di;

class ProductService
{
    private ProductRepository $repository;

    public function __construct()
    {
        $this->repository = Di::getDefault()->getShared('productRepository');
    }

    public function list(array $filters, int $page, int $limit): array
    {
        return $this->repository->findAll($filters, $page, $limit);
    }

    public function get(int $id): array
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        return $product->toArray();
    }

    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            throw new ValidationException($errors);
        }

        if ($this->repository->findByCode($data['product_code'])) {
            throw new ValidationException(['product_code' => 'Product code already exists']);
        }

        $product = new Product();
        $product->assign($data);
        
        // Ensure services have stock = 0
        if ($product->product_type === 'jasa') {
            $product->stock = 0;
        }
        
        $product->save();

        return $product->toArray();
    }

    public function update(int $id, array $data): array
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $product->assign($data);
        
        // Ensure services have stock = 0
        if ($product->product_type === 'jasa') {
            $product->stock = 0;
        }
        
        $product->save();

        return $product->toArray();
    }

    public function delete(int $id): void
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        $product->is_active = 0;
        $product->save();
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['product_code'])) {
            $errors['product_code'] = 'Product code is required';
        }
        if (empty($data['product_name'])) {
            $errors['product_name'] = 'Product name is required';
        }
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors['price'] = 'Valid price is required';
        }

        $productType = $data['product_type'] ?? 'barang';

        if ($productType === 'barang') {
            if (!isset($data['stock']) || $data['stock'] < 0) {
                $errors['stock'] = 'Valid stock is required for barang';
            }
        } elseif ($productType === 'jasa') {
            // Services must have stock = 0
            if (isset($data['stock']) && $data['stock'] != 0) {
                $errors['stock'] = 'Stock must be 0 for jasa (services)';
            }
            if (!isset($data['service_status']) || !in_array($data['service_status'], ['tersedia', 'tidak_tersedia'])) {
                $errors['service_status'] = 'Valid service status is required for jasa';
            }
        }

        return $errors;
    }
}
