<?php

declare(strict_types=1);

namespace App\Services;

use Phalcon\Di\Di;

class ReportService
{
    private function db()
    {
        return Di::getDefault()->getShared('db');
    }

    public function dailySales(string $date): array
    {
        $result = $this->db()->query(
            "SELECT i.*, u.name AS cashier_name, c.customer_name
             FROM invoices i
             LEFT JOIN users u ON u.id = i.cashier_id
             LEFT JOIN customers c ON c.id = i.customer_id
             WHERE DATE(i.created_at) = ? AND i.payment_status = 'paid'
             ORDER BY i.created_at DESC",
            [$date]
        )->fetchAll();

        $summary = $this->db()->query(
            "SELECT COUNT(*) AS total_transactions, COALESCE(SUM(total_amount), 0) AS total_revenue
             FROM invoices WHERE DATE(created_at) = ? AND payment_status = 'paid'",
            [$date]
        )->fetch();

        return ['summary' => $summary, 'transactions' => $result];
    }

    public function monthlySales(int $year, int $month): array
    {
        $result = $this->db()->query(
            "SELECT DATE(created_at) AS date, COUNT(*) AS transactions, SUM(total_amount) AS revenue
             FROM invoices
             WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND payment_status = 'paid'
             GROUP BY DATE(created_at) ORDER BY date ASC",
            [$year, $month]
        )->fetchAll();

        $summary = $this->db()->query(
            "SELECT COUNT(*) AS total_transactions, COALESCE(SUM(total_amount), 0) AS total_revenue
             FROM invoices
             WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND payment_status = 'paid'",
            [$year, $month]
        )->fetch();

        return ['summary' => $summary, 'daily_breakdown' => $result];
    }

    public function productSales(string $dateFrom, string $dateTo): array
    {
        return $this->db()->query(
            "SELECT p.product_code, p.product_name, SUM(id.quantity) AS total_qty,
                    SUM(id.subtotal) AS total_revenue
             FROM invoice_details id
             JOIN products p ON p.id = id.product_id
             JOIN invoices i ON i.id = id.invoice_id
             WHERE DATE(i.created_at) BETWEEN ? AND ? AND i.payment_status = 'paid'
             GROUP BY p.id, p.product_code, p.product_name
             ORDER BY total_revenue DESC",
            [$dateFrom, $dateTo]
        )->fetchAll();
    }

    public function payments(string $dateFrom, string $dateTo): array
    {
        return $this->db()->query(
            "SELECT p.*, i.invoice_number
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             WHERE DATE(p.created_at) BETWEEN ? AND ?
             ORDER BY p.created_at DESC",
            [$dateFrom, $dateTo]
        )->fetchAll();
    }

    public function customerTransactions(int $customerId, string $dateFrom, string $dateTo): array
    {
        return $this->db()->query(
            "SELECT i.*, c.customer_name
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             WHERE i.customer_id = ? AND DATE(i.created_at) BETWEEN ? AND ?
             ORDER BY i.created_at DESC",
            [$customerId, $dateFrom, $dateTo]
        )->fetchAll();
    }
}
