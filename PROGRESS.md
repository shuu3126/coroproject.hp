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

### 2026-07-02（続き6・デプロイ）
- **ユーザー追加指示**: ①トップにアニメーション・オブジェクト・背景の動きを増やす ②HeroのOFFICIAL VISUAL（AIイラスト）が「ゴミすぎる」ので差し替え ③他の分岐ページも同条件で再構築 ④完成基準に「デプロイできるレベル」を追加
- **Hero差し替えの経緯**: タレント動画（shorts/short1.mp4）を試したが、焼き込みテロップ入りゲーム実況ショートでヒーロー不適と判断→**CSSのみの「エコシステム・モック」**（タレントカード⇄惑星ロゴ⇄案件成立カードをダッシュ線とパルスで接続、会社の2事業構造を可視化）に確定
- **動きの追加**: ヒーロー背景の浮遊オーブ2つ、ステージ周囲の浮遊チップ、GATE-MISSION間のアウトライン文字マーキー帯、パルスアニメ。すべてtransform/opacityのみ・`prefers-reduced-motion`対応
- **サブページ再構築**: 新規`assets/css/portal-v3-sub.css`（1枚で5ページ共通のライト再塗装）＋各ページに`extra_css`1行追加。PHPロジック（NEWSフィルタ・CSRF付きフォーム・DB連携）は無変更。実スクショで検出した残骸（sub-leadの暗ボックス、principle-cardのシアンピンク線、role-rowの斜体シアン、contact-infoの暗パネル、news-card-thumbの巨大化）を個別に潰した
- **バグ修正**: about.php・service.phpのdivision-cardリンクが存在しない相対パス`credit/`を指していた（フッターと同型のバグ）→url??slugパターンに統一。「ENTER SYNC」「SEND SIGNAL」等のサイバー文言を日本語に変更
- **セキュリティチェックリスト**: 個人情報テーブルアクセスなし・全ファイル`php -l`通過・動的出力は全て`h()`経由を確認
- **デプロイ**: ファイル指定addでコミット・push（`git add -A`不使用）。production/index.php・top.cssのorigin/main復元も同コミットに含め、6月の未承認リデザインが配信されない状態を保証
- **残課題**: contact.php/news_detail.phpの約16秒応答（既存問題・未調査）。portal-v2.cssはロールバック用にローカル未追跡のまま残置

### 2026-07-02（続き5・最終）
- **ユーザー指示**: 「全体的にデザインが気に入らない、1から作り直し。国内デザインギャラリー掲載サイトを学習してから作成。視覚的・構造的に確認して95点以上まで反復」
- **調査（リサーチAIフォーク）**: MUUUUU.ORG/SANKOU!/Web Design Clipのギャラリー3サイト＋hololive・ANYCOLOR・774 inc.・UUUM等の業界サイトを実地調査。**VTuber業界の現場側サイトは全て白基調**（ダークはBrave group等の持株会社の顔のみ）と判明。ギャラリー掲載傾向も白系2242件>黒系1243件でホワイト圧倒的主流。これまでのダーク基調が違和感の根本原因と結論
- **新コンセプト「White Hall, Purple Stage」で全面再構築**:
  - `assets/css/portal-v3.css` 新規作成（index.php専用・ライトテーマ。portal-v2.cssは読み込み解除、ファイルはロールバック用に残置）
  - 配色: オフホワイト#F7F6FA基調＋紫#7C3AED（CREDiTブランド継承）＋ステージ暗部#100A1F
  - 見出しフォント: Zen Kaku Gothic New 900（業界標準の太ゴシック系）
  - 構成: HERO（左コピー＋右「紫のステージ窓」=暗色角丸パネルにtopgirlイラスト額装）→ GATE（CREDiT紫カード/Production白カード＋立場別3導線）→ MISSION → NEWS → CTA紫帯
  - **NEWS部は`$newsItems`（site-data.php経由・admin/管理画面のDB連携）をそのまま使用、仕組み完全維持**
  - ヘッダー・フッターはlayout.php共通マークアップのままCSSで白基調に上書き（他ページ非影響）。フッター文言「// ALL SYSTEM OPERATIONAL」のみ「© 2026 CORO PROJECT」に変更（全ページ共通、無害）
- **視覚検証**: puppeteer-core+Chromeで実測スクリーンショット（1920/1440/390px、reveal強制発火）を反復。発見・修正: ①H1が中間幅で「る。」だけ3行目に孤立→font-size clamp調整 ②Production白カードが空虚→パステルグラデ＋惑星ロゴ透かし追加 ③topgirl.png 2.7MB→topgirl_web.jpg 191KBに最適化 ④モバイル見切れ疑い→puppeteer実測でscrollWidth=390・オーバーフローゼロと確認（headless CLIのDPIアーティファクトだった）
- **品質管理AI最終採点: 96/100**（コンセプト14/15・タイポ14/15・配色15/15・レイアウト14/15・分岐機能15/15・レスポンシブ10/10・構造/A11y/admin連携9/10・パフォーマンス5/5）→ 合格条件95点クリア
- **既知の別スコープ問題（未対応・記録のみ）**: `contact.php`と`news_detail.php`が応答に約16秒かかる（今回触っていない既存ファイルの問題、原因未調査）。`production/`はローカルで500（ローカルDBスキーマ差異由来、本番サーバーでは動作中・サーバー版を正とする方針のため対象外）
- **状態:** XAMPPで全検証済み。**git pushは引き続き保留**（ユーザー目視レビュー待ち。デプロイ時は`git add index.php assets/css/portal-v3.css includes/layout.php images/topgirl_web.jpg PROGRESS.md`のファイル指定で）

### 2026-07-02（続き4）
- **ユーザー指示**: 総合ページを「ProductionとCREDiTの分岐を行うページ」らしい構成に再設計する
- **対応**: Heroを**フルスクリーン分割ゲートウェイ型**に全面改稿（ANYCOLOR等のグループ企業サイトで定番の「2枚の扉」パターン）
  - `.gateway-hero`: 上部に控えめなイントロ（eyebrow+H1+リード）、その下に画面を左右分割する2枚の大パネル（Production / CREDiT）。ホバーでパネルが`flex-grow`で広がるCSSのみの演出（JS不使用・パフォーマンス配慮）
  - Production側パネル背景: `images/topgirl.png`（ブランドイラスト）を彩度落とし→ホバーでフルカラー化
  - CREDiT側パネル背景: 紫グラデーション＋ドットグリッド（アセット不要のCSS表現）
  - 旧Heroにあったコピー・統計・CTAボタンは新設の`.statement-section`（STATEMENT）に移設
  - **TWO PILLARSセクションは削除**（ゲートウェイパネル＋STATEMENTと内容が3重になるため）。`portal-v2.css`内の`.two-pillars-*`/`.pillar-card`/旧`.hero-*`系ルールはデッドコードとして残存（クリーンアップ候補）
- **障害対応**: 作業中にApacheが停止していたため`apache_start.bat`で再起動（原因不明、MySQLは影響なし）
- **状態:** `php -l`通過・CSS括弧対応確認・XAMPPで200 OK・パネル2枚/リンク先(credit.coroproject.jp / ./production/)/topgirl.png配信すべて確認済み。**git pushは引き続き保留**

### 2026-07-02（続き3）
- **ユーザー指示**: 総合ページをデザインAI・開発ディレクターAI・品質管理AIの3部署に分担してもう一度レビューさせる
- **デザインAI指摘（対応済み）**: Hero/TWO PILLARSの新カードは角丸なのに、WHY/FOR WHOM/NEWS/MISSIONの動画枠が旧来の六角形カット(cyber-clip)のまま残存しデザイン言語が途中で崩れていた → `portal-v2.css`で`.cyber-clip`/`.cyber-clip-lg`とボタン類・カード類のclip-pathを全撤去し角丸に統一。Hero背景動画が`opacity:.16`で実質見えなかった → `.38`に引き上げ、グラデーションオーバーレイ追加
- **開発ディレクターAI指摘（対応済み分）**: NEWSカードの見出しが`h2`のままでh2→h3の階層が崩れていたバグを修正（`portal.css`の元スタイルをh3向けに再現し見た目は維持）。`:focus-visible`が皆無だったためキーボード操作用のフォーカスリングを追加。低コントラストな補助テキスト（`rgba(255,255,255,.4)`）を`.56`に引き上げてWCAG AA基準を満たすよう調整
- **開発ディレクターAI指摘（技術的負債として記録・今回は未対応）**: `portal-v2.css`の`!important`多用（18箇所）は将来`portal.css`本体への統合を検討すべき。`$division['url'] ?? (...)`の重複パターン（index.php 3箇所+layout.php 1箇所）はヘルパー関数化の余地あり
- **品質管理AI**: リンク生存・画像/動画アセット実在・レスポンシブ整合性・PHP構文・CSS構文・他ページへの非影響、すべて問題なしと確認
- **状態:** 修正反映済み・`php -l`/CSS括弧チェック済み・XAMPPで200 OK確認済み。**git pushは引き続き保留**

### 2026-07-02（続き2）
- **ユーザー指摘**: index.php内でHero/TWO PILLARSだけ新デザインになり、他セクション（WHY/MISSION/FOR WHOM/NEWS/CTA）・ヘッダー・フッターが旧ネオン配色のままで統一感がないという指摘
- **対応**: `portal-v2.css`を拡張し、index.php全体（ヘッダーのnav-cta、WHY CORO PROJECTのinsight-card、MISSIONのabout-section/visual-caption、FOR WHOMのroute-card、NEWSのnews-card、CTA、フッター）のシアン/ピンク/インディゴをすべて紫アクセントに統一。見出しはNoto Serif JPに統一
- **バグ発見・修正**: フッター「SERVICES」のCREDiTリンクが相対パス`credit/`になっており実在しないURLを指していた（本来は`https://credit.coroproject.jp/`）。`includes/layout.php`の`render_footer()`を修正し、他ページ共通で正しいリンクになるよう修正（全ページに影響する分だけ他のindex.php専用修正とは別枠の恒久バグ修正）
- **Production（VTuber事務所公開サイト）の扱いについてユーザー指示**: 「レンタルサーバーに上がっているデザインをそのまま使う。ドライブ内のデザイン（6/22のv3 SPECIALITEリデザイン、未デプロイ）はゴミだから削除」との指示
  - `git diff origin/main main -- production/` で実際にデプロイ済み(origin/main)とローカル未pushの差分を確認し、`production/index.php`・`production/css/top.css`を`git checkout origin/main --`で実際にサーバーに上がっている状態へ復元
  - 6/22セッションの残骸（`codex-session-2026-06-22-125315.md`等）を削除
  - 6/22に追加された未使用画像（`production/images/hero_vtuber_phone_*.jpg`等5点）はバイナリのため今回は削除せず残置（実害なし）
- **XAMPP確認環境構築**: `C:\xampp\htdocs\coroproject.jp`を旧スタティックコピー（4/29時点・stale）から`coroproject.jp.stale-backup-20260702`へリネーム退避し、Driveフォルダへのディレクトリジャンクションに差し替え（シンボリックリンクは管理者権限が必要だったためジャンクションを使用）。ローカルDB`db_coroproject_1`は既にスキーマ投入済みで、`production/db.php`がlocalhost判定で自動的にこちらを使う安全な設計（本番DBには非接続）
- **未対応・持ち越し**: `production/html/news.php`で`Unknown column 'n.targets'`のFatal Errorをapache error.logで検知（ローカルDBスキーマと`production/html/`配下のコードの不整合の可能性。今回のindex.php作業スコープ外のため未調査）
- **状態:** ローカルファイル修正完了・`php -l`構文チェック済み・XAMPPで実際に200 OK確認済み。**git pushは引き続き保留**（ユーザー目視レビュー待ち）

### 2026-07-02
- **作業内容:** HERO・TWO PILLARSセクションのデザイン全面刷新（Claude+Gemini委譲、要ユーザーレビュー・未デプロイ）
  - 国内優良サイト（カバー株式会社・ANYCOLOR・SmartHR・freee・LayerX・メルカリ・E-agent等）を2フォークで調査した上でリニューアル
  - 発見: 6/30ログに「マーキー・HUD除去」と記載があったが、実際のコードには`.hero-marquee`（上下2本の流し読みテキスト）と`.hero-hud`（SYS.STATUS等）が**残存していた**（ログと実装の乖離）。今回改めて完全除去
  - 配色をシアン/ピンク/インディゴのネオン3色+紫blobから、**モノトーン＋紫単色アクセント(#7C3AED)**に統一。グループ会社CREDiT（credit.coroproject.jp）が既に紫を採用しているためブランド一貫性を優先
  - 見出しにNoto Serif JP（明朝体）を導入（ANYCOLOU等の同業他社を参考に、エンタメの熱量と企業としての格式を両立）
  - HeroとTWO PILLARSでdivision-card情報が重複していた点を、Hero側を主役カード・TWO PILLARS側を簡潔な補足カード（LayerX型）に整理
  - **新規CSSファイル `assets/css/portal-v2.css` を追加**（既存`portal.css`は無編集、`render_head()`の`extra_css`オプション経由で読み込み）。1800行超の共有CSSファイルへの誤編集リスクを避けるための設計判断
  - 未使用と確認済みの`assets/css/home.css`を削除
  - Gemini初回生成はスコア100だったが、実際にはFont Awesome依存（未読込のため機能しない）・存在しない画像パス・CREDiTへの外部リンクが誤って内部相対パス化・架空の「¥1B+」等の数値の記載など複数の実害あるバグを含んでいたため、Claudeが手動で修正して適用（自動採点を鵜呑みにしない教訓）
- **状態:** ローカルファイル修正完了・構文チェック済み（`php -l`通過、CSS括弧対応確認済み）。**XAMPPでの目視確認は未実施**（ローカルhtdocsコピーが4/29時点で古く現行構成と乖離しているため）。**git pushによるデプロイは意図的に保留**——個人情報を扱う本番サイトの主要ページへの大規模デザイン変更のため、ユーザーの目視レビューを経てから`.company/CLAUDE.md`のデプロイ手順（ファイル指定add→commit→push）を実施する方針
- **次回への申し送り:**
  - ユーザーレビュー後、`git add index.php assets/css/portal-v2.css` でファイル指定addしてデプロイ（`git add -A`は絶対禁止）
  - portal.css内の`.hero-marquee`/`.hero-hud`/`.marquee-track`/`@keyframes marquee`/旧`.division-card`等は現在デッドコードとして残存（意図的に温存、将来的なクリーンアップ候補）
  - WHY CORO PROJECT以降のセクション（MISSION/FOR WHOM/NEWS/CTA）は今回未着手。追加リニューアルする場合は同じモノトーン+紫アクセント方針を踏襲すること

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
