<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Phalcon\Di\Di;

class CustomerService
{
    private CustomerRepository $repository;

    public function __construct()
    {
        $this->repository = Di::getDefault()->getShared('customerRepository');
    }

    public function list(array $filters, int $page, int $limit): array
    {
        return $this->repository->findAll($filters, $page, $limit);
    }

    public function get(int $id): array
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new NotFoundException('Customer not found');
        }
        return $customer->toArray();
    }

    public function create(array $data): array
    {
        if (empty($data['customer_name'])) {
            throw new ValidationException(['customer_name' => 'Customer name is required']);
        }

        $customer = new Customer();
        $customer->assign($data);
        $customer->save();

        return $customer->toArray();
    }

    public function update(int $id, array $data): array
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new NotFoundException('Customer not found');
        }

        $customer->assign($data);
        $customer->save();

        return $customer->toArray();
    }

    public function delete(int $id): void
    {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new NotFoundException('Customer not found');
        }
        $customer->delete();
    }
}
