<?php

declare(strict_types=1);

namespace App\Services;

use Phalcon\Di\Di;

class DashboardService
{
    public function getStats(): array
    {
        $db = Di::getDefault()->getShared('db');

        $todaySales = $db->query(
            "SELECT COALESCE(SUM(total_amount), 0) AS total FROM invoices
             WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'"
        )->fetch();

        $todayTransactions = $db->query(
            "SELECT COUNT(*) AS total FROM invoices WHERE DATE(created_at) = CURDATE()"
        )->fetch();

        $totalProducts = $db->query(
            "SELECT COUNT(*) AS total FROM products"
        )->fetch();

        $monthlyRevenue = $db->query(
            "SELECT COALESCE(SUM(total_amount), 0) AS total FROM invoices
             WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
             AND payment_status = 'paid'"
        )->fetch();

        $topProducts = $db->query(
            "SELECT p.product_name, SUM(id.quantity) AS total_qty, SUM(id.subtotal) AS revenue
             FROM invoice_details id
             JOIN products p ON p.id = id.product_id
             JOIN invoices i ON i.id = id.invoice_id
             WHERE DATE(i.created_at) = CURDATE() AND i.payment_status = 'paid'
             GROUP BY p.id, p.product_name
             ORDER BY total_qty DESC LIMIT 5"
        )->fetchAll();

        $paymentStats = $db->query(
            "SELECT payment_method, COUNT(*) AS count, SUM(amount) AS total
             FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'
             GROUP BY payment_method"
        )->fetchAll();

        $monthlyChart = $db->query(
            "SELECT DATE(created_at) AS date, SUM(total_amount) AS revenue
             FROM invoices
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'paid'
             GROUP BY DATE(created_at) ORDER BY date ASC"
        )->fetchAll();

        return [
            'total_sales_today'      => (float) $todaySales['total'],
            'total_transactions'     => (int) $todayTransactions['total'],
            'total_products'         => (int) $totalProducts['total'],
            'total_invoices'         => (int) $todayTransactions['total'],
            'total_revenue'          => (float) $todaySales['total'],
            'monthly_revenue'        => (float) $monthlyRevenue['total'],
            'top_selling_products'   => $topProducts,
            'payment_statistics'     => $paymentStats,
            'monthly_revenue_chart'  => $monthlyChart,
        ];
    }
}
