<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Invoice;

class InvoiceRepository extends BaseRepository
{
    public function findById(int $id): ?Invoice
    {
        return Invoice::findFirst([
            'conditions' => 'id = :id:',
            'bind'       => ['id' => $id],
        ]) ?: null;
    }

    public function findWithDetails(int $id): ?array
    {
        $invoice = $this->findById($id);
        if (!$invoice) {
            return null;
        }

        $details = $this->db()->query(
            'SELECT id.*, p.product_name, p.product_code
             FROM invoice_details id
             JOIN products p ON p.id = id.product_id
             WHERE id.invoice_id = ?',
            [$id]
        )->fetchAll();

        $payments = $this->db()->query(
            'SELECT p.*, u.name AS cashier_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.cashier_id
             WHERE p.invoice_id = ?',
            [$id]
        )->fetchAll();

        $cashier = $this->db()->query(
            'SELECT u.name FROM users u WHERE u.id = ?',
            [$invoice->cashier_id]
        )->fetch();

        $customer = null;
        if ($invoice->customer_id) {
            $customer = $this->db()->query(
                'SELECT customer_name, phone, email FROM customers WHERE id = ?',
                [$invoice->customer_id]
            )->fetch();
        }

        return [
            'invoice'  => $invoice->toArray(),
            'details'  => $details,
            'payments' => $payments,
            'cashier'  => $cashier,
            'customer' => $customer,
        ];
    }

    public function findAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $conditions = ['1=1'];
        $bind = [];

        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(created_at) >= :date_from:';
            $bind['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(created_at) <= :date_to:';
            $bind['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['payment_status'])) {
            $statuses = array_values((array) $filters['payment_status']);
            $placeholders = [];
            foreach ($statuses as $index => $status) {
                $key = 'payment_status_' . $index;
                $placeholders[] = ':' . $key . ':';
                $bind[$key] = $status;
            }
            $conditions[] = 'payment_status IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($filters['sync_status'])) {
            $syncStatuses = array_values((array) $filters['sync_status']);
            $placeholders = [];
            foreach ($syncStatuses as $index => $status) {
                $key = 'sync_status_' . $index;
                $placeholders[] = ':' . $key . ':';
                $bind[$key] = $status;
            }
            $conditions[] = 'sync_status IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';

            // Phalcon Model doesn't accept a raw SQL subquery here, so resolve
            // matching customer ids first and bind them as an array.
            $matchingCustomerIds = [];
            $customers = Customer::find([
                'columns'    => 'id',
                'conditions' => 'customer_name LIKE :search:',
                'bind'       => ['search' => $term],
            ]);
            foreach ($customers as $customer) {
                $matchingCustomerIds[] = (int) $customer->id;
            }

            if (!empty($matchingCustomerIds)) {
                $conditions[] = '(invoice_number LIKE :search: OR customer_id IN ({customer_ids:array}))';
                $bind['search'] = $term;
                $bind['customer_ids'] = $matchingCustomerIds;
            } else {
                $conditions[] = 'invoice_number LIKE :search:';
                $bind['search'] = $term;
            }
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $limit;

        $invoices = Invoice::find([
            'conditions' => $where,
            'bind'       => $bind,
            'limit'      => $limit,
            'offset'     => $offset,
            'order'      => 'created_at DESC',
        ]);

        $items = $invoices->toArray();

        // Attach customer details so list views can show the customer name.
        $customerIds = [];
        foreach ($items as $item) {
            if (!empty($item['customer_id'])) {
                $customerIds[] = (int) $item['customer_id'];
            }
        }

        if (!empty($customerIds)) {
            $customers = Customer::find([
                'conditions' => 'id IN ({ids:array})',
                'bind'       => ['ids' => array_values(array_unique($customerIds))],
            ]);

            $customerMap = [];
            foreach ($customers as $customer) {
                $customerMap[(int) $customer->id] = $customer->toArray();
            }

            foreach ($items as &$item) {
                $item['customer'] = $customerMap[(int) ($item['customer_id'] ?? 0)] ?? null;
            }
            unset($item);
        }

        $total = Invoice::count([
            'conditions' => $where,
            'bind'       => $bind,
        ]);

        return ['items' => $items, 'total' => $total];
    }
}
