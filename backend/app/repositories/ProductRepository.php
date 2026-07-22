<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function findAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $conditions = ['is_active = 1'];
        $bind = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(product_name LIKE :search: OR product_code LIKE :search:)';
            $bind['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'category_id = :category_id:';
            $bind['category_id'] = (int) $filters['category_id'];
        }

        if (isset($filters['low_stock'])) {
            $conditions[] = 'product_type = :product_type_barang: AND stock <= :low_stock:';
            $bind['product_type_barang'] = 'barang';
            $bind['low_stock'] = (int) $filters['low_stock'];
        }

        $offset = ($page - 1) * $limit;

        $products = Product::find([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
            'limit'      => $limit,
            'offset'     => $offset,
            'order'      => 'product_name ASC',
        ]);

        $total = Product::count([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
        ]);

        return [
            'items' => $products->toArray(),
            'total' => $total,
        ];
    }

    public function findById(int $id): ?Product
    {
        return Product::findFirst($id) ?: null;
    }

    public function findByCode(string $code): ?Product
    {
        return Product::findFirst([
            'conditions' => 'product_code = :code:',
            'bind'       => ['code' => $code],
        ]) ?: null;
    }

    public function decrementStock(int $productId, int $quantity): bool
    {
        return $this->db()->execute(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$quantity, $productId, $quantity]
        );
    }
}
