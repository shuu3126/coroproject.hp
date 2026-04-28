<?php
require_once __DIR__ . '/includes/layout.php';
render_head('ABOUT', 'CORO PROJECTが何をしている事業体か、VTuber業界に対してどのような価値を届けたいかを紹介します。');
render_header('about');
?>
<main class="subpage-main">
  <section class="sub-hero about-hero">
    <div class="container sub-hero-grid reveal is-visible">
      <div class="sub-copy">
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">CORE BRIEFING</span>
        </div>
        <h1 class="sub-title">
          VTuberという新時代の才能を、<br>
          一過性のブームではなく、<br>
          <span>多面的な価値へと接続していく。</span>
        </h1>
        <p class="sub-lead">CORO PROJECTは、VTuberと企業・クリエイターをつなぎ、それぞれが持つ価値を継続的な活動へ変えていくための総合支援ブランドです。</p>
      </div>
      <div class="sub-visual reveal is-visible">
        <div class="visual-frame cyber-clip-lg">
          <div class="visual-gradient"></div>
          <div class="visual-placeholder"></div>
          <div class="frame-corner frame-corner-tl"></div>
          <div class="frame-corner frame-corner-br"></div>
          <div class="visual-caption">
            <span>AUTHORIZATION: CORE</span>
            <strong>Coro</strong>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container section-grid reveal">
      <div>
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">BRAND OVERVIEW</span>
        </div>
        <h2 class="content-title">何をしている事業体か</h2>
      </div>
      <div class="content-copy stack-md">
        <p>CORO PROJECTは、VTuber事務所機能を基盤に、企業案件仲介とクリエイティブ支援を一体で展開する事業体です。活動者が一人で抱えがちな営業・制作・進行の負担を整理し、企業やクリエイターとも適切につながる環境をつくることを目的としています。</p>
        <p>総合ポータルではブランド全体の方向性を示し、各事業部ではそれぞれ独立した役割と窓口を持つ構造を採用しています。</p>
      </div>
    </div>
  </section>

  <section class="content-section content-section-alt">
    <div class="container section-grid reveal">
      <div>
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">MISSION</span>
        </div>
        <h2 class="content-title">VTuber業界に対して何をしたいのか</h2>
      </div>
      <div class="content-copy stack-md">
        <p>VTuber業界には、活動者、企業、クリエイターのそれぞれに可能性がある一方で、それぞれが分断されやすい課題があります。CORO PROJECTは、その間にある調整・提案・進行・支援の役割を担い、単発の消費ではなく継続的な価値が循環する環境を目指します。</p>
        <p>所属タレントの活動支援だけでなく、案件の相談、制作の相談、関係者が安心して関われる窓口づくりを通じて、業界全体の成熟に寄与していくことがミッションです。</p>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container section-head reveal">
      <div class="section-title-wrap">
        <span class="pink-bar"></span>
        <div>
          <span class="section-sub">THREE CORE DIVISIONS</span>
          <h2 class="section-title">3事業部の役割</h2>
        </div>
      </div>
    </div>
    <div class="container info-grid reveal">
      <?php foreach ($divisions as $division): ?>
        <article class="info-card cyber-clip-lg <?= h($division['class']) ?>">
          <div class="corner corner-tl"></div>
          <span class="info-num"><?= h($division['num']) ?></span>
          <span class="info-eyebrow"><?= h($division['title']) ?></span>
          <h3><?= h($division['title_jp']) ?></h3>
          <p><?= h($division['summary']) ?></p>
          <a href="<?= h($division['slug']) ?>/" class="card-link">OPEN DIVISION <span aria-hidden="true">›</span></a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php render_footer(); ?>
