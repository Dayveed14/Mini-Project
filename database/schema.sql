-- UniUyo Dues Portal — database schema
-- Works on MySQL 8.0+ and MariaDB 10.4+ (the version bundled with current XAMPP).
--
-- Import into an EXISTING, empty database (this file doesn't create one,
-- because shared hosts like InfinityFree don't allow CREATE DATABASE):
--   phpMyAdmin: select your database on the left -> Import tab -> choose this file -> Go
--   command line: mysql -u root -p uniuyo_dues < database/schema.sql
--
-- No card numbers or CVVs are ever stored. Paystack keeps the card; we keep only
-- the reusable authorization token it returns plus display details (last 4, expiry, bank).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Users (students and administrators)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name      VARCHAR(60)  NOT NULL,
  last_name       VARCHAR(60)  NOT NULL,
  email           VARCHAR(190) NOT NULL,
  phone           VARCHAR(16)  NOT NULL COMMENT 'E.164, e.g. +2348031234567',
  reg_number      VARCHAR(30)  NULL     COMMENT 'Matric/registration number; NULL for admins',
  role            ENUM('student','admin') NOT NULL DEFAULT 'student',
  password_hash   VARCHAR(255) NOT NULL COMMENT 'password_hash() output (bcrypt/argon2)',
  sms_pin_hash    VARCHAR(255) NULL     COMMENT 'Hashed 4-digit PIN required for PAY-by-SMS',
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at   DATETIME     NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  UNIQUE KEY uq_users_reg_number (reg_number),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Dues: one row per levy (e.g. "SUG dues 2026/2027"). Exactly one is active.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dues (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title          VARCHAR(120) NOT NULL,
  session_label  VARCHAR(20)  NOT NULL COMMENT 'Academic session, e.g. 2026/2027',
  amount_kobo    INT UNSIGNED NOT NULL COMMENT 'Amount in kobo (NGN x 100)',
  is_active      TINYINT(1)   NOT NULL DEFAULT 0,
  due_date       DATE         NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_dues_active (is_active)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Saved cards = Paystack authorizations. Never a PAN, never a CVV.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_methods (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id             INT UNSIGNED NOT NULL,
  authorization_code  VARCHAR(64)  NOT NULL COMMENT 'Paystack AUTH_xxx token, only usable with our secret key',
  signature           VARCHAR(64)  NOT NULL COMMENT 'Paystack card fingerprint, used to de-duplicate',
  card_type           VARCHAR(30)  NULL,
  bank                VARCHAR(80)  NULL,
  last4               CHAR(4)      NOT NULL,
  exp_month           CHAR(2)      NOT NULL,
  exp_year            CHAR(4)      NOT NULL,
  is_default          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pm_user_signature (user_id, signature),
  CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Payments. A row is created as 'pending' before talking to Paystack and only
-- becomes 'success' after Paystack verification (callback or signed webhook).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NOT NULL,
  dues_id           INT UNSIGNED NOT NULL,
  reference         VARCHAR(64)  NOT NULL,
  amount_kobo       INT UNSIGNED NOT NULL,
  currency          CHAR(3)      NOT NULL DEFAULT 'NGN',
  channel           ENUM('checkout','saved_card','sms') NOT NULL,
  status            ENUM('pending','success','failed','abandoned') NOT NULL DEFAULT 'pending',
  paystack_id       BIGINT UNSIGNED NULL,
  gateway_response  VARCHAR(255) NULL,
  card_last4        CHAR(4)      NULL,
  paid_at           DATETIME     NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Database-level guarantee: at most one successful payment per student per dues.
  success_key       VARCHAR(32)  AS (IF(status = 'success', CONCAT(user_id, ':', dues_id), NULL)) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_reference (reference),
  UNIQUE KEY uq_payments_success (success_key),
  KEY idx_payments_user (user_id),
  KEY idx_payments_dues_status (dues_id, status),
  CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_payments_dues FOREIGN KEY (dues_id) REFERENCES dues (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Raw Paystack webhook log (for audit and replay).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS webhook_events (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event         VARCHAR(60)  NOT NULL,
  reference     VARCHAR(64)  NULL,
  payload       MEDIUMTEXT   NOT NULL,
  outcome       VARCHAR(120) NULL,
  received_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_webhook_reference (reference)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Inbound SMS log (from SMS Enabler).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sms_messages (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender      VARCHAR(20)  NOT NULL,
  user_id     INT UNSIGNED NULL,
  body        VARCHAR(160) NOT NULL COMMENT 'PIN digits are masked before storage',
  reply       VARCHAR(160) NULL,
  received_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sms_sender_time (sender, received_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Failed sign-in attempts, for throttling brute force.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  identifier    VARCHAR(190) NOT NULL,
  ip_address    VARCHAR(45)  NOT NULL,
  attempted_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_lookup (identifier, attempted_at),
  KEY idx_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- Starter dues so the portal works immediately. Edit from Admin > Dues.
INSERT INTO dues (title, session_label, amount_kobo, is_active, due_date)
SELECT 'Students\' Union dues', '2026/2027', 170000, 1, '2027-03-31'
WHERE NOT EXISTS (SELECT 1 FROM dues);
