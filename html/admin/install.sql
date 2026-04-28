-- CORO PROJECT admin / accounting schema
-- 実行前提: 既存 public tables (news, talents, talent_links, talent_platforms) はそのまま使う

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  login_id VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action_type VARCHAR(50) NOT NULL,
  target_type VARCHAR(50) NOT NULL,
  target_id VARCHAR(191) NULL,
  summary VARCHAR(255) NOT NULL,
  details_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_logs_created_at (created_at),
  INDEX idx_admin_logs_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value LONGTEXT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_talent_settings (
  talent_id VARCHAR(191) PRIMARY KEY,
  office_share_percent DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  invoice_name VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  bank_info TEXT NULL,
  memo TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounting_talent_settings_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_revenues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  year INT NOT NULL,
  month INT NOT NULL,
  currency VARCHAR(10) NOT NULL,
  amount_streaming DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_goods DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_sponsor DECIMAL(12,2) NOT NULL DEFAULT 0,
  evidence_path VARCHAR(500) NULL,
  memo TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_accounting_revenues_talent_month (talent_id, year, month),
  INDEX idx_accounting_revenues_year_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(100) NOT NULL UNIQUE,
  talent_id VARCHAR(191) NOT NULL,
  close_year INT NOT NULL,
  close_month INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  amount_jpy DECIMAL(12,2) NOT NULL,
  fx_rate DECIMAL(12,4) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'issued',
  note TEXT NULL,
  invoice_pdf_path VARCHAR(500) NULL,
  paid_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounting_invoices_talent (talent_id),
  INDEX idx_accounting_invoices_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_invoice_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  description VARCHAR(255) NOT NULL,
  amount_jpy DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_accounting_invoice_items_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_invoiced_months (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  talent_id VARCHAR(191) NOT NULL,
  year INT NOT NULL,
  month INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_accounting_invoiced_months_unique (talent_id, year, month),
  INDEX idx_accounting_invoiced_months_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL UNIQUE,
  receipt_pdf_path VARCHAR(500) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  issued_by BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_journal_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounting_journal_categories_kind (kind, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_journal_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  kind VARCHAR(20) NOT NULL,
  category VARCHAR(100) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT NOT NULL,
  talent_id VARCHAR(191) NULL,
  invoice_id BIGINT UNSIGNED NULL,
  source VARCHAR(50) NOT NULL,
  evidence_path VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounting_journal_entries_date (date),
  INDEX idx_accounting_journal_entries_kind (kind),
  INDEX idx_accounting_journal_entries_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'income', '配信収益', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='income' AND name='配信収益');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'income', 'グッズ売上', 2, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='income' AND name='グッズ売上');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'income', '企業案件収入', 3, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='income' AND name='企業案件収入');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'income', 'その他収入', 4, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='income' AND name='その他収入');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'expense', 'イラスト外注費', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='expense' AND name='イラスト外注費');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'expense', 'サーバー費', 2, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='expense' AND name='サーバー費');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'expense', 'ソフト使用料', 3, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='expense' AND name='ソフト使用料');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'expense', '広告費', 4, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='expense' AND name='広告費');
INSERT INTO accounting_journal_categories(kind, name, sort_order, is_active)
SELECT 'expense', 'その他経費', 5, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM accounting_journal_categories WHERE kind='expense' AND name='その他経費');
