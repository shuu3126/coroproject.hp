# CORO PROJECT HP — 進捗管理

## 次にやること

- [ ] journalのテスト用レコード（id=20）を管理画面UIから削除
- [ ] revenuesのAPIテスト用レコード（id=19: talent-2, 2026/1, 全額0）を確認・必要なら削除
- [ ] 管理画面からAPIを実際に使った操作フローを確立する（コマンドリファレンス整備）

## APIエンドポイント 動作確認結果（2026-06-04）

**ベースURL**: `https://coroproject.jp/admin/api/`  
**認証**: リクエストヘッダー `X-Api-Key: a473997a0ca9348cbcdf58aa2bea270f3ff27edc6eadbfca80bad2e1ec2ffd20`  
**PATCHメソッド**: Apache制限のため `POST + X-HTTP-Method-Override: PATCH` で代替  
**DELETEメソッド**: `POST + X-HTTP-Method-Override: DELETE` で代替

| エンドポイント | GET一覧 | GET詳細 | POST | PATCH | DELETE | 備考 |
|---|---|---|---|---|---|---|
| /talents | ✅ 2件 | ✅ | - | ✅ | - | VARCHAR ID（talent, talent-2） |
| /clients | ✅ 0件 | ✅ | ✅ 201 | ✅ | ✅ | VARCHAR ID自動生成 |
| /deals | ✅ 0件 | ✅ | ✅ 201 | ✅ | ✅ | VARCHAR ID自動生成 |
| /invoices | ✅ 10件 | ✅ items付き | - | ✅ | - | BIGINT ID |
| /revenues | ✅ 16件 | - | ✅ 201 (UPSERT) | ✅ | - | BIGINT ID |
| /journal | ✅ 14件+summary | - | ✅ 201 | - | - | BIGINT ID |
| /payments | ✅ (overdue/upcoming/paid) | - | - | - | - | 請求書ステータス集計 |
| /update | - | - | ✅ | - | - | 汎用更新（talents/clients/biz_deals） |

**認証**: 無効キー → 401 ✅ 正常拒否確認済み

## 修正履歴（2026-06-04）

1. **DBマイグレーション実行** — accounting_invoicesに`due_date`・`payment_bank_info`カラム追加
2. **api_path_id()修正** — VARCHAR IDのサポート（talent-2等が詳細取得・PATCHで使えなかった）
3. **PHP 7互換性修正** — union type hint除去（`int|string|null` → 型なし）
4. **migrate.phpを削除** — 一時エンドポイントのため削除済み

## セッションログ

### 2026-06-04
- 管理サイト全機能APIの動作確認を実施
- 7エンドポイントすべてGET正常動作を確認
- 3件のバグを発見・修正・デプロイ済み（上記修正履歴参照）
- テストデータ: journal id=20（削除推奨）、revenues id=19（確認推奨）
