-- ============================================================
-- Talent Portal マイグレーション
-- 既存DBにタレントポータル用テーブル・カラムを追加します。
--
-- phpMyAdmin / 古いMySQLでも実行できるように、
-- ALTER TABLE ... ADD COLUMN IF NOT EXISTS は使っていません。
-- ※ 一度実行後に再実行しても、既存カラムはスキップされます。
-- ============================================================

CREATE TABLE IF NOT EXISTS talent_portal_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL UNIQUE,
  login_id VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  login_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  password_changed_at DATETIME NULL,
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

CREATE TABLE IF NOT EXISTS talent_profile_change_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  payload_json LONGTEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  admin_note TEXT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_talent_profile_requests_talent (talent_id),
  INDEX idx_talent_profile_requests_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_portal_activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  detail TEXT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_portal_activity_talent (talent_id, created_at),
  INDEX idx_portal_activity_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_twitch_csv_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  talent_id VARCHAR(191) NOT NULL,
  report_year INT NOT NULL,
  report_month INT NOT NULL,
  original_filename VARCHAR(255) NULL,
  file_path VARCHAR(500) NULL,
  row_count INT NOT NULL DEFAULT 0,
  total_streams INT NOT NULL DEFAULT 0,
  total_minutes DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_views INT NOT NULL DEFAULT 0,
  avg_viewers DECIMAL(12,2) NOT NULL DEFAULT 0,
  peak_viewers INT NOT NULL DEFAULT 0,
  followers_gained INT NOT NULL DEFAULT 0,
  chat_messages INT NOT NULL DEFAULT 0,
  estimated_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
  currency VARCHAR(12) NOT NULL DEFAULT 'JPY',
  summary_json LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'submitted',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_twitch_reports_talent_month (talent_id, report_year, report_month),
  INDEX idx_twitch_reports_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talent_twitch_csv_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  report_id BIGINT UNSIGNED NOT NULL,
  stream_date DATETIME NULL,
  title VARCHAR(255) NULL,
  duration_minutes DECIMAL(10,2) NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  avg_viewers DECIMAL(10,2) NOT NULL DEFAULT 0,
  peak_viewers INT NOT NULL DEFAULT 0,
  followers_gained INT NOT NULL DEFAULT 0,
  chat_messages INT NOT NULL DEFAULT 0,
  estimated_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
  raw_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_twitch_rows_report (report_id),
  INDEX idx_twitch_rows_date (stream_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mail_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  account_id BIGINT UNSIGNED NULL,
  account_email VARCHAR(191) NULL,
  mailbox VARCHAR(30) NOT NULL DEFAULT 'inbox',
  direction VARCHAR(20) NOT NULL DEFAULT 'inbound',
  uidl VARCHAR(191) NULL,
  message_id VARCHAR(191) NULL,
  thread_key VARCHAR(191) NULL,
  from_name VARCHAR(191) NULL,
  from_email VARCHAR(191) NULL,
  to_text TEXT NULL,
  cc_text TEXT NULL,
  bcc_text TEXT NULL,
  subject VARCHAR(500) NULL,
  body_text LONGTEXT NULL,
  body_html LONGTEXT NULL,
  raw_headers LONGTEXT NULL,
  has_attachments TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'unread',
  is_starred TINYINT(1) NOT NULL DEFAULT 0,
  received_at DATETIME NULL,
  sent_at DATETIME NULL,
  admin_user_id BIGINT UNSIGNED NULL,
  reply_to_mail_id BIGINT UNSIGNED NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  linked_inquiry_id BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_mail_messages_uidl (uidl),
  INDEX idx_mail_messages_mailbox_created (mailbox, created_at),
  INDEX idx_mail_messages_status (status),
  INDEX idx_mail_messages_direction (direction),
  INDEX idx_mail_messages_from_email (from_email),
  INDEX idx_mail_messages_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mail_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(120) NOT NULL,
  email VARCHAR(191) NOT NULL,
  smtp_host VARCHAR(191) NOT NULL DEFAULT 'localhost',
  smtp_port INT NOT NULL DEFAULT 25,
  smtp_secure VARCHAR(20) NOT NULL DEFAULT 'none',
  smtp_user VARCHAR(191) NULL,
  smtp_pass VARCHAR(255) NULL,
  receive_protocol VARCHAR(20) NOT NULL DEFAULT 'imap',
  receive_host VARCHAR(191) NOT NULL DEFAULT 's221.myssl.jp',
  receive_port INT NOT NULL DEFAULT 993,
  receive_encryption VARCHAR(20) NOT NULL DEFAULT 'ssl',
  receive_user VARCHAR(191) NULL,
  receive_pass VARCHAR(255) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_sync_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mail_accounts_email (email),
  INDEX idx_mail_accounts_active (is_active, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS coro_add_column_if_missing;

DELIMITER //
CREATE PROCEDURE coro_add_column_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_column_name VARCHAR(64),
  IN p_column_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table_name
       AND COLUMN_NAME = p_column_name
  ) THEN
    SET @coro_sql = CONCAT(
      'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
      '` ADD COLUMN `', REPLACE(p_column_name, '`', '``'),
      '` ', p_column_definition
    );
    PREPARE coro_stmt FROM @coro_sql;
    EXECUTE coro_stmt;
    DEALLOCATE PREPARE coro_stmt;
  END IF;
END//
DELIMITER ;

-- accounting_revenues: タレント申告ステータス
CALL coro_add_column_if_missing(
  'accounting_revenues',
  'status',
  'VARCHAR(20) NOT NULL DEFAULT ''confirmed'' AFTER `memo`'
);

CALL coro_add_column_if_missing(
  'accounting_revenues',
  'submitted_by',
  'VARCHAR(20) NOT NULL DEFAULT ''admin'' AFTER `status`'
);

CALL coro_add_column_if_missing(
  'accounting_revenues',
  'portal_note',
  'TEXT NULL AFTER `submitted_by`'
);

-- 途中版を実行済みの場合の不足列補完
CALL coro_add_column_if_missing(
  'talent_portal_accounts',
  'created_by',
  'BIGINT UNSIGNED NULL AFTER `locked_until`'
);

CALL coro_add_column_if_missing(
  'talent_portal_accounts',
  'password_changed_at',
  'DATETIME NULL AFTER `locked_until`'
);

CALL coro_add_column_if_missing(
  'talent_portal_accounts',
  'updated_by',
  'BIGINT UNSIGNED NULL AFTER `created_by`'
);

-- accounting_talent_settings: タレント本人がポータルから更新するプロフィール
CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'real_name',
  'VARCHAR(255) NULL AFTER `email`'
);

CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'phone',
  'VARCHAR(50) NULL AFTER `real_name`'
);

CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'postal_code',
  'VARCHAR(20) NULL AFTER `phone`'
);

CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'address',
  'TEXT NULL AFTER `postal_code`'
);

CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'emergency_contact',
  'TEXT NULL AFTER `bank_info`'
);

CALL coro_add_column_if_missing(
  'accounting_talent_settings',
  'profile_note',
  'TEXT NULL AFTER `emergency_contact`'
);

CALL coro_add_column_if_missing(
  'talent_portal_notices',
  'updated_by',
  'BIGINT UNSIGNED NULL AFTER `created_by`'
);

CALL coro_add_column_if_missing(
  'mail_messages',
  'account_id',
  'BIGINT UNSIGNED NULL AFTER `id`'
);

CALL coro_add_column_if_missing(
  'mail_messages',
  'account_email',
  'VARCHAR(191) NULL AFTER `account_id`'
);

DROP PROCEDURE IF EXISTS coro_add_column_if_missing;
