<?php
require_once __DIR__ . '/includes/layout.php';
render_head('SERVICE', 'CORO PROJECTが展開する3つの事業部と、その支援内容を紹介します。');
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
        <p class="sub-lead">CORO PROJECTでは、VTuber活動を取り巻く主要な課題に対して、3つの事業部で役割を分担しながら支援体制を構築しています。</p>
      </div>
      <div class="sub-badges reveal is-visible">
        <span class="mini-tag">Production</span>
        <span class="mini-tag">Business Matching</span>
        <span class="mini-tag">Creative Support</span>
      </div>
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
    <div class="container process-grid reveal">
      <div>
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">HOW WE SUPPORT</span>
        </div>
        <h2 class="content-title">相談から実行までの考え方</h2>
      </div>
      <div class="timeline-list">
        <div class="timeline-item"><span>01</span><div><h3>ヒアリング</h3><p>相談内容、目的、予算感、必要な進行範囲を整理します。</p></div></div>
        <div class="timeline-item"><span>02</span><div><h3>最適な導線の提案</h3><p>事務所支援・案件仲介・制作相談のうち、必要な窓口へ振り分けます。</p></div></div>
        <div class="timeline-item"><span>03</span><div><h3>実施・進行</h3><p>提案から制作、出演、進行管理までを必要に応じて伴走します。</p></div></div>
      </div>
    </div>
  </section>
</main>
<?php render_footer(); ?>
