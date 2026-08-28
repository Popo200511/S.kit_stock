<?php

namespace App\Enums;

enum Permission: string
{
    case ViewReports = 'view_reports';
    case StockMovements = 'stock_movements';
    case EditProducts = 'edit_products';
    case StockCount = 'stock_count';
    case OnlineSales = 'online_sales';
    case ManageUsers = 'manage_users';

    public function label(): string
    {
        return match ($this) {
            self::ViewReports => 'ดูรายงาน',
            self::StockMovements => 'รับเข้า-เบิกออก',
            self::EditProducts => 'แก้ไขสินค้า',
            self::StockCount => 'นับสต็อก',
            self::OnlineSales => 'ขายออนไลน์',
            self::ManageUsers => 'จัดการผู้ใช้',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
