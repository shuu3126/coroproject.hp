import { useEffect, useState } from 'react';

const services = [
  {
    emoji: '🎨',
    label: 'ILLUSTRATION',
    title: 'イラスト制作',
    desc: '立ち絵・一枚絵・記念イラスト・表情差分・グッズ用イラストまで、活動内容に合わせてご提案します。',
    time: '1〜4週間',
  },
  {
    emoji: '🤖',
    label: 'LIVE2D MODEL',
    title: 'Live2Dモデル制作・改修',
    desc: '新規モデル制作、パーツ分け、モデリング、表情追加、衣装差分などをまとめて相談できます。',
    time: '1〜3ヶ月',
    featured: true,
  },
  {
    emoji: '✏️',
    label: 'LOGO DESIGN',
    title: 'ロゴ・ロゴタイプ',
    desc: 'タレント名ロゴ、企画ロゴ、配信用ブランドロゴなど、世界観に合うデザインを制作します。',
    time: '1〜2週間',
  },
  {
    emoji: '🖼️',
    label: 'THUMBNAIL',
    title: 'サムネイル制作',
    desc: '配信・動画のサムネイルを制作。継続運用しやすいテンプレート作成も対応できます。',
    time: '3〜5営業日',
  },
  {
    emoji: '🌟',
    label: 'KEY VISUAL',
    title: 'キービジュアル',
    desc: '記念配信・イベント・新衣装公開など、特別な節目に使えるメインビジュアルを制作します。',
    time: '2〜4週間',
  },
  {
    emoji: '🎬',
    label: 'VIDEO EDIT',
    title: '動画編集',
    desc: 'ショート動画・切り抜き・告知動画・活動まとめなど、目的に合わせた編集者をご提案します。',
    time: '3〜10営業日',
  },
  {
    emoji: '🎵',
    label: 'MUSIC / SE',
    title: 'BGM・効果音',
    desc: '配信用BGM、動画用ジングル、オリジナル楽曲、効果音など音まわりも相談できます。',
    time: '2〜4週間',
  },
];

const pains = [
  ['🔍', '依頼先を探すだけで、時間も気力もなくなってしまう'],
  ['❓', 'Live2Dや権利のことが難しくて、何から聞けばいいかわからない'],
  ['💸', '予算が少なくて、クリエイターさんに相談するのが不安'],
  ['📄', '商用利用・グッズ化・クレジット表記の確認がこわい'],
  ['😶', 'イメージはあるのに、依頼文にうまくまとめられない'],
];

const flow = [
  { num: '01', title: '無料相談', text: 'やりたいこと・予算・納期がふわっとしていても大丈夫です。まずは状況を聞かせてください。' },
  { num: '02', title: 'ヒアリング', text: '用途、イメージ、参考資料、活動方針を一緒に整理します。' },
  { num: '03', title: 'クリエイター選定', text: '内容に合うクリエイター・制作方法・進行スケジュールをご提案します。' },
  { num: '04', title: 'お見積り', text: '料金、納期、修正範囲、使用用途を確認してから制作に入ります。' },
  { num: '05', title: '制作進行', text: 'ラフや中間確認を挟みながら、完成まで丁寧に進行します。' },
  { num: '06', title: '納品', text: '用途に合わせた形式で納品。必要に応じて使い方もフォローします。' },
  { num: '07', title: '活動へ活用', text: '公開文・告知・次の制作展開まで、希望があれば一緒に考えます。' },
];

const reasons = [
  { label: 'ONE STOP', title: '窓口ひとつで、制作まわりをまとめて相談', desc: 'イラスト、Live2D、ロゴ、動画などを別々に探す手間を減らし、活動者側の負担を軽くします。' },
  { label: 'VTUBER FIRST', title: '個人VTuberの予算感と不安に寄り添う', desc: 'いきなり高額な提案ではなく、今の活動段階に合わせて必要なものから一緒に整理します。' },
  { label: 'RIGHTS CHECK', title: '権利・利用範囲・クレジットも確認', desc: 'グッズ利用、配信利用、SNS掲載など、後から困りやすい部分を発注前に確認します。' },
  { label: 'CREATOR CARE', title: 'クリエイター側も安心して制作できる進行', desc: '依頼内容を整理して伝えることで、活動者と制作者のすれ違いを減らします。' },
];

const faqs = [
  { q: '個人VTuberでも依頼できますか？', a: 'はい。むしろ個人で活動されている方のご相談を歓迎しています。活動規模に関係なく、まずはお気軽にご相談ください。' },
  { q: 'まだ依頼内容が決まっていなくても大丈夫ですか？', a: '大丈夫です。「新衣装を作りたい」「サムネを整えたい」くらいの段階から一緒に整理できます。' },
  { q: '予算が少なくても相談できますか？', a: '可能です。ご予算内でできる範囲、優先順位、段階的な制作方法をご提案します。' },
  { q: 'クリエイターを指定できますか？', a: 'ご希望があれば確認します。スケジュールや条件によっては、近いテイストの別候補をご提案する場合があります。' },
  { q: '著作権や商用利用はどうなりますか？', a: '案件ごとに利用範囲を確認します。配信・SNS・グッズ・広告利用など、必要な用途を事前に整理します。' },
  { q: '急ぎの依頼にも対応できますか？', a: '内容によって対応できる場合があります。品質を守るため、難しい場合は現実的なスケジュールをご提案します。' },
];

const marqueeItems = ['ILLUST', 'LIVE2D', 'LOGO', 'THUMBNAIL', 'KEY VISUAL', 'MOVIE', 'BGM', 'CREATIVE SUPPORT'];

export default function App() {
  const [isScrolled, setIsScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const [showFloatingCta, setShowFloatingCta] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 14);
      setShowFloatingCta(window.scrollY > 520);
    };

    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12 },
    );

    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
    return () => observer.disconnect();
  }, []);

  const closeMenu = () => setMenuOpen(false);

  return (
    <div className="page-shell">
      <header className={`site-header ${isScrolled ? 'is-scrolled' : ''}`}>
        <div className="container header-inner">
          <a className="brand" href="#top" aria-label="CORO PROJECT Creative Support">
            <img
              className="brand-logo"
              src="/images/toukalogo.png"
              alt="CORO PROJECT"
              onError={(event) => {
                event.currentTarget.style.display = 'none';
              }}
            />
            <span className="brand-main">CORO<br />PROJECT</span>
            <span className="brand-sub">Creative<br />Support</span>
          </a>

          <nav className="site-nav" aria-label="Creative Support navigation">
            <a href="#services"><span>サービス</span>SERVICE</a>
            <a href="#flow"><span>ご依頼の流れ</span>FLOW</a>
            <a href="#reason"><span>選ばれる理由</span>REASON</a>
            <a href="#join"><span>募集</span>JOIN</a>
            <a href="#faq"><span>よくある質問</span>FAQ</a>
          </nav>

          <a className="header-cta" href="#contact">今すぐ相談する<span>→</span></a>
          <button
            className="menu-toggle"
            type="button"
            aria-label="メニューを開く"
            aria-expanded={menuOpen}
            onClick={() => setMenuOpen((current) => !current)}
          >
            <span></span><span></span><span></span>
          </button>
        </div>

        <div className={`mobile-nav ${menuOpen ? 'is-open' : ''}`}>
          <a href="#services" onClick={closeMenu}>サービス</a>
          <a href="#flow" onClick={closeMenu}>ご依頼の流れ</a>
          <a href="#reason" onClick={closeMenu}>選ばれる理由</a>
          <a href="#join" onClick={closeMenu}>クリエイター募集</a>
          <a href="#faq" onClick={closeMenu}>FAQ</a>
          <a href="#contact" onClick={closeMenu}>相談する</a>
        </div>
      </header>

      <main id="top">
        <section className="hero">
          <div className="hero-splash" aria-hidden="true"></div>
          <div className="hero-marquee hero-marquee-top" aria-hidden="true">
            <div className="hero-marquee-track">CREATIVE SUPPORT // VTUBER CREATIVE // ILLUSTRATION // LIVE2D // DESIGN // MOVIE // CREATIVE SUPPORT // VTUBER CREATIVE // ILLUSTRATION // LIVE2D // DESIGN // MOVIE //</div>
          </div>
          <div className="hero-marquee hero-marquee-bottom" aria-hidden="true">
            <div className="hero-marquee-track reverse">YOUR VISION OUR CREATION // CORO PROJECT // CREATIVE PARTNER // YOUR VISION OUR CREATION // CORO PROJECT // CREATIVE PARTNER //</div>
          </div>

          <div className="container hero-grid">
            <div className="hero-copy reveal is-visible">
              <p className="hero-ribbon">個人VTuberとクリエイターをつなぐ制作相談所。</p>
              <h1>
                <span className="headline-line"><em className="paint-pink">想いを、</em>作品に。</span>
                <span className="headline-line"><em className="paint-pink">一緒に、</em><em className="paint-purple">最高を</em></span>
                <span className="headline-line">つくろう。</span>
              </h1>
              <p className="hero-script">Your Vision,<br />Our Creation.</p>
              <p className="hero-lead">
                イラスト・Live2D・ロゴ・動画編集まで、VTuber活動に必要な制作をワンストップでサポート。<br />
                「誰に頼めばいいかわからない」その段階から、まるっと一緒に整理します。
              </p>
              <div className="hero-points">
                <div><span className="point-icon icon-heart" aria-hidden="true"></span><b>初回相談無料</b><small>ふわっと相談OK</small></div>
                <div><span className="point-icon icon-team" aria-hidden="true"></span><b>まるっと進行</b><small>依頼文から整理</small></div>
                <div><span className="point-icon icon-price" aria-hidden="true"></span><b>個人予算OK</b><small>段階制作も提案</small></div>
                <div><span className="point-icon icon-check" aria-hidden="true"></span><b>権利も確認</b><small>利用範囲を明確に</small></div>
              </div>
              <div className="hero-actions">
                <a className="primary-button hero-button" href="#contact">まずは無料で相談する<span>→</span></a>
                <a className="ghost-button" href="#services">できることを見る</a>
              </div>
            </div>

            <div className="hero-visual reveal is-visible" aria-label="CORO PROJECT Creative Support メインビジュアル">
              <div className="visual-badge badge-top">VTuber<br />Creative</div>
              <img
                className="topgirl"
                src="/images/topgirl.png"
                alt="CORO PROJECT Creative Support メインビジュアル"
                onError={(event) => {
                  event.currentTarget.style.display = 'none';
                }}
              />
              <div className="visual-card card-illust">ILLUST</div>
              <div className="visual-card card-live2d">LIVE2D</div>
              <div className="visual-card card-movie">MOVIE</div>
              <span className="vertical-note">CORO PROJECT CREATIVE SUPPORT</span>
            </div>
          </div>
        </section>

        <section className="marquee-section" aria-hidden="true">
          <div className="section-marquee-track">
            {[...marqueeItems, ...marqueeItems, ...marqueeItems].map((item, index) => (
              <span key={`${item}-${index}`}>✦ {item}</span>
            ))}
          </div>
        </section>

        <section className="section section-soft" id="target">
          <div className="container">
            <div className="section-title center reveal">
              <span>FOR VTUBERS</span>
              <h2>こんな悩み、ありませんか？</h2>
              <p>制作を頼みたい気持ちはあるのに、最初の一歩で止まってしまう。そんな人のための窓口です。</p>
            </div>

            <div className="pain-grid reveal">
              {pains.map(([emoji, text]) => (
                <article className="pain-card" key={text}>
                  <span>{emoji}</span>
                  <h3>{text}</h3>
                </article>
              ))}
              <article className="solution-card">
                <span>✅</span>
                <h3>全部、まとめて相談できます。</h3>
                <p>依頼内容の整理、クリエイター選定、進行、権利確認、納品まで。知識ゼロの状態でも大丈夫です。</p>
              </article>
            </div>
          </div>
        </section>

        <section className="section section-white" id="services">
          <div className="container">
            <div className="section-title reveal">
              <span>SERVICES</span>
              <h2>VTuber活動に必要な制作を、まとめて。</h2>
              <p>単発依頼から継続制作まで、活動フェーズに合わせて必要なメニューを組み合わせられます。</p>
            </div>

            <div className="service-grid">
              {services.map((service, index) => (
                <article className={`service-card reveal ${service.featured ? 'featured' : ''}`} style={{ transitionDelay: `${index * 50}ms` }} key={service.title}>
                  {service.featured && <div className="popular-badge">★ POPULAR</div>}
                  <div className="service-emoji">{service.emoji}</div>
                  <span className="service-label">{service.label}</span>
                  <h3>{service.title}</h3>
                  <p>{service.desc}</p>
                  <b>{service.time}</b>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="section section-flow" id="flow">
          <div className="container">
            <div className="section-title center reveal">
              <span>FLOW</span>
              <h2>ご依頼の流れ</h2>
              <p>「何を送ればいいかわからない」状態からでも、順番に整理して進めます。</p>
            </div>

            <div className="flow-list reveal">
              {flow.map((item) => (
                <article className="flow-item" key={item.num}>
                  <span className="flow-num">{item.num}</span>
                  <div>
                    <h3>{item.title}</h3>
                    <p>{item.text}</p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="section section-dark" id="reason">
          <div className="container">
            <div className="section-title reveal light">
              <span>REASON</span>
              <h2>活動者にも、クリエイターにも、やさしい制作進行を。</h2>
              <p>依頼する側と作る側、どちらかだけが無理をする形にしない。だから長く続けやすい制作体制を目指します。</p>
            </div>

            <div className="reason-list">
              {reasons.map((reason, index) => (
                <article className="reason-item reveal" key={reason.title}>
                  <span className="reason-num">0{index + 1}</span>
                  <div>
                    <b>{reason.label}</b>
                    <h3>{reason.title}</h3>
                    <p>{reason.desc}</p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="section cta-band-section">
          <div className="container cta-band reveal">
            <div>
              <p>あなたの「やりたい！」を、</p>
              <h2>一緒に叶えましょう。</h2>
              <span>まだ依頼するか決めていなくても大丈夫です。まずは、作りたいものや悩んでいることを聞かせてください。</span>
            </div>
            <a className="white-button" href="#contact">無料で相談する<span>→</span></a>
          </div>
        </section>

        <section className="section section-join" id="join">
          <div className="container join-grid">
            <div className="section-title reveal">
              <span>FOR CREATORS</span>
              <h2>あなたの制作スキルを、VTuberの活動へ。</h2>
              <p>営業、依頼内容の整理、条件確認、進行管理。制作以外の負担を減らし、クリエイターが作ることに集中できる形をつくります。</p>
              <a className="outline-button" href="#contact">クリエイター登録について相談する</a>
            </div>

            <div className="join-card reveal">
              {['登録フォームから応募', 'ポートフォリオ確認', '条件・単価のすり合わせ', '案件ごとに制作依頼'].map((item, index) => (
                <div className="join-step" key={item}>
                  <span>{index + 1}</span>
                  <b>{item}</b>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="section section-faq" id="faq">
          <div className="container small-container">
            <div className="section-title center reveal">
              <span>FAQ</span>
              <h2>よくあるご質問</h2>
            </div>

            <div className="faq-list reveal">
              {faqs.map((faq) => (
                <details className="faq-item" key={faq.q}>
                  <summary>{faq.q}</summary>
                  <p>{faq.a}</p>
                </details>
              ))}
            </div>
          </div>
        </section>

        <section className="section contact-section" id="contact">
          <div className="contact-glow" aria-hidden="true"></div>
          <div className="container small-container contact-card reveal">
            <span>FREE CONSULTATION</span>
            <h2>まずは、気軽に相談してください。</h2>
            <p>
              「依頼したいけど、まだ迷っている」<br />
              「いくらかかるか知りたいだけ」<br />
              そんな段階でも大丈夫です。
            </p>

            <form className="contact-form" action="#" method="post">
              <div className="form-grid">
                <label>
                  お名前 <small>*</small>
                  <input type="text" name="name" placeholder="VTuber名・お名前" required />
                </label>
                <label>
                  メールアドレス <small>*</small>
                  <input type="email" name="email" placeholder="contact@example.com" required />
                </label>
              </div>

              <div className="form-grid">
                <label>
                  ご依頼の種類
                  <select name="category" defaultValue="その他・複数">
                    {services.map((service) => <option key={service.title}>{service.title}</option>)}
                    <option>その他・複数</option>
                    <option>クリエイター登録について</option>
                  </select>
                </label>
                <label>
                  ご予算の目安
                  <select name="budget" defaultValue="未定">
                    <option>〜1万円</option>
                    <option>1〜3万円</option>
                    <option>3〜10万円</option>
                    <option>10万円以上</option>
                    <option>未定</option>
                  </select>
                </label>
              </div>

              <label>
                ご相談内容
                <textarea name="message" rows={6} placeholder="作りたいもの、参考画像の有無、納期、ご予算などを自由にお書きください。"></textarea>
              </label>

              <button className="primary-button form-button" type="submit">送信する<span>→</span></button>
            </form>
            <small className="contact-note">※このフォームは見た目のみです。公開時は既存の contact.php や送信APIに接続してください。</small>
          </div>
        </section>
      </main>

      <footer className="site-footer">
        <div className="container footer-inner">
          <div>
            <a className="brand footer-brand" href="#top" aria-label="CORO PROJECT Creative Support">
              <span className="brand-main">CORO<br />PROJECT</span>
              <span className="brand-sub">Creative<br />Support</span>
            </a>
            <p>VTuber・クリエイターの夢を応援する制作サポートチーム</p>
          </div>
          <nav>
            <a href="#services">SERVICE</a>
            <a href="#flow">FLOW</a>
            <a href="#faq">FAQ</a>
            <a href="#contact">CONTACT</a>
          </nav>
        </div>
        <p className="copyright">© CORO PROJECT All Rights Reserved.</p>
      </footer>

      <a className={`floating-cta ${showFloatingCta ? 'is-visible' : ''}`} href="#contact">無料相談する</a>
    </div>
  );
}
