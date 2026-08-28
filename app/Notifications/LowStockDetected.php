<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Product;
use Illuminate\Notifications\Notification;

class LowStockDetected extends Notification
{
    public function __construct(protected Product $product)
    {
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::LowStock;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isOut = $this->product->stock <= 0;

        return [
            'icon' => 'warning',
            'title' => $this->product->name,
            'body' => $isOut
                ? 'สินค้าหมดสต็อกแล้ว'
                : "เหลือ {$this->product->stock} {$this->product->unit?->name} ใกล้ถึงจุดสั่งซื้อ",
            'url' => route('alerts.index', ['product' => $this->product->id]),
        ];
    }
}
