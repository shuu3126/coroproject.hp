# CORO PROJECT ファイル構成

## 公開サイト

- `/index.php`, `/about.php`, `/service.php`, `/contact.php`, `/news.php`
  - coroproject.jp 直下で公開する会社サイトの入口です。
- `/includes`
  - 公開サイト共通のレイアウト・公開設定・表示データ。
- `/assets`
  - 公開サイト共通の CSS / JavaScript。
- `/images`
  - 公開サイト共通の画像・動画。

## 事業部サイト

- `/production`
  - Production サイト、タレントページ、オーディションページ、Production用画像。
- `/business`
  - Business Matching サイト。
- `/creative`
  - Creative 系フロントエンド。

## 管理画面

- `/admin`
  - 管理画面の入口、ログイン、共通処理、旧URL互換ファイル。
- `/admin/production`
  - タレント管理、HP掲載情報申請、タレントポータルアカウント、ポータルお知らせ。
- `/admin/content`
  - 公開サイトのお知らせ管理。
- `/admin/crm`
  - クライアント・取引先管理。
- `/admin/business`
  - Business 案件管理、外部VTuberリスト。
- `/admin/creative`
  - Creative 案件管理、クリエイター管理。
- `/admin/accounting`
  - 請求、収益、記帳など会計機能。
- `/admin/mail`
  - 独自メール管理画面。
- `/admin/inquiries`
  - サイト問い合わせの旧メッセージ管理。
- `/admin/system`
  - 設定、操作ログ、セッション更新などシステム系。
- `/admin/assets`
  - 管理画面専用 CSS / JavaScript。
- `/admin/resources`
  - PDF用フォント・印影などの管理画面リソース。

## タレントポータル

- `/portal`
  - タレント向けログイン、収益報告、提出履歴、請求書・領収書、設定画面。
- `/portal/assets`
  - タレントポータル専用 CSS。
- `/portal/uploads`
  - タレントが提出したエビデンスファイル。

## DB / セットアップ

- `/database`
  - 初期セットアップ用SQL。
- `/setup`
  - セットアップメモとSQL。
- `/admin/install.sql`, `/admin/portal_migrate.sql`
  - 管理画面・タレントポータル用SQL。既存運用ではこのパスを使っているため残しています。

## 互換URL

以下は旧URLを壊さないために `/admin` 直下に薄い互換ファイルを残しています。

- `/admin/talents.php` → `/admin/production/talents.php`
- `/admin/talent_edit.php` → `/admin/production/talent_edit.php`
- `/admin/talent_portal.php` → `/admin/production/talent_portal.php`
- `/admin/notices.php` → `/admin/production/notices.php`
- `/admin/news.php` → `/admin/content/news.php`
- `/admin/news_edit.php` → `/admin/content/news_edit.php`
- `/admin/clients.php` → `/admin/crm/clients.php`
- `/admin/client_edit.php` → `/admin/crm/client_edit.php`
- `/admin/messages.php` → `/admin/inquiries/messages.php`
- `/admin/message_detail.php` → `/admin/inquiries/message_detail.php`
- `/admin/logs.php` → `/admin/system/logs.php`
- `/admin/settings.php` → `/admin/system/settings.php`
