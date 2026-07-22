<?php

declare(strict_types=1);

namespace App\Repositories;

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
            'SELECT * FROM payments WHERE invoice_id = ?',
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

        $offset = ($page - 1) * $limit;

        $invoices = Invoice::find([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
            'limit'      => $limit,
            'offset'     => $offset,
            'order'      => 'created_at DESC',
        ]);

        $total = Invoice::count([
            'conditions' => implode(' AND ', $conditions),
            'bind'       => $bind,
        ]);

        return ['items' => $invoices->toArray(), 'total' => $total];
    }
}
