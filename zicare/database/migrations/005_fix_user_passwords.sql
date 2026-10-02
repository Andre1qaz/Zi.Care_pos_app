-- Fix default user passwords to match documentation (bcrypt cost 12)
-- Administrator: Admin@123 | Manager: Manager@123 | Cashier: Cashier@123

USE pos_db;

UPDATE users SET password = '$2y$12$rY1hlx2aiWylOXtIlnYyju4LD8Rn36J4V3.09ddogAKGThMj.h.TW' WHERE email = 'admin@pos.local';
UPDATE users SET password = '$2y$12$nksZGCHSWXtRL8JGlTMNwOovKo10g3vIrPpC5CWGDZF9Jlqy6gCsu' WHERE email = 'manager@pos.local';
UPDATE users SET password = '$2y$12$z.hV7AXqU9l.B4hN1GirguYjH8bYoP3kUC3Uo5E82E0.iJ7c8rUCm' WHERE email = 'cashier@pos.local';
