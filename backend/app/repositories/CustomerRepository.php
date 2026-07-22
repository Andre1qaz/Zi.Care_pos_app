<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function findAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $conditions = ['1=1'];
        $bind = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(customer_name LIKE :search: OR phone LIKE :search: OR email LIKE :search:)';
            $bind['search'] = '%' . $filters['search'] . '%';
        }

        $offset = ($page - 1) * $limit;

        $customers = Customer::find([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
            'limit'      => $limit,
            'offset'     => $offset,
            'order'      => 'customer_name ASC',
        ]);

        $total = Customer::count([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
        ]);

        return ['items' => $customers->toArray(), 'total' => $total];
    }

    public function findById(int $id): ?Customer
    {
        return Customer::findFirst($id) ?: null;
    }
}
