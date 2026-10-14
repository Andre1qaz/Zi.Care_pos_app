-- POS Application - Stock Movements (Kartu Stok & Stok Opname)
-- MariaDB 10.6+
-- Migration: 006_stock_movements.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE pos_db;

-- ============================================================
-- STOCK MOVEMENTS
-- Setiap perubahan stok dicatat sebagai satu baris agar pergerakan
-- (penjualan, penerimaan, penyesuaian/opname) dapat diaudit.
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    movement_type ENUM('sale', 'in', 'adjustment', 'opname', 'return') NOT NULL,
    quantity INT NOT NULL COMMENT 'Perubahan stok: positif = masuk, negatif = keluar',
    stock_before INT NOT NULL DEFAULT 0,
    stock_after INT NOT NULL DEFAULT 0,
    reason VARCHAR(255) DEFAULT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    invoice_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_movements_product_id (product_id),
    KEY idx_stock_movements_type (movement_type),
    KEY idx_stock_movements_created_at (created_at),
    KEY idx_stock_movements_invoice_id (invoice_id),
    CONSTRAINT fk_stock_movements_product_id FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_movements_invoice_id FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE SET NULL,
    CONSTRAINT fk_stock_movements_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SALDO AWAL
-- Catat stok yang sudah ada saat ini sebagai satu gerakan "opname"
-- agar kartu stok tidak kosong untuk produk lama. Hanya dijalankan
-- sekali (dijaga oleh user variable agar tidak duplikat saat diulang).
-- ============================================================
SET @mv_count := (SELECT COUNT(*) FROM stock_movements);

INSERT INTO stock_movements (product_id, movement_type, quantity, stock_before, stock_after, reason, reference)
SELECT p.id, 'opname', p.stock, 0, p.stock, 'Saldo awal sistem', 'OPENING'
FROM products p
WHERE p.stock <> 0 AND @mv_count = 0;

SET FOREIGN_KEY_CHECKS = 1;
