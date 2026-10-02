-- Seed data for development
-- Password: Admin@123, Manager@123, Cashier@123 (bcrypt cost 12)

USE pos_db;

INSERT INTO roles (id, name, description) VALUES
(1, 'administrator', 'Full system access'),
(2, 'manager', 'Reporting and dashboard access'),
(3, 'cashier', 'Transaction and customer access')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, email, password, role_id) VALUES
('Administrator', 'admin@pos.local', '$2y$12$rY1hlx2aiWylOXtIlnYyju4LD8Rn36J4V3.09ddogAKGThMj.h.TW', 1),
('Manager', 'manager@pos.local', '$2y$12$nksZGCHSWXtRL8JGlTMNwOovKo10g3vIrPpC5CWGDZF9Jlqy6gCsu', 2),
('Cashier', 'cashier@pos.local', '$2y$12$z.hV7AXqU9l.B4hN1GirguYjH8bYoP3kUC3Uo5E82E0.iJ7c8rUCm', 3)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role_id = VALUES(role_id);

INSERT INTO categories (category_name, description) VALUES
('Makanan', 'Produk makanan'),
('Minuman', 'Produk minuman'),
('Snack', 'Camilan dan snack');

INSERT INTO products (product_code, product_name, description, price, stock, category_id) VALUES
('PRD-001', 'Nasi Goreng Spesial', 'Nasi goreng dengan telur dan ayam', 25000.00, 100, 1),
('PRD-002', 'Mie Ayam', 'Mie ayam homemade', 18000.00, 80, 1),
('PRD-003', 'Es Teh Manis', 'Teh manis dingin', 5000.00, 200, 2),
('PRD-004', 'Kopi Susu', 'Kopi dengan susu segar', 15000.00, 150, 2),
('PRD-005', 'Keripik Kentang', 'Keripik kentang original', 12000.00, 50, 3);

INSERT INTO customers (customer_name, phone, email, address) VALUES
('Walk-in Customer', NULL, NULL, NULL),
('Budi Santoso', '081234567890', 'budi@email.com', 'Jl. Merdeka No. 10, Jakarta'),
('Siti Aminah', '081987654321', 'siti@email.com', 'Jl. Sudirman No. 5, Bandung');
