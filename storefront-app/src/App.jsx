import {
  ArrowRight, BadgeCheck, Bird, Bone, Cat, ChevronDown, CircleUserRound,
  Dog, Fish, HeartHandshake, LockKeyhole, Mail, MapPin, Menu, MessageCircle, PackageCheck, RefreshCcw,
  PawPrint, Phone, Search, ShieldCheck, ShoppingBag, Truck, Wheat, X,
} from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'

// การ์ดหมวดหมู่หน้าแรก — เป็นแค่ลิงก์ตกแต่งพาไปหน้าสินค้ารวม ไม่ใช่ตัวกรองจริง (ตัวกรองจริง
// ที่หน้า "สินค้าทั้งหมด" ดึงชื่อประเภทจริงจากระบบมาแทน ดูตัวแปร categoryNames ด้านล่าง)
const categoryCards = [
  { name: 'อาหารสุนัข', caption: 'ครบทุกช่วงวัย', icon: Dog, tint: '#ffe8ed', image: '/assets/dog-category-v2.png', position: '50% 35%' },
  { name: 'อาหารแมว', caption: 'ทั้งแบบแห้งและเปียก', icon: Cat, tint: '#fff1d9', image: '/assets/cat-category.png', position: '50% 38%' },
  { name: 'อาหารปลา', caption: 'ปลาดุก ปลานิล และปลาเลี้ยง', icon: Fish, tint: '#e3f5ff', image: '/assets/fish-category.png', position: '50% 50%' },
  { name: 'อาหารสัตว์ฟาร์ม', caption: 'ไก่ หมู โค เป็ด และกบ', icon: Wheat, tint: '#eaf7df' },
  { name: 'อาหารสัตว์เล็ก', caption: 'นกและกระต่าย', icon: Bird, tint: '#f1e9ff' },
  { name: 'อุปกรณ์เลี้ยงสัตว์', caption: 'ถังน้ำ ราง และของใช้', icon: Bone, tint: '#ffece0' },
]

// สีพื้นหลังการ์ดสินค้า วนใช้ตามลำดับ — เดิมผูกกับสินค้าปลอมทีละตัว ตอนนี้ดึงสินค้าจริงมา
// จำนวนไม่แน่นอนเลยวนสีตาม index แทน
const productCardColors = ['#fce9f7', '#ffe9f0', '#e8f2ff', '#fff0df', '#e5f3e6', '#fff1dd']

const services = [
  { icon: Truck, title: 'จัดส่งไว', text: 'ทั่วประเทศ' },
  { icon: ShieldCheck, title: 'ของแท้ 100%', text: 'เชื่อถือได้' },
  { icon: HeartHandshake, title: 'บริการด้วยใจ', text: 'ใส่ใจทุกออเดอร์' },
  { icon: LockKeyhole, title: 'ชำระเงินปลอดภัย', text: 'หลายช่องทาง' },
  { icon: RefreshCcw, title: 'คืนสินค้า/เปลี่ยนสินค้า', text: 'ภายใน 7 วัน' },
]

const brands = ['Whiskas', 'BOBBI Cat', 'BOBBI Dog', 'Ole Kat', 'Bingo Star', 'Doggy Pro']

// สินค้าจริงส่วนใหญ่ยังไม่มีรูปในระบบ (ต้องอัปโหลดทีหลังในหน้า "สินค้า/ราคา") — ตัวนี้ตกลง
// เป็นวงกลมอักษรตัวแรกของชื่อสินค้าแทนรูปที่ยังไม่มี เหมือนที่หน้าเว็บเดิม (Blade) ทำ
function ProductPicture({ product }) {
  if (product.photo_url) {
    return <img src={product.photo_url} alt={product.name} loading="lazy" />
  }

  return <span className="product-placeholder" aria-hidden="true">{product.name.charAt(0)}</span>
}

function App() {
  const [menuOpen, setMenuOpen] = useState(false)
  const [searchOpen, setSearchOpen] = useState(false)
  const [navFixed, setNavFixed] = useState(false)
  const [page, setPage] = useState('home')
  const [catalogFilter, setCatalogFilter] = useState('ทั้งหมด')
  const [catalogSearch, setCatalogSearch] = useState('')

  // สินค้า + ประเภทสินค้าจริงจากระบบจัดการสต็อก — ดึงครั้งเดียวตอนเปิดเว็บ (จำนวนสินค้า
  // ไม่ได้เยอะมาก กรอง/ค้นหาฝั่ง browser เอาก็พอ ไม่ต้องยิง request ใหม่ทุกครั้งที่พิมพ์ค้นหา)
  const [products, setProducts] = useState([])
  const [categoryNames, setCategoryNames] = useState([])
  const [loadError, setLoadError] = useState(false)

  useEffect(() => {
    Promise.all([
      fetch('/shop/api/products').then((r) => r.json()),
      fetch('/shop/api/categories').then((r) => r.json()),
    ])
      .then(([productsRes, categoriesRes]) => {
        setProducts(productsRes.data ?? [])
        setCategoryNames((categoriesRes.data ?? []).map((c) => c.name))
      })
      .catch(() => setLoadError(true))
  }, [])

   useEffect(() => {
    const handleScroll = () => setNavFixed(window.scrollY > 42)
    handleScroll()
    window.addEventListener('scroll', handleScroll, { passive: true })
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const openPage = (nextPage) => {
    setPage(nextPage)
    setMenuOpen(false)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const scrollTo = (id) => {
    setPage('home')
    setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }), 0)
    setMenuOpen(false)
  }

  const catalogFilters = useMemo(() => ['ทั้งหมด', ...categoryNames], [categoryNames])

  const visibleProducts = products.filter((product) => {
    const matchesType = catalogFilter === 'ทั้งหมด' || product.category === catalogFilter
    const query = catalogSearch.trim().toLowerCase()
    return matchesType && (!query || `${product.name} ${product.category ?? ''} ${product.size ?? ''}`.toLowerCase().includes(query))
  })

  return (
    <div className="site-shell">
      <div className="announcement">
        <span><PawPrint size={15} /> อาหารสัตว์ดี มีคุณภาพ บริการด้วยใจ</span>
        <span className="announcement-perks">จัดส่งทั่วประเทศ · จำหน่ายปลีกและส่ง</span>
      </div>

      <header className={`navbar${navFixed ? ' is-fixed' : ''}`}>
        <button className="brand" onClick={() => openPage('home')} aria-label="กลับหน้าแรก">
          <img src="/assets/s-kij-logo.png" alt="โลโก้ ส.กิจการค้า" />
          <span><strong>ส.กิจการค้า</strong><small>ศูนย์รวมอาหารสัตว์คุณภาพ</small></span>
        </button>
        <nav className={menuOpen ? 'nav-links is-open' : 'nav-links'}>
          <button onClick={() => openPage('home')}>หน้าแรก</button>
          <button onClick={() => openPage('products')}>สินค้า <ChevronDown size={15} /></button>
          <button onClick={() => scrollTo('recommended')}>สินค้าแนะนำ</button>
          <button onClick={() => scrollTo('about')}>เกี่ยวกับเรา</button>
          <button onClick={() => scrollTo('contact')}>ติดต่อเรา</button>
        </nav>
        <div className="nav-actions">
          <button className="icon-button" onClick={() => setSearchOpen(!searchOpen)} aria-label="ค้นหา"><Search /></button>
          <button className="icon-button hide-mobile" aria-label="บัญชีผู้ใช้"><CircleUserRound /></button>
          <button className="bag-button" aria-label="ตะกร้าสินค้า"><ShoppingBag /><span>0</span></button>
          <button className="menu-button" onClick={() => setMenuOpen(!menuOpen)} aria-label="เปิดเมนู">{menuOpen ? <X /> : <Menu />}</button>
        </div>
        {searchOpen && <div className="search-panel"><Search size={19} /><input autoFocus placeholder="ค้นหาอาหารสัตว์หรืออุปกรณ์..." /></div>}
      </header>
      {navFixed && <div className="navbar-spacer" aria-hidden="true" />}

      {page === 'home' ? <main>
        <section className="hero-banner" id="home" aria-label="อาหารสัตว์ดี มีคุณภาพ เพื่อสัตว์เลี้ยงที่คุณรัก">
          <img src="/assets/banner-products-real-v4.png" alt="อาหารสัตว์ดี มีคุณภาพ เพื่อสัตว์เลี้ยงที่คุณรัก พร้อมอาหารสุนัขและอาหารแมว 7 รายการ" />
          <button className="banner-cta" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={18} /></button>
          <section className="services" aria-label="บริการของร้าน">
            {services.map(({ icon: Icon, title, text }) => <article key={title}><span><Icon /></span><div><h3>{title}</h3><p>{text}</p></div></article>)}
          </section>
        </section>

        <section className="section categories-section" id="categories">
          <div className="section-heading"><div><span className="kicker">เลือกง่าย ได้ของที่ใช่</span><h2>หมวดหมู่สินค้า</h2></div><button className="text-link" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={17} /></button></div>
          <div className="category-grid">
            {categoryCards.map(({ name, caption, icon: Icon, tint, image, position }) => <button className="category-card" key={name} onClick={() => openPage('products')} style={{ '--tint': tint }}><span className={`category-icon${image ? ' has-photo' : ''}`}>{image ? <img src={image} alt={name} style={{ objectPosition: position }} /> : <Icon />}</span><span><strong>{name}</strong><small>{caption}</small></span><ArrowRight className="card-arrow" size={18} /></button>)}
          </div>
        </section>

        <section className="section product-section" id="recommended">
          <div className="section-heading"><div><span className="kicker">เลือกโดยลูกค้าประจำ</span><h2>สินค้าแนะนำ</h2></div><div className="mini-tabs"><button className="active">สินค้ายอดนิยม</button><button>มาใหม่</button></div></div>
          <div className="product-grid">
            {products.slice(0, 6).map((product, i) => <article className="product-card" key={product.id}>
              <div className="product-picture" style={{ background: productCardColors[i % productCardColors.length] }}><ProductPicture product={product} /><button aria-label={`เพิ่ม ${product.name} ลงรายการ`}><HeartHandshake size={19} /></button></div>
              <div className="product-info"><small>{product.category}</small><h3>{product.name}</h3><p>{product.size}</p><button onClick={() => scrollTo('contact')}>สอบถามราคา <ArrowRight size={16} /></button></div>
            </article>)}
          </div>
          {!products.length && !loadError && <p className="product-loading-note">กำลังโหลดสินค้า...</p>}
          {loadError && <p className="product-loading-note">โหลดรายการสินค้าไม่สำเร็จ ลองรีเฟรชหน้าอีกครั้ง</p>}
        </section>

        <section className="sales-section" aria-label="บริการจำหน่ายปลีกและส่ง">
          <div className="sales-card sales-retail"><span className="sales-label">สำหรับเจ้าของสัตว์เลี้ยง</span><h2>ซื้อปลีก เลือกง่าย<br />มีของพร้อมส่ง</h2><p>เลือกสินค้ายอดนิยม หรือสอบถามอาหารที่เหมาะกับน้องได้โดยตรงกับร้าน</p><button className="primary-button" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={18} /></button></div>
          <div className="sales-card sales-wholesale"><span className="sales-label">สำหรับร้านค้าและฟาร์ม</span><h2>สั่งหลายถุง<br />สอบถามราคาส่ง</h2><p>แจ้งรายการสินค้าและจำนวนที่ต้องการ เพื่อให้ทางร้านช่วยเช็กราคาและค่าจัดส่ง</p><a className="white-button" href="tel:0956699178"><Phone size={18} />โทร 095-669-9178</a></div>
        </section>

        <section className="brands-section" aria-labelledby="brands-title">
          <div className="section-heading"><div><span className="kicker">แบรนด์ที่ลูกค้าคุ้นเคย</span><h2 id="brands-title">แบรนด์ที่มีจำหน่าย</h2></div><a className="text-link" href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer">ดูหน้าร้าน Shopee <ArrowRight size={17} /></a></div>
          <div className="brand-list">{brands.map((brand) => <span key={brand}>{brand}</span>)}</div>
        </section>

        <section className="reviews-section" aria-labelledby="reviews-title">
          <div><span className="kicker light">เช็กรีวิวและสินค้าจริง</span><h2 id="reviews-title">ดูความเคลื่อนไหวของร้าน<br />ก่อนตัดสินใจสั่งซื้อ</h2><p>ติดตามสินค้าเข้าใหม่ รีวิว และสอบถามรายละเอียดเพิ่มเติมผ่านช่องทางร้านโดยตรง</p></div>
          <div className="review-links"><a href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer"><MessageCircle size={22} /><span><strong>Facebook</strong><small>ส.กิจการค้า</small></span><ArrowRight size={18} /></a><a href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer"><ShoppingBag size={22} /><span><strong>Shopee</strong><small>ดูสินค้าและรีวิวจากผู้ซื้อ</small></span><ArrowRight size={18} /></a></div>
        </section>

        <section className="about-section" id="about">
          <div className="about-card">
            <div className="about-mark"><img src="/assets/s-kij-logo.png" alt="ตราร้าน ส.กิจการค้า" /></div>
            <div className="about-copy"><span className="kicker">รู้จัก ส.กิจการค้า</span><h2>เราเชื่อว่าอาหารที่ดี<br />คือจุดเริ่มต้นของสุขภาพที่ดี</h2><p>เราคัดสรรอาหารสัตว์หลากหลายประเภท เพื่อให้เจ้าของสัตว์เลี้ยงและเกษตรกรเลือกสินค้าที่เหมาะสมได้สะดวก พร้อมบริการแบบเป็นกันเองและจริงใจในทุกออเดอร์</p><div className="about-stats"><span><strong>30+</strong> กลุ่มสินค้า</span><span><strong>ปลีก–ส่ง</strong> รองรับทุกความต้องการ</span><span><strong>ทั่วไทย</strong> พร้อมจัดส่ง</span></div></div>
          </div>
        </section>

        <section className="contact-banner" id="contact">
          <div><span className="kicker light">ต้องการคำแนะนำ?</span><h2>ทักมาคุยกับเราได้เลย</h2><p>แจ้งชนิดสัตว์ อายุ และความต้องการ ทีมงานจะช่วยแนะนำสินค้าให้เหมาะสม</p><div className="contact-details"><a href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer"><MapPin size={17} />369 หมู่ 1 ต.ศรีสุทโธ อ.บ้านดุง จ.อุดรธานี 41190</a><a href="mailto:swisuttiya1@gmail.com"><Mail size={17} />swisuttiya1@gmail.com</a></div></div>
          <div className="contact-actions"><a className="white-button" href="tel:0956699178"><Phone size={18} />095-669-9178</a><a className="outline-button" href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer"><MessageCircle size={18} />Facebook</a><a className="shopee-button" href="https://shopee.co.th/shop/1789277286?uls_trackid=56hnr8le002p&utm_content=58CnmyeXriKScXfSwoPfG69XgsZ" target="_blank" rel="noreferrer"><ShoppingBag size={18} />ร้านใน Shopee</a></div>
        </section>

        <section className="store-location" aria-labelledby="store-location-title">
          <div className="location-copy"><span className="kicker">ที่ตั้งร้าน ส.กิจการค้า</span><h2 id="store-location-title">แวะมาหาเราได้ที่บ้านดุง</h2><p><MapPin size={19} />369 หมู่ 1 ต.ศรีสุทโธ อ.บ้านดุง จ.อุดรธานี 41190</p><a className="primary-button" href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer"><MapPin size={18} />เปิดใน Google Maps</a></div>
          <div className="map-frame"><iframe title="แผนที่ร้าน ส.กิจการค้า" src="https://www.google.com/maps?q=17.6976687,103.2576179&z=18&output=embed" loading="lazy" referrerPolicy="no-referrer-when-downgrade" allowFullScreen /></div>
        </section>
      </main> : <main className="catalog-page">
        <section className="catalog-hero">
          <button className="catalog-back" onClick={() => openPage('home')}>← กลับหน้าแรก</button>
          <span className="kicker">สินค้าคุณภาพจาก ส.กิจการค้า</span>
          <h1>สินค้าทั้งหมด</h1>
          <p>เลือกอาหารที่เหมาะกับน้องหมา น้องแมว และสัตว์เลี้ยงของคุณ หากต้องการราคาปลีกหรือราคาส่ง ติดต่อร้านได้ทันที</p>
          <a className="catalog-shopee" href="https://shopee.co.th/shop/1789277286?uls_trackid=56hnr8le002p&utm_content=58CnmyeXriKScXfSwoPfG69XgsZ" target="_blank" rel="noreferrer"><ShoppingBag size={19} />เลือกซื้อสินค้าบน Shopee <ArrowRight size={17} /></a>
        </section>
        <section className="catalog-content">
          <div className="catalog-toolbar">
            <div className="catalog-search"><Search size={20} /><input value={catalogSearch} onChange={(event) => setCatalogSearch(event.target.value)} placeholder="ค้นหาชื่อสินค้า แบรนด์ หรือขนาด..." /></div>
            <div className="catalog-filters" aria-label="กรองประเภทสินค้า">
              {catalogFilters.map((filter) => <button key={filter} className={catalogFilter === filter ? 'active' : ''} onClick={() => setCatalogFilter(filter)}>{filter}</button>)}
            </div>
          </div>
          <div className="catalog-summary"><strong>{visibleProducts.length} รายการ</strong><span>สอบถามราคาปลีก–ส่งได้ทุกสินค้า</span></div>
          {visibleProducts.length ? <div className="product-grid catalog-grid">
            {visibleProducts.map(product => <article className="product-card" key={product.id}>
              <div className="product-picture" style={{ background: productCardColors[product.id % productCardColors.length] }}>
                <span className={`product-badge${product.in_stock ? '' : ' out-of-stock'}`}>{product.in_stock ? 'พร้อมจำหน่าย' : 'หมดชั่วคราว'}</span>
                <ProductPicture product={product} />
                <button aria-label={`สนใจ ${product.name}`}><HeartHandshake size={19} /></button>
              </div>
              <div className="product-info"><small>{product.category}</small><h3>{product.name}</h3><p>{product.size}</p><a className="product-enquire" href="tel:0956699178">สอบถามราคา <ArrowRight size={16} /></a></div>
            </article>)}
          </div> : <div className="catalog-empty"><Search size={30} /><h3>{products.length ? 'ไม่พบสินค้าที่ค้นหา' : (loadError ? 'โหลดรายการสินค้าไม่สำเร็จ' : 'กำลังโหลดสินค้า...')}</h3><p>{products.length ? 'ลองเปลี่ยนคำค้นหาหรือเลือกหมวดหมู่อื่น' : (loadError ? 'ลองรีเฟรชหน้าอีกครั้ง' : '')}</p></div>}
        </section>
      </main>}

      <footer>
        <div className="footer-main"><div className="footer-brand"><img src="/assets/s-kij-logo.png" alt="โลโก้ ส.กิจการค้า" /><p>อาหารสัตว์ดี มีคุณภาพ<br />บริการด้วยใจ</p></div><div><h4>สินค้า</h4><a href="#categories">อาหารสัตว์เลี้ยง</a><a href="#categories">อาหารสัตว์ฟาร์ม</a><a href="#categories">อุปกรณ์เลี้ยงสัตว์</a><a href="https://shopee.co.th/shop/1789277286?uls_trackid=56hnr8le002p&utm_content=58CnmyeXriKScXfSwoPfG69XgsZ" target="_blank" rel="noreferrer">เลือกซื้อบน Shopee</a></div><div><h4>ช่วยเหลือ</h4><a href="#contact">ติดต่อเรา</a><a href="#about">เกี่ยวกับร้าน</a><a href="#recommended">สินค้าแนะนำ</a></div><div><h4>ติดต่อร้าน</h4><a href="tel:0956699178">095-669-9178</a><a href="mailto:swisuttiya1@gmail.com">swisuttiya1@gmail.com</a><a href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer">Facebook: ส.กิจการค้า</a><a href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer">369 หมู่ 1 ต.ศรีสุทโธ<br />อ.บ้านดุง จ.อุดรธานี 41190</a></div></div>
        <div className="footer-bottom"><span>© 2026 ส.กิจการค้า</span><span>เลขที่ใบอนุญาตขายอาหารสัตว์ 62410000300252</span></div>
      </footer>

      <nav className="floating-contact" aria-label="ช่องทางติดต่อด่วน">
        <a className="float-phone" href="tel:0956699178" aria-label="โทรหาร้าน"><Phone /></a>
        <a className="float-facebook" href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer" aria-label="Facebook ส.กิจการค้า"><MessageCircle /></a>
        <a className="float-shopee" href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer" aria-label="ร้านใน Shopee"><ShoppingBag /></a>
        <a className="float-map" href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer" aria-label="เปิดแผนที่ร้าน"><MapPin /></a>
      </nav>
    </div>
  )
}

export default App
