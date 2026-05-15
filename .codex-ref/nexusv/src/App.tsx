import { motion, useScroll, useTransform } from 'motion/react';
import {
  Palette,
  Video,
  Handshake,
  ShieldCheck,
  Gem,
  ArrowRight,
  MessageSquareHeart,
  ChevronRight,
  Star,
  Zap,
  MoveRight
} from 'lucide-react';

const Marquee = ({ text, outline = false, reverse = false, className = "" }: any) => (
  <div className={`flex overflow-hidden whitespace-nowrap select-none w-full ${className}`}>
    <motion.div
      animate={{ x: reverse ? ["-50%", "0%"] : ["0%", "-50%"] }}
      transition={{ duration: 25, repeat: Infinity, ease: "linear" }}
      className="flex shrink-0 items-center justify-center min-w-full"
    >
      {[...Array(4)].map((_, i) => (
        <span key={i} className={`px-4 text-4xl md:text-6xl font-display font-black uppercase shrink-0 ${outline ? 'text-stroke-solid' : 'text-[#0f172a]'}`}>
          {text} <Star className={`inline-block w-8 h-8 md:w-10 md:h-10 mx-4 ${outline ? 'text-[#0f172a] stroke-[3px] fill-transparent' : 'fill-[#0f172a]'}`} />
        </span>
      ))}
    </motion.div>
  </div>
);

export default function App() {
  return (
    <div className="min-h-screen bg-[#f8fafc] text-[#0f172a] font-sans overflow-x-hidden selection:bg-[#fde047]">
      {/* Background patterns */}
      <div className="fixed inset-0 bg-dot-pattern opacity-20 z-[-1] pointer-events-none mix-blend-multiply"></div>

      {/* Chaotic Background Text Elements */}
      <div className="fixed top-[20%] -right-[15%] opacity-[0.03] rotate-90 origin-right whitespace-nowrap z-[-1] pointer-events-none font-display font-black text-[25vw] leading-none">
        CREATIVE
      </div>
      <div className="fixed bottom-[10%] -left-[10%] opacity-10 -rotate-[10deg] whitespace-nowrap z-[-1] pointer-events-none font-display font-black text-[15vw] leading-none text-stroke-bg">
        NEXUS<br />AGENCY
      </div>

      {/* Nav */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-[#f8fafc] brutal-border-b p-4 sm:px-6">
        <div className="max-w-screen-2xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="bg-[#fde047] p-2 brutal-border">
              <Zap className="w-6 h-6 fill-[#0f172a]" />
            </div>
            <span className="font-display font-black text-3xl tracking-tighter">
              NEXUS<span className="text-[#f472b6]">V</span>
            </span>
            <span className="hidden sm:inline-block ml-4 text-[10px] font-bold px-2 py-1 bg-[#22d3ee] brutal-border tracking-widest">
              JP // EST_2024
            </span>
          </div>
          <div className="hidden lg:flex items-center gap-8 text-sm font-black tracking-widest font-display">
            <a href="#about" className="hover:bg-[#fde047] hover:px-2 transition-all">ABOUT (会社概要)</a>
            <a href="#features" className="hover:bg-[#fde047] hover:px-2 transition-all">FOR VTUBER (Vtuberの方へ)</a>
            <a href="#recruit" className="bg-[#f472b6] px-3 py-1 brutal-border text-white hover:text-[#0f172a] transition-all">RECRUIT (採用)</a>
          </div>
          <button className="bg-[#0f172a] text-white px-5 py-2.5 font-bold flex items-center gap-2 brutal-border brutal-shadow-sm brutal-shadow-hover text-sm sm:text-base">
            ご相談 <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      </nav>

      <main className="pt-20">
        {/* HERO SECTION */}
        <section className="relative pt-24 pb-32 min-h-[90vh] flex flex-col justify-center brutal-border-b bg-grid-pattern overflow-hidden">
          {/* Diagonal Ticker Tapes */}
          <div className="absolute top-[25%] -left-[20%] w-[150%] bg-[#fde047] brutal-border-t brutal-border-b py-5 rotate-[6deg] z-0 shadow-xl">
            <Marquee text="CREATE YOUR STAR ✦ 次世代VTUBERエージェンシー" />
          </div>
          <div className="absolute top-[45%] -left-[20%] w-[150%] bg-[#22d3ee] brutal-border-t brutal-border-b py-5 -rotate-[4deg] z-0 mix-blend-multiply">
            <Marquee text="THE ULTIMATE CREATIVE HUB ✦ クリエイターとタレントを繋ぐ" reverse outline />
          </div>
          <div className="absolute top-[65%] -left-[20%] w-[150%] bg-[#f472b6] brutal-border-t brutal-border-b py-3 rotate-[2deg] z-0">
            <Marquee text="VIRTUAL TALENT PRODUCTION ✦ NEXUS-V" />
          </div>

          <div className="max-w-7xl mx-auto px-6 w-full relative z-10 grid lg:grid-cols-2 gap-12 items-center">
            {/* Left Col - Typography */}
            <div className="relative">
              <div className="absolute -top-12 -left-8 w-24 h-24 bg-white brutal-border brutal-shadow-sm rounded-full flex items-center justify-center -rotate-12 transform hover:rotate-12 transition-transform">
                <span className="font-black text-2xl">No.1</span>
              </div>
              
              <div className="inline-block bg-[#0f172a] text-[#fde047] brutal-border px-4 py-2 font-black text-sm tracking-widest mb-6 transform rotate-2 brutal-shadow-sm">
                [ SYS.01 ] CREATIVE AGENCY
              </div>
              
              <h1 className="text-[5rem] sm:text-[6rem] md:text-[8rem] font-black font-display leading-[0.85] tracking-tighter text-[#0f172a] relative">
                <span className="relative inline-block">
                  <span className="absolute inset-0 text-[#f472b6] translate-x-2 translate-y-2 -z-10 mix-blend-multiply">YOUR</span>
                  YOUR
                </span> <br />
                <span className="text-white text-stroke-solid">STAR</span><br />
                IDENTITY.
              </h1>
              
              <div className="mt-12 bg-white brutal-border brutal-shadow p-6 md:p-8 max-w-lg transform -rotate-1 relative group">
                <div className="absolute -right-4 -top-4 bg-[#22d3ee] p-2 brutal-border font-black text-xs rotate-12 group-hover:rotate-[24deg] transition-transform">
                  NEW WAVE
                </div>
                <p className="text-lg md:text-xl font-bold leading-relaxed">
                  個人VTuberの活動を最前線へ導く。<br />
                  <span className="bg-[#fde047] px-1 font-black">「なりたい姿」</span>を最高のクオリティで実現する、新時代のクリエイティブ・ネットワーク。
                </p>
                <div className="mt-8 flex flex-col sm:flex-row gap-4">
                  <button className="flex-1 bg-[#0f172a] text-white brutal-border brutal-shadow-sm px-6 py-4 font-black flex items-center justify-center gap-2 hover:bg-[#22d3ee] hover:text-[#0f172a] transition-colors brutal-shadow-hover text-lg">
                     無料相談・お見積り <ArrowRight className="w-5 h-5" />
                  </button>
                  <button className="flex-1 bg-white text-[#0f172a] brutal-border brutal-shadow-sm px-6 py-4 font-black hover:bg-[#fde047] transition-colors brutal-shadow-hover text-lg flex justify-center items-center">
                     クリエイター一覧
                  </button>
                </div>
              </div>
            </div>

            {/* Right Col - Image Collage */}
            <div className="relative mt-16 lg:mt-0 flex justify-center lg:justify-end">
              <div className="relative z-10 w-full max-w-md aspect-[4/5] bg-white brutal-border brutal-shadow p-4 transform rotate-3 hover:rotate-1 transition-transform duration-500">
                <div className="absolute inset-0 bg-[#f472b6] brutal-border -z-10 translate-x-4 translate-y-4"></div>
                <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2400&auto=format&fit=crop" alt="Abstract Art" className="w-full h-full object-cover brutal-border filter contrast-125" />
                
                {/* Floating Spinning Badge */}
                <motion.div 
                  animate={{ rotate: 360 }} 
                  transition={{ duration: 15, repeat: Infinity, ease: "linear" }}
                  className="absolute -bottom-10 -left-10 w-36 h-36 bg-[#fde047] rounded-full flex items-center justify-center brutal-border brutal-shadow-sm"
                >
                  <svg viewBox="0 0 100 100" className="w-full h-full p-2">
                    <path id="circlePath" d="M 50, 50 m -35, 0 a 35,35 0 1,1 70,0 a 35,35 0 1,1 -70,0" fill="none" />
                    <text className="font-display font-black text-sm tracking-widest fill-[#0f172a]">
                      <textPath href="#circlePath" startOffset="0%">TOP QUALITY CREATORS ✦ DESIGN </textPath>
                    </text>
                  </svg>
                  <Star className="absolute w-8 h-8 fill-[#0f172a]" />
                </motion.div>

                {/* Stickers */}
                <div className="absolute top-8 -right-8 bg-[#22d3ee] brutal-border px-5 py-2 font-black rotate-12 brutal-shadow-sm text-lg">
                  QUALITY 100%
                </div>
                <div className="absolute bottom-16 -right-6 bg-white brutal-border p-2 font-black -rotate-12 brutal-shadow-sm">
                  <div className="border-2 border-dashed border-[#0f172a] px-2 py-1">APPROVED</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* FEATURES SECTION */}
        <section id="features" className="py-32 px-6 relative brutal-border-b bg-white overflow-hidden">
          {/* Huge background typography */}
          <div className="absolute top-[10%] -right-[10%] text-[15vw] font-black font-display text-stroke-bg pointer-events-none select-none rotate-6 leading-none">
            PROMISE
          </div>

          <div className="max-w-7xl mx-auto relative z-10">
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
              <div>
                <div className="inline-block bg-[#22d3ee] brutal-border px-3 py-1 font-black text-sm tracking-widest mb-4">
                  02 // FOR VTUBERS
                </div>
                <h2 className="text-4xl md:text-6xl font-black leading-tight">
                  <span className="bg-[#fde047] px-2">個人VTuber</span>を最前線へ導く<br className="hidden md:block"/>
                  3つの約束。
                </h2>
              </div>
              <p className="text-lg font-bold max-w-md bg-white p-4 brutal-border brutal-shadow-sm transform rotate-1">
                依頼の仕方がわからない、クオリティが不安、クリエイターとのやり取りが難しい。<br />
                そんな個人活動のハードルを私たちがクリアにします。
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-8 md:gap-6 mt-12">
              {[
                { 
                  icon: ShieldCheck, 
                  color: 'bg-[#fde047]',
                  title: '安心の進行管理・品質保証', 
                  desc: '依頼から納品まで、専門のディレクターが間に入りサポート。金銭トラブルやコミュニケーションのすれ違いを未然に防ぎます。' 
                },
                { 
                  icon: Gem, 
                  color: 'bg-[#f472b6]',
                  title: 'トップクラスのクリエイター', 
                  desc: '当社独自の審査を通過した、実績あるイラストレーターやモデラーのみが在籍。あなたの理想を妥協なく形にします。' 
                },
                { 
                  icon: MessageSquareHeart, 
                  color: 'bg-[#22d3ee]',
                  title: 'デビュー後も続く継続サポート', 
                  desc: '日々の配信サムネイル、切り抜き動画編集、新衣装の制作など、活動に必要なクリエイティブを継続的に支援します。' 
                }
              ].map((item, idx) => (
                <div 
                  key={idx}
                  className={`bg-[#f8fafc] brutal-border brutal-shadow p-8 relative group transition-transform hover:-translate-y-2`}
                >
                  <div className="absolute top-0 right-0 p-4 font-black text-[4rem] leading-none text-stroke-bg select-none">
                    0{idx + 1}
                  </div>
                  <div className={`w-16 h-16 brutal-border ${item.color} flex items-center justify-center mb-8 transform -rotate-3 group-hover:rotate-6 transition-transform brutal-shadow-sm`}>
                    <item.icon className="w-8 h-8 text-[#0f172a]" />
                  </div>
                  <h4 className="text-2xl font-black mb-4 relative z-10">{item.title}</h4>
                  <div className="w-full h-1 bg-[#0f172a] mb-4"></div>
                  <p className="text-[#334155] font-bold leading-relaxed relative z-10">{item.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* RECRUIT & COLLABORATION DUAL SECTION */}
        <section id="recruit" className="relative brutal-border-b bg-[#0f172a] text-white overflow-hidden">
          <div className="absolute inset-0 bg-grid-pattern opacity-10 blur-sm"></div>
          {/* Pink massive circle */}
          <div className="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-[#f472b6] rounded-full blur-[100px] opacity-30 pointer-events-none"></div>

          <div className="grid lg:grid-cols-2">
            
            {/* Recruit Panel */}
            <div className="p-8 md:p-16 lg:p-24 relative brutal-border-b lg:brutal-border-b-0 lg:border-r-4 border-white/20">
              <div className="inline-block bg-[#f472b6] text-[#0f172a] px-3 py-1 font-black text-sm tracking-widest mb-6">
                03 // FOR CREATORS
              </div>
              <h2 className="text-5xl md:text-6xl font-black mb-8 leading-[1.1]">
                次世代のスターを、<br/>
                <span className="text-[#fde047]">共に創り上げる才能を。</span>
              </h2>
              <p className="text-lg font-bold text-gray-300 mb-12 leading-relaxed">
                実績づくりや、安定した制作活動の場として参加しませんか？<br/>
                イラストレーター、モデラー、動画編集者を募集しています。
              </p>

              <div className="space-y-6">
                {/* Job 1 */}
                <div className="bg-white/5 border border-white/20 p-6 hover:bg-white/10 transition-colors group cursor-pointer relative overflow-hidden">
                  <div className="absolute h-full w-2 bg-[#f472b6] left-0 top-0 transform scale-y-0 group-hover:scale-y-100 transition-transform origin-bottom duration-300"></div>
                  <div className="flex items-center justify-between pl-4">
                    <div>
                      <h3 className="text-2xl font-black mb-2 flex items-center gap-3">
                        <Palette className="w-6 h-6 text-[#f472b6]" /> メインイラストレーター
                      </h3>
                      <p className="text-gray-400 font-bold text-sm">キャラクターデザイン・パーツ分け (Mothers & Fathers)</p>
                    </div>
                    <MoveRight className="w-8 h-8 text-white/50 group-hover:text-white transform group-hover:translate-x-2 transition-all" />
                  </div>
                </div>

                {/* Job 2 */}
                <div className="bg-white/5 border border-white/20 p-6 hover:bg-white/10 transition-colors group cursor-pointer relative overflow-hidden">
                  <div className="absolute h-full w-2 bg-[#22d3ee] left-0 top-0 transform scale-y-0 group-hover:scale-y-100 transition-transform origin-bottom duration-300"></div>
                  <div className="flex items-center justify-between pl-4">
                    <div>
                      <h3 className="text-2xl font-black mb-2 flex items-center gap-3">
                        <Video className="w-6 h-6 text-[#22d3ee]" /> サポートクリエイター
                      </h3>
                      <p className="text-gray-400 font-bold text-sm">Live2D作成・動画編集・デザイン制作 (Creators)</p>
                    </div>
                    <MoveRight className="w-8 h-8 text-white/50 group-hover:text-white transform group-hover:translate-x-2 transition-all" />
                  </div>
                </div>
              </div>
            </div>

            {/* Collaboration Panel */}
            <div id="collaboration" className="p-8 md:p-16 lg:p-24 bg-[#fde047] text-[#0f172a] relative">
              <motion.div 
                 animate={{ y: [0, -10, 0] }}
                 transition={{ duration: 2, repeat: Infinity }}
                 className="absolute top-[10%] right-[10%] w-32 h-32 bg-white brutal-border rounded-full flex justify-center items-center font-black brutal-shadow text-xl"
              >
                B2B!
              </motion.div>

              <div className="inline-block bg-[#0f172a] text-white px-3 py-1 font-black text-sm tracking-widest mb-6">
                04 // CONNECTION
              </div>
              <h2 className="text-5xl md:text-6xl font-black mb-8 leading-[1.1]">
                既存タレント様・<br/>
                企業様との<span className="text-[#f472b6]">連携</span>。
              </h2>
              
              <div className="bg-white brutal-border brutal-shadow-sm p-8 mb-8 transform rotate-1">
                <Handshake className="w-12 h-12 mb-6 text-[#0f172a]" />
                <p className="text-xl font-bold leading-relaxed">
                  「現在のモデルをアップデートしたい」「新規企画に向けたチームを探している」など、
                  すでに活動中のタレント様や、企業様からの制作ご依頼も歓迎しております。
                </p>
              </div>

              <button className="bg-[#0f172a] text-white brutal-border brutal-shadow-sm px-8 py-5 font-black text-xl flex items-center justify-between w-full hover:bg-white hover:text-[#0f172a] transition-colors brutal-shadow-hover group">
                 <span>詳細を見る・お問い合わせ</span>
                 <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" className="group-hover:translate-x-2 transition-transform"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </div>
            
          </div>
        </section>
      </main>

      {/* FOOTER */}
      <footer className="bg-[#f8fafc] pt-20 pb-10 px-6 border-t-[8px] border-[#0f172a] relative z-10 overflow-hidden">
        <div className="absolute top-[-50%] left-[-10%] text-[20vw] font-display font-black text-stroke-bg pointer-events-none select-none -rotate-12">
          NEXUSV
        </div>
        
        <div className="max-w-7xl mx-auto relative z-10 grid md:grid-cols-4 gap-12 mb-16 border-b-4 border-[#0f172a] pb-16">
          <div className="md:col-span-2">
            <div className="flex items-center gap-2 mb-6">
              <div className="bg-[#0f172a] p-2">
                 <Zap className="w-8 h-8 fill-[#fde047] text-[#fde047]" />
              </div>
              <span className="font-display font-black text-4xl tracking-tighter">
                NEXUS<span className="text-[#f472b6]">V</span>
              </span>
            </div>
            <p className="text-xl font-bold leading-relaxed max-w-sm">
              VTuberとクリエイターをつなぐ、<br />
              次世代のエージェンシー。
            </p>
          </div>
          <div>
            <h4 className="font-black mb-6 tracking-widest uppercase font-display border-b-2 border-[#0f172a] pb-2 inline-block">VTuberの方へ</h4>
            <ul className="space-y-4 font-bold tracking-wide">
              <li><a href="#" className="hover:text-[#f472b6] hover:translate-x-2 inline-block transition-transform">サービス概要</a></li>
              <li><a href="#" className="hover:text-[#f472b6] hover:translate-x-2 inline-block transition-transform">制作のながれ</a></li>
              <li><a href="#" className="hover:text-[#f472b6] hover:translate-x-2 inline-block transition-transform">無料相談のご予約</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-black mb-6 tracking-widest uppercase font-display border-b-2 border-[#0f172a] pb-2 inline-block">クリエイター募集</h4>
            <ul className="space-y-4 font-bold tracking-wide">
              <li><a href="#" className="hover:text-[#22d3ee] hover:translate-x-2 inline-block transition-transform">メインイラストレーター募集</a></li>
              <li><a href="#" className="hover:text-[#22d3ee] hover:translate-x-2 inline-block transition-transform">サポートクリエイター募集</a></li>
              <li><a href="#" className="hover:text-[#22d3ee] hover:translate-x-2 inline-block transition-transform">企業・ご相談</a></li>
            </ul>
          </div>
        </div>
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
          <div className="font-black tracking-widest uppercase font-display bg-[#0f172a] text-white px-4 py-2 brutal-border">
            © {new Date().getFullYear()} NEXUS_V CREATIVE.
          </div>
          <div className="flex flex-wrap justify-center gap-6 font-bold tracking-wide uppercase text-sm">
            <a href="#" className="hover:bg-[#fde047] px-2 transition-colors">Terms of Service</a>
            <a href="#" className="hover:bg-[#fde047] px-2 transition-colors">Privacy Policy</a>
            <a href="#" className="hover:bg-[#fde047] px-2 transition-colors">Contact</a>
          </div>
        </div>
      </footer>
    </div>
  );
}
