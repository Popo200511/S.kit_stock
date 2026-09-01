<?php

return [
    // Storefront branding + contact channels — no cart/checkout, so "buying"
    // just means the customer taps one of these to reach a human.
    'name' => env('SHOP_NAME', 'ส.กิจการค้า'),
    'phone' => env('SHOP_PHONE'),
    'line_id' => env('SHOP_LINE_ID'),
    'line_url' => env('SHOP_LINE_URL'),

    // ลิงก์หน้าร้านบนแพลตฟอร์มอื่นๆ ที่ขายอยู่คู่กัน — ใส่เมื่อพร้อมค่อยโชว์ไอคอนที่หน้าร้าน
    // ไม่บังคับกรอก ถ้าไม่ตั้งค่า .env ตัวไหนไว้ ไอคอนช่องทางนั้นจะไม่ขึ้นเลย
    'shopee_url' => env('SHOP_SHOPEE_URL'),
    'facebook_url' => env('SHOP_FACEBOOK_URL'),
];
