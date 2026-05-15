<?php
require_once __DIR__ . '/includes/layout.php';
render_head('SERVICE', 'CORO PROJECTが展開する3つの事業部と、活動支援・案件接続・制作支援の具体的な支援内容を紹介します。', [
    'canonical' => 'https://coroproject.jp/service.php',
    'robots'    => 'index, follow',
    'og_type'   => 'website',
    'og_image'  => 'https://coroproject.jp/images/ogp.png',
]);
render_header('service');
?>
<main class="subpage-main">
  <section class="sub-hero compact-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">SERVICE MAP</span>
        </div>
        <h1 class="sub-title">活動支援・案件接続・制作支援を、<br><span>一つのブランドで設計する。</span></h1>
        <p class="sub-lead">CORO PROJECTでは、VTuber活動を取り巻く主要な課題に対して、3つの事業部で役割を分担しながら支援体制を構築しています。相談の入口は分けつつ、案件・制作・活動設計が連動するように全体を見て進行します。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">Production</span>
        <span class="mini-tag">Business Matching</span>
        <span class="mini-tag">Creative Support</span>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container section-head reveal">
      <div class="section-title-wrap">
        <span class="pink-bar"></span>
        <div>
          <span class="section-sub">SERVICE ENTRY</span>
          <h2 class="section-title">相談したい内容から探す</h2>
        </div>
      </div>
    </div>
    <div class="container route-grid reveal">
      <article class="route-card cyber-clip-lg">
        <span>PR / CASTING</span>
        <h3>VTuberを起用したい</h3>
        <p>商品紹介、店舗紹介、イベント出演、SNSキャンペーンなど、企業目的に合う施策へ整理します。</p>
        <a href="business/" class="card-link">BUSINESS MATCHING <span aria-hidden="true">›</span></a>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>DESIGN / MOVIE</span>
        <h3>制作物を依頼したい</h3>
        <p>立ち絵、KV、ロゴ、動画、配信画面など、活動や案件で必要な制作物の相談を受け付けます。</p>
        <a href="creative/" class="card-link">CREATIVE SUPPORT <span aria-hidden="true">›</span></a>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>MANAGEMENT</span>
        <h3>所属・活動について相談したい</h3>
        <p>活動方針、配信企画、案件対応、制作導線など、継続活動に必要な支援を確認します。</p>
        <a href="production/" class="card-link">PRODUCTION <span aria-hidden="true">›</span></a>
      </article>
      <article class="route-card cyber-clip-lg">
        <span>PARTNERSHIP</span>
        <h3>提携・取材を相談したい</h3>
        <p>メディア掲載、共同企画、業務提携など、事業横断で確認が必要な内容は総合窓口へつなぎます。</p>
        <a href="contact.php" class="card-link">CONTACT <span aria-hidden="true">›</span></a>
      </article>
    </div>
  </section>

  <section class="content-section">
    <div class="container cards-grid reveal is-visible">
      <?php foreach ($divisions as $division): ?>
        <a class="division-card cyber-clip-lg <?= h($division['class']) ?>" href="<?= h($division['slug']) ?>/">
          <div class="corner corner-tl"></div>
          <div class="card-top">
            <div class="card-icon"></div>
            <span class="card-num"><?= h($division['num']) ?></span>
            <span class="card-num-name"><?= h($division['title_jp']) ?></span>
          </div>
          <span class="card-en"><?= h($division['title']) ?></span>
          <h2 class="card-jp"><?= h($division['title_jp']) ?></h2>
          <p class="card-desc"><?= h($division['summary']) ?></p>
          <span class="card-link">ENTER SYNC <span aria-hidden="true">›</span></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="content-section content-section-alt">
    <div class="container section-head reveal">
      <div class="section-title-wrap">
        <span class="pink-bar"></span>
        <div>
          <span class="section-sub">SUPPORT RANGE</span>
          <h2 class="section-title">対応できる支援範囲</h2>
        </div>
      </div>
      <p class="section-note">小さな相談から、案件・制作・活動支援が絡む複合的な相談まで対応します。内容が決まっていない段階でも、必要な情報を整理するところから始められます。</p>
    </div>
    <div class="container matrix-grid reveal">
      <article class="matrix-card cyber-clip">
        <h3>企画整理</h3>
        <p>目的、ターゲット、見せ方、必要素材、実施時期を確認し、何から決めるべきかを整理します。</p>
      </article>
      <article class="matrix-card cyber-clip">
        <h3>候補提案</h3>
        <p>案件や制作内容に合わせて、タレント・クリエイター・導線を提案します。</p>
      </article>
      <article class="matrix-card cyber-clip">
        <h3>条件調整</h3>
        <p>実施範囲、納期、確認回数、使用範囲など、後から揉めやすい項目を先に整えます。</p>
      </article>
      <article class="matrix-card cyber-clip">
        <h3>進行管理</h3>
        <p>告知、素材提出、制作確認、公開・出演まで、関係者の動きが止まらないように伴走します。</p>
      </article>
    </div>
  </section>

  <section class="content-section">
    <div class="container process-grid reveal">
      <div>
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">HOW WE SUPPORT</span>
        </div>
        <h2 class="content-title">相談から実行までの考え方</h2>
      </div>
      <div class="timeline-list">
        <div class="timeline-item"><span>01</span><div><h3>ヒアリング</h3><p>相談内容、目的、予算感、希望時期、必要な進行範囲を整理します。</p></div></div>
        <div class="timeline-item"><span>02</span><div><h3>課題の切り分け</h3><p>活動支援、案件接続、制作相談、提携相談のどこに重心があるかを確認します。</p></div></div>
        <div class="timeline-item"><span>03</span><div><h3>最適な導線の提案</h3><p>必要な事業部や担当領域を組み合わせ、実行しやすい進め方に落とし込みます。</p></div></div>
        <div class="timeline-item"><span>04</span><div><h3>実施・進行</h3><p>提案から制作、出演、告知、確認、公開までを必要に応じて伴走します。</p></div></div>
        <div class="timeline-item"><span>05</span><div><h3>振り返りと次の接続</h3><p>単発で終わらせず、次回企画や活動改善につながるポイントを整理します。</p></div></div>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
