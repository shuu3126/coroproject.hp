<?php
require_once __DIR__ . '/includes/layout.php';
render_head('ABOUT', 'CORO PROJECTが何をしている事業体か、VTuber業界に対してどのような価値を届けたいか、運営思想と支援体制を紹介します。', [
    'canonical' => 'https://coroproject.jp/about.php',
    'robots'    => 'index, follow',
    'og_type'   => 'website',
    'og_image'  => 'https://coroproject.jp/images/ogp.png',
    'extra_css' => ['assets/css/portal-v3.css', 'assets/css/portal-v3-sub.css'],
]);
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
        <p class="sub-lead">CORO PROJECTは、VTuberと企業・クリエイターをつなぎ、それぞれが持つ価値を継続的な活動へ変えていくための総合支援ブランドです。活動者の個性、企業の目的、制作の品質を一つの流れで設計します。</p>
      </div>
      <div class="sub-visual reveal is-visible">
        <div class="visual-frame cyber-clip-lg">
          <div class="visual-gradient"></div>
          <video class="visual-video" src="images/short/short1.mp4" autoplay muted loop playsinline preload="metadata" aria-label="青海しび ゲームプレイ動画"></video>
          <div class="frame-corner frame-corner-tl"></div>
          <div class="frame-corner frame-corner-br"></div>
          <div class="visual-caption">
            <span>FEATURED TALENT</span>
            <strong class="talent-name">青海しび</strong>
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
        <p>総合ポータルではブランド全体の方向性を示し、各事業部ではそれぞれ独立した役割と窓口を持つ構造を採用しています。相談の入口は分かりやすく、裏側の連携は密にすることで、活動・案件・制作のどこか一つだけが先走らない状態を目指します。</p>
        <p>たとえば企業案件では、タレント候補を出すだけではなく、企画意図、表現上の相性、必要素材、告知導線、スケジュールまで確認します。制作相談では、単なる発注代行ではなく、活動の世界観や使われ方に合わせて依頼内容を整えます。</p>
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
        <p>所属タレントの活動支援だけでなく、案件の相談、制作の相談、関係者が安心して関われる窓口づくりを通じて、業界全体の成熟に寄与していくことがミッションです。数字だけを追うのではなく、活動者の納得感、企業の成果、制作物の品質が同時に成立するかを判断軸にします。</p>
      </div>
    </div>
  </section>

  <section class="content-section">
    <div class="container section-head reveal">
      <div class="section-title-wrap">
        <span class="pink-bar"></span>
        <div>
          <span class="section-sub">OPERATING PRINCIPLES</span>
          <h2 class="section-title">運営で大切にしていること</h2>
        </div>
      </div>
    </div>
    <div class="container principle-grid reveal">
      <article class="principle-card cyber-clip-lg">
        <span>01</span>
        <h3>個性を先に見る</h3>
        <p>流行や数字だけで判断せず、タレントの声、得意分野、ファンとの関係性、活動ペースを踏まえて支援内容を設計します。</p>
      </article>
      <article class="principle-card cyber-clip-lg">
        <span>02</span>
        <h3>期待値を曖昧にしない</h3>
        <p>案件や制作では、目的、範囲、納期、確認フローを最初に整理し、関係者が安心して動ける状態をつくります。</p>
      </article>
      <article class="principle-card cyber-clip-lg">
        <span>03</span>
        <h3>短期施策を次の資産へ変える</h3>
        <p>単発の告知や制作物で終わらせず、次の企画、プロフィール強化、ブランド理解につながる形で蓄積します。</p>
      </article>
      <article class="principle-card cyber-clip-lg">
        <span>04</span>
        <h3>関係者の負担を減らす</h3>
        <p>連絡、素材確認、進行管理、相談先の整理を担い、活動者・企業・クリエイターが本来の役割に集中できるよう支えます。</p>
      </article>
    </div>
  </section>

  <section class="content-section content-section-alt">
    <div class="container section-grid reveal">
      <div>
        <div class="section-marker">
          <span class="marker-bar"></span>
          <span class="marker-text">WHO WE SERVE</span>
        </div>
        <h2 class="content-title">関係者ごとに、必要な支援を変える</h2>
      </div>
      <div class="role-stack">
        <article class="role-row cyber-clip">
          <span>Talent</span>
          <p>活動方針、案件対応、制作物、告知導線を整理し、長く活動を続けられる状態を支援します。</p>
        </article>
        <article class="role-row cyber-clip">
          <span>Company</span>
          <p>VTuber起用が初めてでも相談しやすいように、目的から逆算して施策内容と進行範囲を提案します。</p>
        </article>
        <article class="role-row cyber-clip">
          <span>Creator</span>
          <p>依頼内容、仕様、納期、使用範囲を明確にし、制作パートナーが力を発揮しやすい進行を整えます。</p>
        </article>
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
      <?php foreach ($divisions as $division):
        $divUrl = $division['url'] ?? ($division['slug'] . '/');
        $divExt = !empty($division['url']);
      ?>
        <article class="info-card cyber-clip-lg <?= h($division['class']) ?>">
          <div class="corner corner-tl"></div>
          <span class="info-num"><?= h($division['num']) ?></span>
          <span class="info-eyebrow"><?= h($division['title']) ?></span>
          <h3><?= h($division['title_jp']) ?></h3>
          <p><?= h($division['summary']) ?></p>
          <a href="<?= h($divUrl) ?>" class="card-link" <?= $divExt ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>詳しく見る <span aria-hidden="true">›</span></a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php render_footer(); ?>
