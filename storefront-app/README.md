# หน้าเว็บโปรโมทร้าน (storefront-app)

เว็บ React (Vite) ที่เสิร์ฟที่ `/shop` — แยกโปรเจกต์จากแอป Laravel หลัก แต่เก็บโค้ดต้นทางไว้
ใน repo เดียวกันเพื่อให้ track การเปลี่ยนแปลงและ deploy คู่กันได้ง่าย

ดึงสินค้า/ประเภทสินค้าจริงจาก 2 endpoint ของแอป Laravel (ดู `routes/web.php`):
- `GET /shop/api/products`
- `GET /shop/api/categories`

## พัฒนาต่อ (เห็นการเปลี่ยนแปลงสด ๆ)

```
cd storefront-app
npm install   # ครั้งแรกเท่านั้น
npm run dev
```

ต้องรันแอป Laravel หลัก (`php artisan serve` หรือ `composer run dev`) คู่กันด้วย ไม่งั้น fetch
ข้อมูลสินค้าจะไม่มีอะไรให้ดึง (dev server ของ Vite ไม่ได้ proxy ไปหา Laravel ให้อัตโนมัติ —
ถ้าจะรันคู่กันตอน dev ต้องเปิดคนละ terminal คนละพอร์ต แล้วเข้าเว็บผ่านพอร์ตของแอป Laravel
เพื่อให้ path แบบ `/shop/api/...` ชี้ไปที่ Laravel ถูกเครื่อง)

## Build + Deploy จริง (สำคัญ — ทำทุกครั้งที่แก้ไฟล์ในนี้)

หน้า `/shop` ของเว็บจริงไม่ได้รันจาก vite dev server — เป็นไฟล์ static ที่ build แล้วเอาไปวางไว้ที่
`public/assets/` และ `public/shop.html` ของแอป Laravel ตรงๆ **แก้โค้ดในนี้แล้วต้อง build +
ก็อปไฟล์ใหม่ทุกครั้ง ไม่งั้นเว็บจริงจะไม่เห็นการเปลี่ยนแปลง**

```
cd storefront-app
npm run build
```

จากนั้นก็อป:
- `storefront-app/dist/assets/*` → `public/assets/` (ทับของเดิม)
- `storefront-app/dist/index.html` → `public/shop.html`

แล้ว commit ทั้งคู่ (โค้ดต้นทางใน `storefront-app/` + ไฟล์ build ใน `public/`) พร้อมกัน แล้ว push —
Railway จะ deploy ให้อัตโนมัติ
