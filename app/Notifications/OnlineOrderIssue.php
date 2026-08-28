<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\OnlineOrder;
use Illuminate\Notifications\Notification;

class OnlineOrderIssue extends Notification
{
    public function __construct(protected OnlineOrder $order)
    {
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::OnlineOrderIssue;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->order->status === 'returned' ? 'ถูกคืนสินค้า' : 'จัดส่งไม่สำเร็จ';

        return [
            'icon' => 'cart',
            'title' => "ออเดอร์ {$this->order->order_no} {$label}",
            'body' => $this->order->item,
            'url' => route('online.index', ['order' => $this->order->id]),
        ];
    }
}
