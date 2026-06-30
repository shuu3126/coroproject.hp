# CORO PROJECT HP — 進捗管理

## 次にやること

- [ ] journalのテスト用レコード（id=20）を管理画面UIから削除
- [ ] revenuesのAPIテスト用レコード（id=19: talent-2, 2026/1, 全額0）を確認・必要なら削除
- [ ] 今後の請求書・領収書発行時はgenerate_pdf→filesでGドライブへ保存（会計AI担当）

## 🔴 次回最優先：タレント分配の支出記帳（未完了）

受取収入の70%をタレントに支払っているが**全額未記帳**。
実際の手元残高と仕訳残高がズレているため要修正。

記帳予定の支出一覧：

| 請求書 | タレント | 受取額 | 支払い予定額(70%) | 支払日 | 備考 |
|---|---|---|---|---|---|
| INV-000002 | 要確認（talent空） | ¥6,048.88 | ¥4,234 | 2025-11-16頃 | talent_id未設定 |
| INV-000003 | 要確認（talent空） | ¥10,000 | ¥7,000 | 2025-11-21頃 | talent_id未設定 |
| INV-000004 | 要確認 | ¥6,150.13 | ¥4,305 | 2025-12-16頃 | |
| INV-000005 | 要確認 | ¥10,388.46 | ¥7,272 | 2026-01-30頃 | |
| INV-000006 | 要確認 | ¥5,455.17 | ¥3,819 | 2026-03-23頃 | |
| INV-000007 | 青海しび | ¥1,400 | **対象外** | - | 事務所→タレントへの請求（ConoHa代立替回収）|
| INV-000008 | 要確認 | ¥5,153.53 | ¥3,607 | 2026-04-18頃 | |
| INV-000009 | 要確認 | ¥8,691.99 | ¥6,084 | 要確認（paid_atなし）| receipt_issuedだが日付不明 |
| INV-000010 | 青海しび | ¥14,148.72 | ¥9,904 | 2026-05-13頃 | |
| INV-000011 | 青海しび | ¥27,000 | ¥18,900 | 2026-05-15頃 | |

**合計支払予定: ¥65,125**（INV-000007除く）

次回確認事項：
- INV-000002, 003, 004, 005, 006, 008 のタレントは来凛みゅぜ？青海しび？
- INV-000009 の実際の支払い日はいつ？
- ¥65,125以外にも出て行ったお金（ツール代、交通費等）があれば

## 書類管理フォルダ（Gドライブ）

```
G:\マイドライブ\2.副業\Coroproject.jp\書類管理\
├── 請求書\（10件）
│   ├── 2025-10_INV-000002_請求書.pdf ← 復旧済み
│   ├── 2025-11_INV-000003_請求書.pdf ← 復旧済み
│   ├── 2025-11_INV-000004_請求書.pdf ← 復旧済み
│   ├── 2025-12_INV-000005_請求書.pdf ← 復旧済み
│   ├── 2026-01_INV-000006_請求書.pdf ← 復旧済み
│   ├── 2026-01_INV-000007_請求書.pdf ← 復旧済み
│   ├── 2026-02_INV-000008_請求書.pdf ← 復旧済み
│   ├── 2026-03_INV-000009_請求書.pdf
│   ├── 2026-05_INV-000010_請求書.pdf
│   └── 2026-05_INV-000011_請求書.pdf
└── 領収書\（3件）
    ├── 2026-03_INV-000009_領収書.pdf
    ├── 2026-05_INV-000010_領収書.pdf
    └── 2026-05_INV-000011_領収書.pdf
```

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

### 2026-06-30
- index.php を全面リデザイン: SYS.VER.1.0.4ウィジェット・マーキー除去、クリーンな企業ポータルに
- ヒーロー: ラベル「VTuber事務所 × B2B Platform」、h1「VTuber業界を、インフラから変える。」
- 2ピラー構成: CREDiT（外部URL直リンク・OPEN PLATFORM）+ Production（ENTER）
- セクション構成: HERO → TWO PILLARS → WHY CORO PROJECT → MISSION → FOR WHOM → NEWS → CTA
- includes/site-data.php: $divisions を2ピラーに整理（url/summary フィールド追加）
- includes/site-data.php: DB結果が空の場合は静的newsItemsを維持する修正（!empty）
- XAMPP確認済み（全セクション・newsカード3件表示OK）

### 2026-06-22
- production/index.php + css/top.css をv3デザイン（SPECIALITEリファレンス）に全面リデザイン
- 白背景 + 40pxグリッドライン、グラデーション眉毛ラベル、3D遠近スマホ（CSS perspective）実装
- .deco-layer → .bg-deco に置き換え（スキャンライン・縦横ライン・ドット）
- .global-bg でフィックス背景ブロブ（cyan/purple/pink、mix-blend-mode:multiply）追加
- フッター: #111827 + border-top 4px solid #00e5ff（シアン） 
- Audition CTA: #0a0a0a 超ダーク背景 + purpleアウトラインボタン
- XAMPPで確認済み（ss_hero / ss_about / ss_talents / ss_cta スクショ確認OK）
- デプロイ保留（ユーザー指示待ち）

### 2026-06-04
- 管理サイト全機能APIの動作確認を実施
- 7エンドポイントすべてGET正常動作を確認
- 3件のバグを発見・修正・デプロイ済み（上記修正履歴参照）
- テストデータ: journal id=20（削除推奨）、revenues id=19（確認推奨）
