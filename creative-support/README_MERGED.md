# CORO PROJECT Creative Support merged LP

Google AI Studio版LPをベースに、`creative-support.zip` のHeroデザイン要素を統合したReact/Vite版です。

## 変更内容

- Heroを手書き風見出し・斜めリボン・英字マルキー・ネオン背景のデザインへ差し替え
- Google AI Studio版の「悩み訴求」「サービス」「ご依頼の流れ」「選ばれる理由」「クリエイター募集」「FAQ」「問い合わせ」を統合
- 全体を個人VTuberが相談しやすい文言へ調整
- CSSはTailwind依存を減らし、`src/index.css`中心に整理

## 画像について

Heroは以下の画像パスを参照しています。元ZIPには画像ファイルが入っていなかったため、実サイト側の画像を配置してください。

- `/public/images/toukalogo.png`
- `/public/images/topgirl.png`

既存サイトに `images/toukalogo.png` / `images/topgirl.png` がある場合は、公開時の配置に合わせて `src/App.tsx` のパスを調整してください。

## 注意

問い合わせフォームは見た目のみです。公開時は既存の `contact.php` か送信APIに接続してください。
