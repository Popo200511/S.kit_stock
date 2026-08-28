<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case StoreManager = 'store_manager';
    case WarehouseStaff = 'warehouse_staff';
    case SalesStaff = 'sales_staff';
    case Tester = 'tester';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'เจ้าของ',
            self::StoreManager => 'ผู้จัดการร้าน',
            self::WarehouseStaff => 'พนักงานคลัง',
            self::SalesStaff => 'พนักงานขาย',
            self::Tester => 'ผู้ทดสอบระบบ (Software Tester)',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Owner => 'เข้าถึงได้ทุกเมนู รวมถึงการจัดการผู้ใช้และกำหนดสิทธิ์',
            self::StoreManager => 'ดูรายงาน แก้ไขสินค้า และนับสต็อกได้ แต่จัดการผู้ใช้ไม่ได้',
            self::WarehouseStaff => 'รับเข้า-เบิกออก และนับสต็อกได้',
            self::SalesStaff => 'บันทึกเบิกออก/ขายหน้าร้านได้เท่านั้น',
            self::Tester => 'เข้าถึงเมนูทดสอบระบบได้เกือบทั้งหมด (เหมือนผู้จัดการร้าน) ยกเว้นจัดการผู้ใช้ เพื่อกันไม่ให้แก้ไขบัญชีจริงของผู้อื่นโดยไม่ตั้งใจ',
        };
    }

    /** @return array<string> default permission keys granted to a newly created user of this role */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Owner => [
                Permission::ViewReports->value,
                Permission::StockMovements->value,
                Permission::EditProducts->value,
                Permission::StockCount->value,
                Permission::OnlineSales->value,
                Permission::ManageUsers->value,
            ],
            self::StoreManager => [
                Permission::ViewReports->value,
                Permission::StockMovements->value,
                Permission::EditProducts->value,
                Permission::StockCount->value,
                Permission::OnlineSales->value,
            ],
            self::WarehouseStaff => [
                Permission::StockMovements->value,
                Permission::StockCount->value,
            ],
            self::SalesStaff => [
                Permission::StockMovements->value,
            ],
            self::Tester => [
                Permission::ViewReports->value,
                Permission::StockMovements->value,
                Permission::EditProducts->value,
                Permission::StockCount->value,
                Permission::OnlineSales->value,
            ],
        };
    }
}
