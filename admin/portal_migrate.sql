-- ============================================================
-- Talent Portal マイグレーション
-- 既存の accounting_revenues テーブルにカラムを追加します。
-- ※ 一度だけ実行してください。
-- ============================================================

-- status: 'pending'（タレント申告・要確認）/ 'confirmed'（管理者確定）/ 'rejected'（要修正）
-- submitted_by: 'admin' / 'talent'
-- portal_note: タレントからのコメント

ALTER TABLE accounting_revenues
  ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'confirmed' AFTER memo,
  ADD COLUMN IF NOT EXISTS submitted_by VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER status,
  ADD COLUMN IF NOT EXISTS portal_note TEXT NULL AFTER submitted_by;

-- MySQL 5.x（ADD COLUMN IF NOT EXISTS 非対応）の場合は下記を使用:
-- ALTER TABLE accounting_revenues ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'confirmed' AFTER memo;
-- ALTER TABLE accounting_revenues ADD COLUMN submitted_by VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER status;
-- ALTER TABLE accounting_revenues ADD COLUMN portal_note TEXT NULL AFTER submitted_by;
