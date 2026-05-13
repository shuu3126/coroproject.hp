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
  'updated_by',
  'BIGINT UNSIGNED NULL AFTER `created_by`'
);

CALL coro_add_column_if_missing(
  'talent_portal_notices',
  'updated_by',
  'BIGINT UNSIGNED NULL AFTER `created_by`'
);

DROP PROCEDURE IF EXISTS coro_add_column_if_missing;
