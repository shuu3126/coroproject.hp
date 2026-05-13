-- ============================================================
-- Talent Portal マイグレーション
-- 既存DBにタレントポータル用テーブル・カラムを追加します。
-- ※ 一度だけ実行してください。
-- ============================================================

-- status: 'pending'（タレント申告・要確認）/ 'confirmed'（管理者確定）/ 'rejected'（要修正）
-- submitted_by: 'admin' / 'talent'
-- portal_note: タレントからのコメント

ALTER TABLE accounting_revenues
  ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'confirmed' AFTER memo,
  ADD COLUMN IF NOT EXISTS submitted_by VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER status,
  ADD COLUMN IF NOT EXISTS portal_note TEXT NULL AFTER submitted_by;

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

-- 途中版のマイグレーションを実行済みの場合に不足列を補います。
ALTER TABLE talent_portal_accounts
  ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER locked_until,
  ADD COLUMN IF NOT EXISTS updated_by BIGINT UNSIGNED NULL AFTER created_by;

ALTER TABLE talent_portal_notices
  ADD COLUMN IF NOT EXISTS updated_by BIGINT UNSIGNED NULL AFTER created_by;

-- MySQL 5.x（ADD COLUMN IF NOT EXISTS 非対応）の場合は下記を使用:
-- ALTER TABLE accounting_revenues ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'confirmed' AFTER memo;
-- ALTER TABLE accounting_revenues ADD COLUMN submitted_by VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER status;
-- ALTER TABLE accounting_revenues ADD COLUMN portal_note TEXT NULL AFTER submitted_by;
-- ALTER TABLE talent_portal_accounts ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER locked_until;
-- ALTER TABLE talent_portal_accounts ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by;
-- ALTER TABLE talent_portal_notices ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by;
