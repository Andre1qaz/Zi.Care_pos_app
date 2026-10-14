<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StockMovement;

class StockMovementRepository extends BaseRepository
{
    /**
     * Catat satu pergerakan stok.
     */
    public function record(array $data): void
    {
        $movement = new StockMovement();
        $movement->assign($data);
        $movement->save();
    }

    /**
     * Daftar pergerakan stok untuk satu produk (dengan nama kasir/pengguna).
     */
    public function findByProduct(int $productId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $conditions = ['sm.product_id = ?'];
        $bind = [$productId];

        if (!empty($filters['type'])) {
            $conditions[] = 'sm.movement_type = ?';
            $bind[] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(sm.created_at) >= ?';
            $bind[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(sm.created_at) <= ?';
            $bind[] = $filters['date_to'];
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $limit;

        $items = $this->db()->query(
            "SELECT sm.*, u.name AS user_name, i.invoice_number
             FROM stock_movements sm
             LEFT JOIN users u ON u.id = sm.user_id
             LEFT JOIN invoices i ON i.id = sm.invoice_id
             WHERE {$where}
             ORDER BY sm.created_at DESC, sm.id DESC
             LIMIT {$offset}, {$limit}",
            $bind
        )->fetchAll();

        $total = (int) $this->db()->query(
            "SELECT COUNT(*) AS total FROM stock_movements sm WHERE {$where}",
            $bind
        )->fetch()['total'];

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Ringkasan pergerakan stok untuk satu produk.
     */
    public function summary(int $productId): array
    {
        $row = $this->db()->query(
            "SELECT
                COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END), 0) AS total_out,
                COUNT(*) AS total_movements
             FROM stock_movements
             WHERE product_id = ?",
            [$productId]
        )->fetch();

        return [
            'total_in'        => (int) ($row['total_in'] ?? 0),
            'total_out'       => (int) ($row['total_out'] ?? 0),
            'total_movements' => (int) ($row['total_movements'] ?? 0),
        ];
    }
}
