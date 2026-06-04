# CORO PROJECT サイト — 作業ルール

## このファイルの目的
このプロジェクトには**個人情報・財務情報がDBに含まれる**。
作業のたびに必ずこのファイルを読み込み、ルールを守ること。

---

## フォルダ構成と公開範囲

```
HP/
├── production/     ← 公開サイト（インターネットから直接アクセス可能）
│   ├── index.php, about.php, news.php, service.php, contact.php 等
│   ├── images/     ← 公開画像
│   └── uploads/    ← タレント写真・請求書PDF（直リンク注意）
├── admin/          ← 管理画面（Basic認証＋セッション認証で保護）
│   ├── api/        ← REST API（APIキー認証のみ）
│   └── *.php       ← 管理画面各ページ
└── about.php 等    ← HPルートファイル（production/と連携）
```

---

## 個人情報が含まれるDBテーブル（最重要）

以下のテーブルのデータは**絶対に公開ページ（production/）に出力してはならない**。

| テーブル | 含まれる個人情報 |
|---|---|
| `accounting_talent_settings` | 本名・住所・口座情報・電話番号・メール |
| `talent_portal_accounts` | ログインID・パスワードハッシュ |
| `talent_profile_change_requests` | タレントの個人情報変更申請 |
| `accounting_invoices` | 請求先・金額・支払い情報 |
| `accounting_revenues` | タレント収益（月別） |
| `clients` | クライアントの連絡先・メール・電話 |
| `admin_users` | 管理者パスワードハッシュ |
| `cre_creators` | クリエイターの本名・住所・口座・インボイス番号 |
| `talent_portal_activity_logs` | IPアドレス・ユーザーエージェント |

**公開ページで参照してよいテーブル:**
- `talents`（is_published=1 のもののみ、bio/name/avatar/platforms_json）
- `news`（is_published=1 のもの）
- `inquiries`（フォーム送信受付のみ）

---

## 作業前チェックリスト

### コード変更時（毎回必須）

- [ ] **production/内のファイルを変更した場合**: 個人情報テーブルへのアクセスが一切ないか確認
- [ ] **admin/内のファイルを変更した場合**: `_auth.php` の認証チェックが最初に呼ばれているか確認
- [ ] **admin/api/内のファイルを変更した場合**: `_bootstrap.php` のAPIキー認証が最初に呼ばれているか確認
- [ ] **新規テーブルアクセスを追加した場合**: そのテーブルが上記「個人情報テーブル」に該当しないか確認
- [ ] **ファイルアップロード機能を追加した場合**: 保存先パスが `production/uploads/` 配下に限定されているか確認

### セキュリティ確認項目

- [ ] SQL文はすべてPDOプリペアドステートメントを使用しているか（SQL injection防止）
- [ ] 出力値はすべて `htmlspecialchars()` でエスケープされているか（XSS防止）
- [ ] ファイルパスにユーザー入力を直接使っていないか（path traversal防止）
- [ ] エラーメッセージに内部パス・DBスキーマ・スタックトレースが含まれていないか
- [ ] `production/db.php` や `.htpasswd` が公開ページからインクルードされていないか

---

## デプロイルール（最重要）

**作業完了後は自動でデプロイを実施する。ただし以下の順序を厳守：**

### Step 1: セキュリティ確認
上記チェックリストを実行し、すべて問題ないことを確認する。

### Step 2: 変更内容の確認
```powershell
git diff --stat
git diff
```
変更ファイルと差分を確認し、意図しない変更が含まれていないか確認する。

### Step 3: コミット＆プッシュ
```powershell
git add <変更したファイル>
git commit -m "変更内容の説明"
git push origin main
```

### Step 4: GitHub Actions完了確認
プッシュ後、GitHub ActionsのFTPデプロイが完了するまで待機（通常1〜2分）。
完了後、本番サイト（https://coroproject.jp）で動作確認を行う。

**注意: `git add .` や `git add -A` は使わない。**
個人情報を含む設定ファイル（`.env`系）や不要なファイルを誤ってコミットしないよう、
必ずファイル指定でaddすること。

---

## よくあるリスクパターン（過去の判断事項より）

### NG例1: 管理データを公開ページに流す
```php
// ❌ NG: 収益データを公開ページに出力
$stmt = $pdo->query("SELECT * FROM accounting_revenues");
```

### NG例2: APIレスポンスに機密フィールドを含める
```php
// ❌ NG: bank_info や real_name をAPIから返す
$stmt = $pdo->query("SELECT * FROM accounting_talent_settings");
// → 必要なフィールドだけSELECTすること
```

### NG例3: 認証バイパス
```php
// ❌ NG: _auth.php の前にデータ取得処理を書く
$data = getData(); // ← 認証前に実行される
require '_auth.php';
```

---

## production/uploads/ の注意事項

- タレント写真: `production/images/talents/` → 公開OK（意図的に公開するもの）
- 請求書PDF: `production/uploads/accounting/` → **URLを知られると誰でもアクセス可能**
  - 請求書PDFは `admin/download.php` 経由（認証付き）でのみ提供すること
  - 直リンクURLを外部に共有しないこと

---

## PROGRESS.md

作業開始時・終了時に必ず `PROGRESS.md`（このフォルダ直下）を読み込み・更新すること。
（`.company/CLAUDE.md` の「プロジェクト作業時の必須ルール」も参照）
