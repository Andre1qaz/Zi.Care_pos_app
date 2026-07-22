-- Partial Payment System Migration
-- Add support for outstanding payments, payment history, and due dates
-- Migration: 004_partial_payment_system.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE pos_db;

-- ============================================================
-- UPDATE INVOICES TABLE
-- ============================================================

-- Add due_date column for payment deadline
ALTER TABLE invoices 
ADD COLUMN due_date DATETIME DEFAULT NULL AFTER created_at;

-- Add outstanding_balance column (calculated field)
ALTER TABLE invoices 
ADD COLUMN outstanding_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER paid_amount;

-- Update payment_status enum to include 'overdue'
ALTER TABLE invoices 
MODIFY COLUMN payment_status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'unpaid';

-- Add index on due_date for overdue queries
ALTER TABLE invoices 
ADD INDEX idx_invoices_due_date (due_date);

-- ============================================================
-- UPDATE PAYMENTS TABLE
-- ============================================================

-- Add cashier_id to track who processed each payment
ALTER TABLE payments 
ADD COLUMN cashier_id INT UNSIGNED DEFAULT NULL AFTER invoice_id;

-- Add notes field for payment descriptions
ALTER TABLE payments 
ADD COLUMN notes TEXT DEFAULT NULL AFTER payment_reference;

-- Add foreign key constraint for cashier_id
ALTER TABLE payments 
ADD CONSTRAINT fk_payments_cashier_id FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add index on cashier_id
ALTER TABLE payments 
ADD INDEX idx_payments_cashier_id (cashier_id);

-- ============================================================
-- UPDATE EXISTING DATA
-- ============================================================

-- Set outstanding_balance for existing invoices
UPDATE invoices 
SET outstanding_balance = total_amount - paid_amount 
WHERE outstanding_balance = 0;

-- Update payment_status for existing invoices based on paid_amount
UPDATE invoices 
SET payment_status = CASE
    WHEN paid_amount = 0 THEN 'unpaid'
    WHEN paid_amount >= total_amount THEN 'paid'
    ELSE 'partial'
END
WHERE payment_status IN ('pending', 'paid', 'partial');

-- Set default due_date to 7 days from creation for existing invoices
UPDATE invoices 
SET due_date = DATE_ADD(created_at, INTERVAL 7 DAY)
WHERE due_date IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
