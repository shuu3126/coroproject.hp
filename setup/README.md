# CORO PROJECT — セットアップガイド

## ディレクトリ構成

```
setup/
└── sql/
    ├── setup.sql    # 完全初期化（DB作成 + ユーザー作成 + 全テーブル）
    └── install.sql  # 共有サーバー用（既存DBにテーブルだけ追加）
```

---

## 環境別セットアップ手順

### ローカル開発 (XAMPP)

1. XAMPP の Apache / MySQL を起動
2. phpMyAdmin を開く（http://localhost/phpmyadmin）
3. `setup/sql/setup.sql` をインポート
4. `production/db.php` の接続先はデフォルトで `localhost / root / (パスワードなし) / db_coroproject_1` になっているため変更不要
5. http://localhost/coroproject.jp/admin/ にアクセスしてログイン（admin / admin）

### 本番サーバー（VPS・専用サーバー）

1. MySQLに root でログインして `setup/sql/setup.sql` を実行
   ```bash
   mysql -u root -p < setup/sql/setup.sql
   ```
2. `production/db.php` が読む環境変数をサーバーの `.env` または Apache/Nginx の環境変数として設定：
   ```
   CORO_DB_HOST=localhost
   CORO_DB_NAME=db_coroproject_1
   CORO_DB_USER=db_coroproject
   CORO_DB_PASS=<パスワード>
   ```

### 共有サーバー（さくら・ロリポップ等）

1. ホスティングのコントロールパネルで DB とユーザーを作成
2. phpMyAdmin などで `setup/sql/install.sql` をインポート（テーブルのみ追加）
3. `production/db.php` の環境変数 or フォールバック値を本番情報に書き換え

---

## 初期管理者アカウント

| ユーザー名 | パスワード | 権限 |
|---|---|---|
| `admin` | `admin` | 管理者（全権限） |

**本番環境では必ず変更してください。**

---

## ファイル変更履歴

| 日付 | 内容 |
|---|---|
| 初回 | `database/setup.sql` と `admin/install.sql` を `setup/sql/` に統合 |
