<?php

namespace App\Support;

use App\Enums\Permission;

/**
 * Sidebar navigation + page metadata, ported from NAV/TITLES/landingPage() in
 * Feed Stock Manager v2.dc.html (support.js lines 2206-2228, 2846-2853).
 */
class Nav
{
    /**
     * Ordered page definitions. Order matters: User::landingRoute() walks this
     * list and lands on the first page the user is allowed to see, exactly
     * like the prototype's landingPage() fallback order.
     *
     * @return array<int, array{key: string, route: string, group: string, label: string, sub: string, permission: ?Permission, d: string}>
     */
    public static function pages(): array
    {
        return [
            [
                'key' => 'dashboard',
                'route' => 'dashboard',
                'group' => 'ภาพรวม',
                'label' => 'แดชบอร์ด',
                'sub' => 'สรุปสต็อก ยอดขาย และงานที่ต้องทำวันนี้',
                'permission' => Permission::ViewReports,
                'd' => 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
            ],
            [
                'key' => 'movements',
                'route' => 'movements.index',
                'group' => 'คลังสินค้า',
                'label' => 'รับเข้า–เบิกออก',
                'sub' => 'เอกสารทั้งหมด พิมพ์ใบเสร็จและใบส่งของได้',
                'permission' => Permission::StockMovements,
                'd' => 'M7 3v18M7 3 3 7M7 3l4 4M17 21V3M17 21l4-4M17 21l-4-4',
            ],
            [
                'key' => 'products',
                'route' => 'products.index',
                'group' => 'คลังสินค้า',
                'label' => 'สินค้า / ราคา',
                'sub' => 'ประเภท · ราคาหน่วย · ราคาต่อหน่วย · ออนไลน์ พร้อมยอดคงเหลือ',
                'permission' => null,
                'd' => 'M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8',
            ],
            [
                'key' => 'alerts',
                'route' => 'alerts.index',
                'group' => 'คลังสินค้า',
                'label' => 'ใกล้หมด / สั่งซื้อ',
                'sub' => 'จุดสั่งซื้อขั้นต่ำและจำนวนที่ระบบแนะนำ',
                'permission' => null,
                'd' => 'M12 3l9 16H3zM12 9v5M12 17h.01',
            ],
            [
                'key' => 'count',
                'route' => 'stock-count.index',
                'group' => 'คลังสินค้า',
                'label' => 'นับสต็อก',
                'sub' => 'เทียบยอดจริงกับระบบและเก็บประวัติทุกรอบ',
                'permission' => Permission::StockCount,
                'd' => 'M9 11l3 3 8-8M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9',
            ],
            [
                'key' => 'online',
                'route' => 'online.index',
                'group' => 'คลังสินค้า',
                'label' => 'ขายออนไลน์',
                'sub' => 'รายรับ-รายจ่ายจากออเดอร์ออนไลน์',
                'permission' => Permission::OnlineSales,
                'd' => 'M6 2h12l2 5H4zM5 7v13h14V7M9 11a3 3 0 0 0 6 0',
            ],
            [
                'key' => 'categories',
                'route' => 'categories.index',
                'group' => 'จัดการ',
                'label' => 'ประเภทสินค้า',
                'sub' => 'มูลค่าสต็อกและอัตรากำไรแยกตามประเภท',
                'permission' => null,
                'd' => 'M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
            ],
            [
                'key' => 'reports',
                'route' => 'reports.index',
                'group' => 'ภาพรวม',
                'label' => 'รายงาน / กำไร',
                'sub' => 'ยอดขาย กำไรขั้นต้น และสินค้าขายดี',
                'permission' => Permission::ViewReports,
                'd' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
            ],
            [
                'key' => 'users',
                'route' => 'users.index',
                'group' => 'จัดการ',
                'label' => 'ผู้ใช้ / สิทธิ์',
                'sub' => 'เจ้าของและพนักงาน กำหนดสิทธิ์รายคน',
                'permission' => Permission::ManageUsers,
                'd' => 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8M20 20v-2a4 4 0 0 0-3-3.8',
            ],
        ];
    }

    /** Groups in display order, matching prototype's navGroups(). */
    public static function groups(): array
    {
        return ['ภาพรวม', 'คลังสินค้า', 'จัดการ'];
    }

    public static function find(string $key): ?array
    {
        foreach (self::pages() as $page) {
            if ($page['key'] === $key) {
                return $page;
            }
        }

        return null;
    }
}
