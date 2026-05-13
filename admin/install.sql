-- CORO PROJECT admin / accounting schema
-- 使い方:
--   すでに作成済みのDBを選択した状態でインポートしてください。
--   DB作成・DBユーザー作成までまとめて行う場合は database/setup.sql を使います。

CREATE TABLE IF NOT EXISTS news (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  tag VARCHAR(100) NULL,
  thumb VARCHAR(500) NULL,
  excerpt TEXT NULL,
  content LONGTEXT NULL,
  content_json LONGTEXT NULL,
  url VARCHAR(500) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_news_published_order (is_published, sort_order, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talents (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  kana VARCHAR(255) NULL,
  talent_group VARCHAR(255) NULL,
  status VARCHAR(100) NULL,
  debut DATE NULL,
  last_active DATE NULL,
  avatar VARCHAR(500) NULL,
  bio TEXT NULL,
  long_bio_json LONGTEXT NULL,
  platforms_json LONGTEXT NULL,
  links_json LONGTEXT NULL,
  tags_json LONGTEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_talents_published_order (is_published, sort_order, debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_links (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  label VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_talent_links_talent (talent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_platforms (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  name VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_talent_platforms_talent (talent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inquiries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  topic VARCHAR(100) NOT NULL,
  url VARCHAR(500) NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'unread',
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inquiries_created_at (created_at),
  INDEX idx_inquiries_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inquiry_replies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  inquiry_id BIGINT UNSIGNED NOT NULL,
  admin_user_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  mail_sent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inquiry_replies_inquiry (inquiry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT INTO admin_users (login_id, password_hash, display_name, email, is_active)
SELECT 'admin', '$2y$10$2FDDJ3JTdXKU2jeQ99.7d.03/s3xeL2Pkp8.DgyhMUjTHGu5nlxNu', 'Admin', NULL, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admin_users WHERE login_id = 'admin');

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
  status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
  submitted_by VARCHAR(20) NOT NULL DEFAULT 'admin',
  portal_note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_accounting_revenues_talent_month (talent_id, year, month),
  INDEX idx_accounting_revenues_status (status),
  INDEX idx_accounting_revenues_year_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounting_invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(100) NOT NULL UNIQUE,
  talent_id VARCHAR(191) NULL,
  client_id VARCHAR(191) NULL,
  close_year INT NOT NULL,
  close_month INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  amount_jpy DECIMAL(12,2) NOT NULL,
  fx_rate DECIMAL(12,4) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'issued',
  note TEXT NULL,
  division VARCHAR(20) NOT NULL DEFAULT 'production',
  deal_id VARCHAR(191) NULL,
  project_id VARCHAR(191) NULL,
  invoice_pdf_path VARCHAR(500) NULL,
  paid_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounting_invoices_talent (talent_id),
  INDEX idx_accounting_invoices_client (client_id),
  INDEX idx_accounting_invoices_status (status),
  INDEX idx_accounting_invoices_division (division),
  INDEX idx_accounting_invoices_deal (deal_id),
  INDEX idx_accounting_invoices_project (project_id)
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

-- ============================================================
-- 共通クライアント管理
-- ============================================================

CREATE TABLE IF NOT EXISTS clients (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'individual',
  rank VARCHAR(50) NOT NULL DEFAULT 'new',
  memo TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clients_rank (rank),
  INDEX idx_clients_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Business 事業部
-- ============================================================

CREATE TABLE IF NOT EXISTS biz_ext_talents (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  channel_url VARCHAR(500) NULL,
  genre VARCHAR(100) NULL,
  subscriber_count INT NULL,
  memo TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS biz_deals (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  client_id VARCHAR(191) NULL,
  title VARCHAR(255) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT '相談中',
  description TEXT NULL,
  budget DECIMAL(12,2) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'manual',
  memo TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_biz_deals_status (status),
  INDEX idx_biz_deals_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS biz_deal_candidates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  deal_id VARCHAR(191) NOT NULL,
  talent_type VARCHAR(20) NOT NULL DEFAULT 'external',
  talent_id VARCHAR(191) NULL,
  ext_talent_id VARCHAR(191) NULL,
  status VARCHAR(50) NOT NULL DEFAULT '提案中',
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_biz_deal_candidates_deal (deal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Creative 事業部
-- ============================================================

CREATE TABLE IF NOT EXISTS cre_creators (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  skill_tags_json LONGTEXT NULL,
  rate_memo TEXT NULL,
  contact VARCHAR(255) NULL,
  portfolio_url VARCHAR(500) NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'external',
  memo TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cre_creators_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cre_projects (
  id VARCHAR(191) NOT NULL PRIMARY KEY,
  client_id VARCHAR(191) NULL,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL DEFAULT 'illustration',
  status VARCHAR(50) NOT NULL DEFAULT '受付',
  creator_id VARCHAR(191) NULL,
  deadline DATE NULL,
  deliverable_url VARCHAR(500) NULL,
  client_amount DECIMAL(12,2) NULL,
  creator_amount DECIMAL(12,2) NULL,
  memo TEXT NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'manual',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cre_projects_status (status),
  INDEX idx_cre_projects_client (client_id),
  INDEX idx_cre_projects_creator (creator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inquiry_replies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  inquiry_id BIGINT UNSIGNED NOT NULL,
  admin_user_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  mail_sent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inquiry_replies_inquiry (inquiry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Talent Portal（タレントポータル）
-- ============================================================

-- ※ accounting_revenues へのカラム追加は別ファイル admin/portal_migrate.sql を
--   一度だけ実行してください（既存DBへの影響を分離するため）

CREATE TABLE IF NOT EXISTS talent_portal_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL UNIQUE,
  login_id VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  login_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_talent_portal_accounts_talent (talent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_portal_notices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_talent_portal_notices_published (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
