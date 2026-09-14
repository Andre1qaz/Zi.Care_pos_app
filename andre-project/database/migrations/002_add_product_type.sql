-- POS Application - Add Product Type Differentiation
-- MariaDB 10.6+
-- Migration: 002_add_product_type.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE pos_db;

-- ============================================================
-- ADD PRODUCT TYPE COLUMNS
-- ============================================================
ALTER TABLE products
ADD COLUMN product_type ENUM('barang', 'jasa') NOT NULL DEFAULT 'barang'
AFTER description,
ADD COLUMN service_status ENUM('tersedia', 'tidak_tersedia') DEFAULT 'tersedia' NULL
AFTER product_type;

-- Add index for product_type to improve query performance
ALTER TABLE products
ADD INDEX idx_products_product_type (product_type);

SET FOREIGN_KEY_CHECKS = 1;
