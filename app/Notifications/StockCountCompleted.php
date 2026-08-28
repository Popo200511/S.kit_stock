<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Notifications\Notification;

class StockCountCompleted extends Notification
{
    public function __construct(protected StockCount $count, protected User $actor)
    {
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::StockCount;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'check',
            'title' => "ปิดรอบนับสต็อก {$this->count->count_no}",
            'body' => "โดย {$this->actor->name} · {$this->count->scope_label}",
            'url' => route('stock-count.index', ['count' => $this->count->id]),
        ];
    }
}
