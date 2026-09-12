import {
  ArrowLeft, ArrowRight, BadgeCheck, Bird, Bone, Cat, ChevronDown, CircleUserRound, ClipboardCheck,
  Dog, Fish, HeartHandshake, LockKeyhole, Mail, MapPin, Menu, MessageCircle, PackageCheck, RefreshCcw,
  Minus, PawPrint, Phone, Plus, Search, Share2, ShieldCheck, ShoppingBag, Truck, Wheat, X,
} from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import AOS from 'aos'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from './components/ui/dropdown-menu'
import { Sheet, SheetTrigger, SheetContent, SheetTitle, SheetDescription, SheetClose } from './components/ui/sheet'

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
  const [imageFailed, setImageFailed] = useState(false)

  if (product.photo_url && !imageFailed) {
    return <img src={product.photo_url} alt={product.name} loading="lazy" onError={() => setImageFailed(true)} />
  }

  return <span className="product-placeholder" aria-hidden="true">{product.name.charAt(0)}</span>
}

function ProductDetailPage({ productId, products, formatPrice, onBack, onAddToCart }) {
  const [product, setProduct] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [quantity, setQuantity] = useState(1)

  useEffect(() => {
    setLoading(true)
    setError(false)
    fetch(`/shop/api/products/${productId}`)
      .then((response) => {
        if (!response.ok) throw new Error('Product not found')
        return response.json()
      })
      .then((response) => setProduct(response.data))
      .catch(() => setError(true))
      .finally(() => setLoading(false))
  }, [productId])

  if (loading) return <main className="detail-page"><div className="detail-state"><PawPrint /><h1>กำลังโหลดสินค้า...</h1><p>รอสักครู่นะครับ</p></div></main>
  if (error || !product) return <main className="detail-page"><div className="detail-state"><Search /><h1>ไม่พบสินค้านี้</h1><p>สินค้าอาจถูกย้ายหรือหยุดจำหน่ายชั่วคราว</p><button className="primary-button" onClick={onBack}>กลับไปดูสินค้าทั้งหมด</button></div></main>

  const related = products.filter((item) => item.id !== product.id && item.category === product.category).slice(0, 4)

  return <main className="detail-page">
    <section className="detail-wrap">
      <nav className="detail-breadcrumb" aria-label="เส้นทางหน้าเว็บ">
        <button onClick={onBack}><ArrowLeft size={17} /> สินค้าทั้งหมด</button><span>/</span><span>{product.category || 'สินค้า'}</span><span>/</span><strong>{product.name}</strong>
      </nav>

      <section className="detail-layout">
        <div className="detail-media">
          <span className="detail-category-pill"><PawPrint size={16} /> {product.category || 'สินค้า'}</span>
          <div className="detail-picture"><ProductPicture product={product} /></div>
          <p><BadgeCheck size={18} /> รูปสินค้าจริงจากร้าน ส.กิจการค้า</p>
        </div>

        <div className="detail-panel">
          <span className="kicker">คัดสรรเพื่อสัตว์เลี้ยงที่คุณรัก</span>
          <h1>{product.name}</h1>
          {product.size && <p className="detail-size">ขนาด {product.size}</p>}
          <div className="detail-price-row"><strong>{formatPrice(product.price)}</strong><span className={product.in_stock ? 'in-stock' : 'out-stock'}>{product.in_stock ? 'พร้อมจำหน่าย' : 'หมดชั่วคราว'}</span></div>
          <div className="detail-notice"><HeartHandshake /><div><strong>สนใจสินค้านี้?</strong><p>โทรหรือทักหาร้านเพื่อเช็กราคา จำนวนคงเหลือ และค่าจัดส่งได้ทันที</p></div></div>
          <div className="detail-order-box">
            <div><strong>จำนวนที่ต้องการ</strong><small>เลือกจำนวนสินค้าที่จะเพิ่มลงตะกร้า</small></div>
            <div className="quantity-control"><button onClick={() => setQuantity((value) => Math.max(1, value - 1))} aria-label="ลดจำนวน"><Minus /></button><output>{quantity}</output><button onClick={() => setQuantity((value) => Math.min(999, value + 1))} aria-label="เพิ่มจำนวน"><Plus /></button></div>
          </div>
          <div className="detail-actions">
            <button className="add-cart-button" disabled={!product.in_stock} onClick={() => onAddToCart(product, quantity)}><ShoppingBag size={19} /> {product.in_stock ? 'เพิ่มลงตะกร้า' : 'สินค้าหมดชั่วคราว'}</button>
            <a className="primary-button" href="tel:0956699178"><Phone size={19} /> โทร 095-669-9178</a>
            <a className="detail-facebook" href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer"><MessageCircle size={19} /> ทัก Facebook</a>
            <a className="shopee-button" href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer"><ShoppingBag size={19} /> ดูร้านใน Shopee</a>
          </div>
          <div className="detail-description"><h2>รายละเอียดสินค้า</h2><p>{product.description || `${product.name}${product.size ? ` ขนาด ${product.size}` : ''} สินค้าคุณภาพจากร้าน ส.กิจการค้า สามารถสอบถามข้อมูลเพิ่มเติมและราคาส่งกับทางร้านได้โดยตรง`}</p><dl><div><dt>หมวดหมู่</dt><dd>{product.category || '-'}</dd></div><div><dt>ขนาด</dt><dd>{product.size || '-'}</dd></div><div><dt>สถานะ</dt><dd>{product.in_stock ? 'มีสินค้า' : 'หมดชั่วคราว'}</dd></div></dl></div>
        </div>
      </section>

      <section className="detail-trust" aria-label="บริการของร้าน">
        {services.slice(0, 4).map(({ icon: Icon, title, text }) => <article key={title}><Icon /><span><strong>{title}</strong><small>{text}</small></span></article>)}
      </section>

      {related.length > 0 && <section className="detail-related"><div className="section-heading"><div><span className="kicker">เลือกดูเพิ่มเติม</span><h2>สินค้าในหมวดเดียวกัน</h2></div><button className="text-link" onClick={onBack}>ดูทั้งหมด <ArrowRight size={17} /></button></div><div className="product-grid">{related.map((item, index) => <article className="product-card" key={item.id}><div className="product-picture" style={{ background: productCardColors[index % productCardColors.length] }}><a className="product-photo-link" href={`/shop/product/${item.id}`}><ProductPicture product={item} /></a></div><div className="product-info"><small>{item.category}</small><h3><a className="product-name-link" href={`/shop/product/${item.id}`}>{item.name}</a></h3><p>{item.size}</p><strong className="product-price">{formatPrice(item.price)}</strong></div></article>)}</div></section>}
    </section>
  </main>
}

function App() {
  const productRoute = window.location.pathname.match(/^\/shop\/product\/(\d+)\/?$/)
  const [menuOpen, setMenuOpen] = useState(false)
  const [searchOpen, setSearchOpen] = useState(false)
  const [categoryDropdownOpen, setCategoryDropdownOpen] = useState(false)
  const [contactOpen, setContactOpen] = useState(false)
  const [navFixed, setNavFixed] = useState(false)
  const [page, setPage] = useState(productRoute ? 'detail' : 'home')
  const [catalogFilter, setCatalogFilter] = useState('ทั้งหมด')
  const [catalogSearch, setCatalogSearch] = useState('')
  const catalogSort = 'name-asc'
  const [catalogPage, setCatalogPage] = useState(1)
  const [cartOpen, setCartOpen] = useState(false)
  const [cartCopied, setCartCopied] = useState(false)
  const [cart, setCart] = useState(() => {
    try { return JSON.parse(localStorage.getItem('s-kij-cart')) ?? [] } catch { return [] }
  })

  // สินค้า + ประเภทสินค้าจริงจากระบบจัดการสต็อก — ดึงครั้งเดียวตอนเปิดเว็บ (จำนวนสินค้า
  // ไม่ได้เยอะมาก กรอง/ค้นหาฝั่ง browser เอาก็พอ ไม่ต้องยิง request ใหม่ทุกครั้งที่พิมพ์ค้นหา)
  const [products, setProducts] = useState([])
  const [categoryNames, setCategoryNames] = useState([])
  const [loadError, setLoadError] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  const loadCatalog = () => {
    setIsLoading(true)
    setLoadError(false)
    Promise.all([
      fetch('/shop/api/products').then((r) => r.json()),
      fetch('/shop/api/categories').then((r) => r.json()),
    ])
      .then(([productsRes, categoriesRes]) => {
        setProducts(productsRes.data ?? [])
        setCategoryNames((categoriesRes.data ?? []).map((c) => c.name))
      })
      .catch(() => setLoadError(true))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => { loadCatalog() }, [])
  useEffect(() => { try { localStorage.setItem('s-kij-cart', JSON.stringify(cart)) } catch {} }, [cart])
  useEffect(() => {
    const dismiss = (event) => {
      if (event.type === 'keydown' && event.key !== 'Escape') return
      if (event.type === 'keydown') { setMenuOpen(false); setSearchOpen(false); setContactOpen(false) }
    }
    document.addEventListener('keydown', dismiss)
    return () => document.removeEventListener('keydown', dismiss)
  }, [])

   useEffect(() => {
    const handleScroll = () => setNavFixed(window.scrollY > 42)
    handleScroll()
    window.addEventListener('scroll', handleScroll, { passive: true })
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const openPage = (nextPage) => {
    setPage(nextPage)
    window.history.pushState({}, '', '/shop')
    setMenuOpen(false)
    setCategoryDropdownOpen(false)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const openCategory = (category = 'ทั้งหมด') => {
    setCatalogFilter(category)
    setPage('products')
    window.history.pushState({}, '', '/shop')
    setMenuOpen(false)
    setCategoryDropdownOpen(false)
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

  const sortedProducts = useMemo(() => [...visibleProducts].sort((a, b) => {
    if (catalogSort === 'price-asc') return Number(a.price) - Number(b.price)
    if (catalogSort === 'price-desc') return Number(b.price) - Number(a.price)
    if (catalogSort === 'stock') return Number(b.in_stock) - Number(a.in_stock) || a.name.localeCompare(b.name, 'th')
    if (catalogSort === 'name-desc') return b.name.localeCompare(a.name, 'th')
    return a.name.localeCompare(b.name, 'th')
  }), [visibleProducts, catalogSort])

  const pageSize = 12
  const totalPages = Math.max(1, Math.ceil(sortedProducts.length / pageSize))
  const paginatedProducts = sortedProducts.slice((catalogPage - 1) * pageSize, catalogPage * pageSize)

  useEffect(() => { setCatalogPage(1) }, [catalogFilter, catalogSearch, catalogSort])
  useEffect(() => {
    const frame = requestAnimationFrame(() => AOS.refreshHard())
    return () => cancelAnimationFrame(frame)
  }, [page, products, catalogFilter, catalogSearch, catalogSort, catalogPage])

  const formatPrice = (price) => Number(price) > 0
    ? `${new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(price)} บาท`
    : 'สอบถามราคา'

  const cartCount = cart.reduce((total, item) => total + item.quantity, 0)
  const cartTotal = cart.reduce((total, item) => total + (Number(item.price) || 0) * item.quantity, 0)
  const addToCart = (product, quantity = 1) => {
    setCart((current) => {
      const found = current.find((item) => item.id === product.id)
      if (found) return current.map((item) => item.id === product.id ? { ...item, quantity: Math.min(999, item.quantity + quantity) } : item)
      return [...current, { id: product.id, name: product.name, size: product.size, price: product.price, photo_url: product.photo_url, quantity }]
    })
    setCartOpen(true)
  }
  const changeCartQuantity = (id, amount) => setCart((current) => current.map((item) => item.id === id ? { ...item, quantity: Math.max(1, item.quantity + amount) } : item))
  const removeFromCart = (id) => setCart((current) => current.filter((item) => item.id !== id))
  const cartMessage = `สอบถามสินค้าจากร้าน ส.กิจการค้า\n${cart.map((item, index) => `${index + 1}. ${item.name}${item.size ? ` (${item.size})` : ''} จำนวน ${item.quantity} — ${formatPrice((Number(item.price) || 0) * item.quantity)}`).join('\n')}\n${cartTotal > 0 ? `รวมราคาสินค้าโดยประมาณ: ${formatPrice(cartTotal)}\n` : ''}กรุณาตรวจสอบสินค้า ราคา และค่าจัดส่งให้ด้วยครับ`
  const copyCart = async () => {
    await navigator.clipboard.writeText(cartMessage)
    setCartCopied(true)
    window.setTimeout(() => setCartCopied(false), 2200)
  }
  const shareCart = async () => {
    if (navigator.share) await navigator.share({ title: 'รายการสินค้าที่สนใจ', text: cartMessage })
    else await copyCart()
  }

  return (
    <Sheet open={cartOpen} onOpenChange={setCartOpen}><div className="site-shell">
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
          <button className={`nav-home-link${page === 'home' ? ' active' : ''}`} onClick={() => openPage('home')}>หน้าแรก</button>
          <div className={`nav-product-menu${categoryDropdownOpen ? ' is-open' : ''}`}>
            <DropdownMenu open={categoryDropdownOpen} onOpenChange={setCategoryDropdownOpen} modal={false}>
              <DropdownMenuTrigger asChild><button className={`nav-product-trigger${page !== 'home' ? ' active' : ''}`}>สินค้า <ChevronDown size={15} /></button></DropdownMenuTrigger>
              <DropdownMenuContent className="shop-category-menu" align="start" sideOffset={12} collisionPadding={16}>
                <DropdownMenuItem onSelect={() => openCategory('ทั้งหมด')}><ShoppingBag /><span><strong>สินค้าทั้งหมด</strong><small>ดูสินค้าทุกหมวดหมู่</small></span></DropdownMenuItem>
                {categoryNames.map((category) => <DropdownMenuItem key={category} onSelect={() => openCategory(category)}><PawPrint /><span><strong>{category}</strong><small>เลือกดูสินค้าในหมวดนี้</small></span></DropdownMenuItem>)}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          <button onClick={() => scrollTo('recommended')}>สินค้าแนะนำ</button>
          <button onClick={() => scrollTo('about')}>เกี่ยวกับเรา</button>
          <button onClick={() => scrollTo('contact')}>ติดต่อเรา</button>
        </nav>
        <div className="nav-actions">
          <button className="icon-button" onClick={() => setSearchOpen(!searchOpen)} aria-label="ค้นหา"><Search /></button>
          <button className="icon-button hide-mobile" aria-label="บัญชีผู้ใช้"><CircleUserRound /></button>
          <SheetTrigger asChild><button className="bag-button" aria-label={`ตะกร้าสินค้า ${cartCount} รายการ`}><ShoppingBag /><span>{cartCount}</span></button></SheetTrigger>
          <button className="menu-button" onClick={() => setMenuOpen(!menuOpen)} aria-label="เปิดเมนู">{menuOpen ? <X /> : <Menu />}</button>
        </div>
        {searchOpen && <form className="search-panel" onSubmit={(event) => { event.preventDefault(); setCatalogFilter('ทั้งหมด'); openPage('products'); setSearchOpen(false) }}><Search size={19} /><input autoFocus aria-label="ค้นหาสินค้า" value={catalogSearch} onChange={(event) => setCatalogSearch(event.target.value)} placeholder="ค้นหาอาหารสัตว์หรืออุปกรณ์..." /><button type="submit">ค้นหา</button></form>}
      </header>
      {navFixed && <div className="navbar-spacer" aria-hidden="true" />}

      {page === 'detail' ? <ProductDetailPage productId={productRoute?.[1]} products={products} formatPrice={formatPrice} onBack={() => openPage('products')} onAddToCart={addToCart} /> : page === 'home' ? <main>
        <section className="hero-banner" id="home" aria-label="อาหารสัตว์ดี มีคุณภาพ เพื่อสัตว์เลี้ยงที่คุณรัก">
          <img src="/assets/banner-products-real-v4.png" alt="อาหารสัตว์ดี มีคุณภาพ เพื่อสัตว์เลี้ยงที่คุณรัก พร้อมอาหารสุนัขและอาหารแมว 7 รายการ" />
          <button className="banner-cta" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={18} /></button>
          <section className="services" aria-label="บริการของร้าน">
            {services.map(({ icon: Icon, title, text }) => <article key={title}><span><Icon /></span><div><h3>{title}</h3><p>{text}</p></div></article>)}
          </section>
        </section>

        <section className="section categories-section" id="categories" data-aos="fade-up">
          <div className="section-heading"><div><span className="kicker">เลือกง่าย ได้ของที่ใช่</span><h2>หมวดหมู่สินค้า</h2></div><button className="text-link" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={17} /></button></div>
          <div className="category-grid">
            {categoryCards.map(({ name, caption, icon: Icon, tint, image, position }) => <button className="category-card" key={name} onClick={() => openPage('products')} style={{ '--tint': tint }}><span className={`category-icon${image ? ' has-photo' : ''}`}>{image ? <img src={image} alt={name} style={{ objectPosition: position }} /> : <Icon />}</span><span><strong>{name}</strong><small>{caption}</small></span><ArrowRight className="card-arrow" size={18} /></button>)}
          </div>
        </section>

        <section className="section product-section" id="recommended" data-aos="fade-up">
          <div className="section-heading"><div><span className="kicker">เลือกโดยลูกค้าประจำ</span><h2>สินค้าแนะนำ</h2></div><div className="mini-tabs"><button className="active">สินค้ายอดนิยม</button><button>มาใหม่</button></div></div>
          <div className="product-grid">
            {products.slice(0, 6).map((product, i) => <article className="product-card" key={product.id}>
              <div className="product-picture" style={{ background: productCardColors[i % productCardColors.length] }}><a className="product-photo-link" href={`/shop/product/${product.id}`} aria-label={`ดูรายละเอียด ${product.name}`}><ProductPicture product={product} /></a><a className="product-quick-link" href={`/shop/product/${product.id}`} aria-label={`ดู ${product.name}`}><HeartHandshake size={19} /></a></div>
              <div className="product-info"><small>{product.category}</small><h3><a className="product-name-link" href={`/shop/product/${product.id}`}>{product.name}</a></h3><p>{product.size}</p><a className="product-detail" href={`/shop/product/${product.id}`}>ดูรายละเอียด <ArrowRight size={16} /></a></div>
            </article>)}
          </div>
          {!products.length && !loadError && <p className="product-loading-note">กำลังโหลดสินค้า...</p>}
          {loadError && <p className="product-loading-note">โหลดรายการสินค้าไม่สำเร็จ ลองรีเฟรชหน้าอีกครั้ง</p>}
          <div className="recommended-actions"><button className="primary-button" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={18} /></button></div>
        </section>

        <section className="sales-section" aria-label="บริการจำหน่ายปลีกและส่ง" data-aos="fade-up">
          <div className="sales-card sales-retail"><span className="sales-label">สำหรับเจ้าของสัตว์เลี้ยง</span><h2>ซื้อปลีก เลือกง่าย<br />มีของพร้อมส่ง</h2><p>เลือกสินค้ายอดนิยม หรือสอบถามอาหารที่เหมาะกับน้องได้โดยตรงกับร้าน</p><button className="primary-button" onClick={() => openPage('products')}>ดูสินค้าทั้งหมด <ArrowRight size={18} /></button></div>
          <div className="sales-card sales-wholesale"><span className="sales-label">สำหรับร้านค้าและฟาร์ม</span><h2>สั่งหลายถุง<br />สอบถามราคาส่ง</h2><p>แจ้งรายการสินค้าและจำนวนที่ต้องการ เพื่อให้ทางร้านช่วยเช็กราคาและค่าจัดส่ง</p><a className="white-button" href="tel:0956699178"><Phone size={18} />โทร 095-669-9178</a></div>
        </section>

        <section className="brands-section" aria-labelledby="brands-title" data-aos="fade-up">
          <div className="section-heading"><div><span className="kicker">แบรนด์ที่ลูกค้าคุ้นเคย</span><h2 id="brands-title">แบรนด์ที่มีจำหน่าย</h2></div><a className="text-link" href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer">ดูหน้าร้าน Shopee <ArrowRight size={17} /></a></div>
          <div className="brand-list">{brands.map((brand) => <span key={brand}>{brand}</span>)}</div>
        </section>

        <section className="reviews-section" aria-labelledby="reviews-title" data-aos="fade-up">
          <div><span className="kicker light">เช็กรีวิวและสินค้าจริง</span><h2 id="reviews-title">ดูความเคลื่อนไหวของร้าน<br />ก่อนตัดสินใจสั่งซื้อ</h2><p>ติดตามสินค้าเข้าใหม่ รีวิว และสอบถามรายละเอียดเพิ่มเติมผ่านช่องทางร้านโดยตรง</p></div>
          <div className="review-links"><a href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer"><MessageCircle size={22} /><span><strong>Facebook</strong><small>ส.กิจการค้า</small></span><ArrowRight size={18} /></a><a href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer"><ShoppingBag size={22} /><span><strong>Shopee</strong><small>ดูสินค้าและรีวิวจากผู้ซื้อ</small></span><ArrowRight size={18} /></a></div>
        </section>

        <section className="about-section" id="about" data-aos="fade-up">
          <div className="about-card">
            <div className="about-mark"><img src="/assets/s-kij-logo.png" alt="ตราร้าน ส.กิจการค้า" /></div>
            <div className="about-copy"><span className="kicker">รู้จัก ส.กิจการค้า</span><h2>เราเชื่อว่าอาหารที่ดี<br />คือจุดเริ่มต้นของสุขภาพที่ดี</h2><p>เราคัดสรรอาหารสัตว์หลากหลายประเภท เพื่อให้เจ้าของสัตว์เลี้ยงและเกษตรกรเลือกสินค้าที่เหมาะสมได้สะดวก พร้อมบริการแบบเป็นกันเองและจริงใจในทุกออเดอร์</p><div className="about-stats"><span><strong>30+</strong> กลุ่มสินค้า</span><span><strong>ปลีก–ส่ง</strong> รองรับทุกความต้องการ</span><span><strong>ทั่วไทย</strong> พร้อมจัดส่ง</span></div></div>
          </div>
        </section>

        <section className="contact-banner" id="contact" data-aos="fade-up">
          <div><span className="kicker light">ต้องการคำแนะนำ?</span><h2>ทักมาคุยกับเราได้เลย</h2><p>แจ้งชนิดสัตว์ อายุ และความต้องการ ทีมงานจะช่วยแนะนำสินค้าให้เหมาะสม</p><div className="contact-details"><a href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer"><MapPin size={17} />369 หมู่ 1 ต.ศรีสุทโธ อ.บ้านดุง จ.อุดรธานี 41190</a><a href="mailto:swisuttiya1@gmail.com"><Mail size={17} />swisuttiya1@gmail.com</a></div></div>
          <div className="contact-actions"><a className="white-button" href="tel:0956699178"><Phone size={18} />095-669-9178</a><a className="outline-button" href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer"><MessageCircle size={18} />Facebook</a><a className="shopee-button" href="https://shopee.co.th/shop/1789277286?uls_trackid=56hnr8le002p&utm_content=58CnmyeXriKScXfSwoPfG69XgsZ" target="_blank" rel="noreferrer"><ShoppingBag size={18} />ร้านใน Shopee</a></div>
        </section>

        <section className="store-location" aria-labelledby="store-location-title" data-aos="fade-up">
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
          <div className="catalog-toolbar catalog-toolbar-clean">
            <label className="catalog-search-field"><span>ค้นหาสินค้า</span><div className="catalog-search"><Search size={19} /><input value={catalogSearch} onChange={(event) => setCatalogSearch(event.target.value)} placeholder="ชื่อสินค้า แบรนด์ หรือขนาด..." /></div></label>
            <label className="catalog-category-field"><span>หมวดหมู่สินค้า</span><select value={catalogFilter} onChange={(event) => setCatalogFilter(event.target.value)}>{catalogFilters.map((filter) => <option key={filter} value={filter}>{filter === 'ทั้งหมด' ? 'ทุกหมวดหมู่' : filter}</option>)}</select></label>
          </div>
          {(catalogFilter !== 'ทั้งหมด' || catalogSearch.trim()) && <div className="catalog-active-filters"><span>กำลังแสดง: {catalogFilter === 'ทั้งหมด' ? 'ทุกหมวดหมู่' : catalogFilter}{catalogSearch.trim() && ` · “${catalogSearch.trim()}”`}</span><button onClick={() => { setCatalogFilter('ทั้งหมด'); setCatalogSearch('') }}><X size={14} />ล้างตัวกรอง</button></div>}
          <div className="catalog-summary"><strong>{sortedProducts.length} รายการ</strong><span>{totalPages > 1 ? `หน้า ${catalogPage} จาก ${totalPages}` : 'สอบถามราคาปลีก–ส่งได้ทุกสินค้า'}</span></div>
          {paginatedProducts.length ? <><div className="product-grid catalog-grid">
            {paginatedProducts.map(product => <article className="product-card" key={product.id}>
              <div className="product-picture" style={{ background: productCardColors[product.id % productCardColors.length] }}>
                <span className={`product-badge${product.in_stock ? '' : ' out-of-stock'}`}>{product.in_stock ? 'พร้อมจำหน่าย' : 'หมดชั่วคราว'}</span>
                <a className="product-photo-link" href={`/shop/product/${product.id}`} aria-label={`ดูรายละเอียด ${product.name}`}><ProductPicture product={product} /></a>
                <a className="product-quick-link" href={`/shop/product/${product.id}`} aria-label={`ดู ${product.name}`}><HeartHandshake size={19} /></a>
              </div>
              <div className="product-info"><small>{product.category}</small><h3><a className="product-name-link" href={`/shop/product/${product.id}`}>{product.name}</a></h3><p>{product.size}</p><strong className="product-price">{formatPrice(product.price)}</strong><div className="product-actions"><button className="product-cart" disabled={!product.in_stock} onClick={() => addToCart(product)}><ShoppingBag size={15} />{product.in_stock ? 'ใส่ตะกร้า' : 'สินค้าหมด'}</button><a className="product-detail" href={`/shop/product/${product.id}`}>ดูรายละเอียด <ArrowRight size={16} /></a><a className="product-enquire" href="tel:0956699178">โทรสอบถาม</a></div></div>
            </article>)}
          </div>{totalPages > 1 && <nav className="catalog-pagination" aria-label="เปลี่ยนหน้าสินค้า"><button disabled={catalogPage === 1} onClick={() => setCatalogPage((pageNumber) => pageNumber - 1)}><ArrowLeft size={17} />ก่อนหน้า</button><div>{Array.from({ length: totalPages }, (_, index) => index + 1).map((pageNumber) => <button key={pageNumber} className={catalogPage === pageNumber ? 'active' : ''} onClick={() => setCatalogPage(pageNumber)}>{pageNumber}</button>)}</div><button disabled={catalogPage === totalPages} onClick={() => setCatalogPage((pageNumber) => pageNumber + 1)}>ถัดไป<ArrowRight size={17} /></button></nav>}</> : <div className="catalog-empty"><Search size={30} /><h3>{isLoading ? 'กำลังโหลดสินค้า...' : (loadError ? 'โหลดรายการสินค้าไม่สำเร็จ' : 'ไม่พบสินค้าที่ค้นหา')}</h3><p>{isLoading ? 'กรุณารอสักครู่' : (loadError ? 'ตรวจสอบการเชื่อมต่อแล้วลองใหม่อีกครั้ง' : 'ลองเปลี่ยนคำค้นหาหรือเลือกหมวดหมู่อื่น')}</p>{loadError && <button className="primary-button" onClick={loadCatalog}>ลองโหลดใหม่</button>}</div>}
        </section>
      </main>}

      <footer>
        <div className="footer-main"><div className="footer-brand"><img src="/assets/s-kij-logo.png" alt="โลโก้ ส.กิจการค้า" /><p>อาหารสัตว์ดี มีคุณภาพ<br />บริการด้วยใจ</p></div><div><h4>สินค้า</h4><a href="#categories">อาหารสัตว์เลี้ยง</a><a href="#categories">อาหารสัตว์ฟาร์ม</a><a href="#categories">อุปกรณ์เลี้ยงสัตว์</a><a href="https://shopee.co.th/shop/1789277286?uls_trackid=56hnr8le002p&utm_content=58CnmyeXriKScXfSwoPfG69XgsZ" target="_blank" rel="noreferrer">เลือกซื้อบน Shopee</a></div><div><h4>ช่วยเหลือ</h4><a href="#contact">ติดต่อเรา</a><a href="#about">เกี่ยวกับร้าน</a><a href="#recommended">สินค้าแนะนำ</a></div><div><h4>ติดต่อร้าน</h4><a href="tel:0956699178">095-669-9178</a><a href="mailto:swisuttiya1@gmail.com">swisuttiya1@gmail.com</a><a href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer">Facebook: ส.กิจการค้า</a><a href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer">369 หมู่ 1 ต.ศรีสุทโธ<br />อ.บ้านดุง จ.อุดรธานี 41190</a></div></div>
        <div className="footer-bottom"><span>© 2026 ส.กิจการค้า</span><span>เลขที่ใบอนุญาตขายอาหารสัตว์ 62410000300252</span></div>
      </footer>

      <SheetContent className="cart-drawer shop-cart-sheet" showCloseButton={false}>
          <header><div><span className="kicker">รายการที่สนใจ</span><SheetTitle>ตะกร้าสินค้า <small>{cartCount} ชิ้น</small></SheetTitle><SheetDescription className="sr-only">ตรวจสอบรายการและจำนวนสินค้าก่อนสอบถามร้าน</SheetDescription></div><SheetClose asChild><button aria-label="ปิดตะกร้า"><X /></button></SheetClose></header>
          <div className="cart-items">
            {cart.length ? cart.map((item) => <article className="cart-item" key={item.id}>
              <a className="cart-thumb" href={`/shop/product/${item.id}`}><ProductPicture product={item} /></a>
              <div className="cart-item-info"><h3><a href={`/shop/product/${item.id}`}>{item.name}</a></h3><small>{item.size || 'ไม่ระบุขนาด'}</small><strong>{formatPrice(item.price)}</strong><div className="cart-item-controls"><div className="quantity-control"><button onClick={() => changeCartQuantity(item.id, -1)} aria-label="ลดจำนวน"><Minus /></button><output>{item.quantity}</output><button onClick={() => changeCartQuantity(item.id, 1)} aria-label="เพิ่มจำนวน"><Plus /></button></div><button className="cart-remove" onClick={() => removeFromCart(item.id)}>ลบ</button></div></div>
            </article>) : <div className="cart-empty"><ShoppingBag /><h3>ตะกร้ายังว่างอยู่</h3><p>เลือกสินค้าที่สนใจ แล้วส่งรายการให้ร้านเช็กราคาได้เลย</p><button className="primary-button" onClick={() => { setCartOpen(false); openPage('products') }}>เลือกดูสินค้า</button></div>}
          </div>
          {cart.length > 0 && <div className="cart-summary"><div><span>รวมโดยประมาณ</span><strong>{cartTotal > 0 ? formatPrice(cartTotal) : 'สอบถามราคา'}</strong></div><small>ราคานี้ยังไม่รวมค่าจัดส่ง กรุณายืนยันกับทางร้านอีกครั้ง</small><button className="cart-primary" onClick={copyCart}><ClipboardCheck /> {cartCopied ? 'คัดลอกรายการแล้ว' : 'คัดลอกรายการสอบถาม'}</button><button className="cart-secondary" onClick={shareCart}><Share2 /> แชร์รายการ</button></div>}
      </SheetContent>

      <nav className={`floating-contact${contactOpen ? ' expanded' : ''}`} aria-label="ช่องทางติดต่อด่วน">
        <button className="contact-toggle" aria-expanded={contactOpen} onClick={() => setContactOpen((open) => !open)}>{contactOpen ? <X /> : <MessageCircle />} ติดต่อร้าน</button>
        <a className="float-phone" href="tel:0956699178" aria-label="โทรหาร้าน"><Phone /></a>
        <a className="float-facebook" href="https://www.facebook.com/search/top?q=ส.กิจการค้า" target="_blank" rel="noreferrer" aria-label="Facebook ส.กิจการค้า"><MessageCircle /></a>
        <a className="float-shopee" href="https://shopee.co.th/shop/1789277286" target="_blank" rel="noreferrer" aria-label="ร้านใน Shopee"><ShoppingBag /></a>
        <a className="float-map" href="https://maps.app.goo.gl/YLaEaMjqeYFKG2uP8" target="_blank" rel="noreferrer" aria-label="เปิดแผนที่ร้าน"><MapPin /></a>
      </nav>
    </div></Sheet>
  )
}

export default App
