# CORO PROJECT HP — 進捗管理

## 次にやること

- [ ] migrate APIの実行（DBにdue_date / payment_bank_infoカラム追加）
  ```powershell
  $api = "https://coroproject.jp/admin/api"
  $key = "a473997a0ca9348cbcdf58aa2bea270f3ff27edc6eadbfca80bad2e1ec2ffd20"
  $headers = @{ "X-Api-Key" = $key; "Content-Type" = "application/json" }
  Invoke-WebRequest -Uri "$api/migrate" -Method POST -Headers $headers -UseBasicParsing | Select-Object -Expand Content
  ```
- [ ] migrate完了後、migrate.phpを削除してデプロイ
- [ ] invoices / payments エンドポイントの動作確認
- [ ] 全エンドポイントのCRUDテスト（POST/PATCH/DELETE）

## 現在の状態

**APIエンドポイント**: `https://coroproject.jp/admin/api/`
**認証方式**: リクエストヘッダー `X-Api-Key: <key>`（_bootstrap.php参照）

| エンドポイント | GET一覧 | GET詳細 | POST | PATCH | DELETE |
|---|---|---|---|---|---|
| /talents | ✅ (2件) | 未確認 | ✅ | ✅ | - |
| /clients | ✅ (0件) | 未確認 | ✅ | ✅ | ✅ |
| /deals | ✅ (0件) | 未確認 | ✅ | ✅ | ✅ |
| /invoices | ❌ 500 | - | - | - | - |
| /revenues | ✅ (16件) | - | ✅ | ✅ | - |
| /journal | ✅ | - | ✅ | - | - |
| /payments | ❌ 500 | - | - | - | - |
| /update | - | - | ✅ | - | - |

**500エラー原因**: accounting_invoicesテーブルにdue_date / payment_bank_infoカラムが未追加

## セッションログ

### 2026-06-04
- 管理サイト全機能APIの動作確認を実施
- admin/api/ 配下に8エンドポイントが存在することを確認
- talents/clients/deals/revenues/journalは正常動作
- invoices/paymentsは500エラー → DBスキーマの差異が原因と特定
  - 旧スキーマ: due_date / payment_bank_info カラムなし
  - 現コード: これらのカラムを参照しているため PDOException が発生
- migrate.php（一時マイグレーション用エンドポイント）を作成・デプロイ済み
- HP/CLAUDE.md を新規作成（セキュリティルール・デプロイ手順）
- .company/CLAUDE.md にプロジェクトCLAUDE.md読み込みルールを追記
