# Deploy ขึ้น Railway (สำหรับดูตัวอย่าง/จำลอง)

โปรเจกต์นี้มี `railway.json` เตรียมไว้แล้ว (สั่ง build อัตโนมัติผ่าน Nixpacks, รัน migrate + สร้าง symlink storage ทุกครั้งที่ deploy) เหลือแค่ทำตามขั้นตอนนี้บนเว็บ Railway

## ขั้นตอน

1. เข้า [railway.app](https://railway.app) แล้วสมัคร/login ด้วยบัญชี GitHub
2. กด **New Project** → **Deploy from GitHub repo** → เลือก `S.kit_stock`
3. กด **New** อีกครั้งในโปรเจกต์เดียวกัน → **Database** → **Add MySQL** (Railway จะสร้างฐานข้อมูลแยกเป็นอีก service หนึ่งให้อัตโนมัติ)
4. ไปที่ service ของแอป (ไม่ใช่ตัว MySQL) → แท็บ **Variables** → เพิ่มตัวแปรตามนี้:

   ```
   APP_NAME=FeedStock
   APP_ENV=local
   APP_KEY=                      (ดูวิธีสร้างด้านล่าง)
   APP_DEBUG=false
   APP_URL=                      (ใส่หลังจากได้โดเมนจาก Railway แล้ว ดูขั้นตอน 6)

   APP_LOCALE=th
   APP_FALLBACK_LOCALE=en

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database

   SHOP_NAME=ส.กิจการค้า
   SHOP_PHONE=
   SHOP_LINE_ID=
   SHOP_LINE_URL=
   ```

   ค่า `${{MySQL.MYSQLHOST}}` แบบนี้พิมพ์ตรงๆ ได้เลย — Railway จะไปดึงค่าจริงจาก service MySQL ที่สร้างไว้ในขั้นตอน 3 ให้เอง (ต้องตั้งชื่อ service ว่า `MySQL` ตามที่ Railway ตั้งให้อัตโนมัติ ถ้าเปลี่ยนชื่อ service ต้องแก้ตรงนี้ให้ตรงด้วย)

   **หมายเหตุเรื่อง `APP_ENV=local`:** ตั้งเป็น `local` เพื่อให้ปุ่ม "บัญชีสาธิต" บนหน้า login โชว์ (เดโมกดกรอกอัตโนมัติได้เลย) — เช็กโค้ดแล้วค่านี้มีผลแค่จุดเดียวคือปุ่มเดโม ไม่กระทบพฤติกรรมอื่นของระบบ แต่**ตั้ง `APP_DEBUG=false` ควบคู่ไปด้วยเสมอ** กันไม่ให้หน้า error โชว์ stack trace/ข้อมูล internal ออกสู่สาธารณะ

5. สร้าง `APP_KEY` — เปิด terminal ในเครื่องตัวเอง (โฟลเดอร์โปรเจกต์) แล้วรัน:
   ```bash
   php artisan key:generate --show
   ```
   จะได้ค่าแบบ `base64:xxxxxxxx...` เอาไปวางในตัวแปร `APP_KEY` บน Railway

6. กด **Settings** ของ service แอป → **Networking** → **Generate Domain** จะได้โดเมนแบบ `xxxxx.up.railway.app` — เอา URL นั้น (มี `https://` นำหน้า) ไปใส่ในตัวแปร `APP_URL` ที่เว้นว่างไว้ในขั้นตอน 4 แล้ว save อีกที (จะ deploy ใหม่อัตโนมัติ)

7. รอ deploy เสร็จ (ดู log ในแท็บ **Deployments**) แล้วเข้าโดเมนที่ได้ → `/login` ทดสอบกดปุ่มบัญชีสาธิตได้เลย

## ข้อควรรู้

- **ยังไม่มีข้อมูลสินค้า/ผู้ใช้เดโมในฐานข้อมูลใหม่** — ต้องรัน seeder เองครั้งแรกผ่าน Railway's **Shell** (ในแท็บ service มีปุ่มเปิด shell ของ container ได้): `php artisan db:seed`
- **รูปสินค้าที่อัปโหลดจริงจะไม่ติดไปด้วย** เพราะไม่ได้ commit ขึ้น GitHub (เป็นไฟล์ผู้ใช้ ไม่ใช่โค้ด) และพื้นที่เก็บไฟล์ของ Railway เป็นแบบชั่วคราว (หายเมื่อ deploy ใหม่) — สินค้าที่ไม่มีรูปจะโชว์เป็นตัวอักษรย่อแทนอัตโนมัติอยู่แล้ว ถ้าต้องการให้รูปติดถาวรต้องเพิ่ม Railway Volume mount ที่ `storage/app/public` หรือย้ายไปใช้ S3-compatible storage แทน (แจ้งได้ถ้าต้องการให้ช่วยตั้งค่าส่วนนี้เพิ่ม)
- ฟีเจอร์ **เชื่อมต่อ Shopee** ต้องมี `SHOPEE_PARTNER_ID`/`SHOPEE_PARTNER_KEY` ของจริงถึงจะใช้ได้ ถ้าแค่ต้องการดูตัวอย่างทั่วไป ข้ามส่วนนี้ไปได้เลย
