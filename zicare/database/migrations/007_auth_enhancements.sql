-- POS Application - Auth enhancements (security & login UX)
-- MariaDB 10.6+
-- Migration: 007_auth_enhancements.sql
--
-- Menambahkan dukungan:
--   * Rate limiting / account lockout (failed_login_attempts, locked_until)
--   * Info login terakhir (last_login_at, last_login_ip, last_login_user_agent)
--   * Two-Factor Authentication (TOTP) — two_factor_enabled, two_factor_secret
--   * Lupa password (tabel password_reset_tokens)

USE pos_db;

SET NAMES utf8mb4;

-- ============================================================
-- USERS - kolom keamanan & info login
-- ============================================================
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL AFTER failed_login_attempts,
    ADD COLUMN IF NOT EXISTS last_login_at DATETIME DEFAULT NULL AFTER locked_until,
    ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) DEFAULT NULL AFTER last_login_at,
    ADD COLUMN IF NOT EXISTS last_login_user_agent VARCHAR(255) DEFAULT NULL AFTER last_login_ip,
    ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER last_login_user_agent,
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(64) DEFAULT NULL AFTER two_factor_enabled;

-- ============================================================
-- PASSWORD RESET TOKENS
-- Hanya hash token yang disimpan (token mentah tidak pernah ditulis ke DB).
-- ============================================================
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    requested_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user_id (user_id),
    KEY idx_password_reset_expires_at (expires_at),
    CONSTRAINT fk_password_reset_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
