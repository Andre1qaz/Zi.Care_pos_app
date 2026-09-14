-- POS Application - Fix Service Stock
-- MariaDB 10.6+
-- Migration: 003_fix_service_stock.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE pos_db;

-- ============================================================
-- ENSURE SERVICES HAVE STOCK = 0
-- ============================================================
UPDATE products
SET stock = 0
WHERE product_type = 'jasa' AND stock != 0;

-- ============================================================
-- ADD CHECK CONSTRAINT TO PREVENT STOCK > 0 FOR SERVICES
-- ============================================================
-- Note: MariaDB doesn't support CHECK constraints in the same way as MySQL 8.0+
-- We'll handle this at the application level instead

SET FOREIGN_KEY_CHECKS = 1;
