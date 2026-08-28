<?php

namespace App\Enums;

enum NotificationType: string
{
    case StockMovement = 'stock_movement';
    case LowStock = 'low_stock';
    case StockCount = 'stock_count';
    case OnlineOrderIssue = 'online_order_issue';

    public function label(): string
    {
        return match ($this) {
            self::StockMovement => 'มีการบันทึกรับเข้า-เบิกออก',
            self::LowStock => 'สินค้าใกล้หมด/หมดสต็อก',
            self::StockCount => 'ปิดรอบนับสต็อก',
            self::OnlineOrderIssue => 'ออเดอร์ออนไลน์มีปัญหา (จัดส่งไม่สำเร็จ/คืนสินค้า)',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
