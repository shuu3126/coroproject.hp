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

DROP PROCEDURE IF EXISTS coro_add_column_if_missing;
