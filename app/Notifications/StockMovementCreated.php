<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Notifications\Notification;

class StockMovementCreated extends Notification
{
    public function __construct(protected StockMovement $movement, protected User $actor)
    {
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::StockMovement;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isIn = $this->movement->type === 'in';

        return [
            'icon' => $isIn ? 'in' : 'out',
            'title' => "{$this->actor->name} บันทึกเอกสาร {$this->movement->doc_no}",
            'body' => ($isIn ? 'รับสินค้าเข้าคลัง' : 'เบิกสินค้าออกจากคลัง').' · '.number_format($this->movement->total, 2).' บาท',
            'url' => route('movements.index', ['doc' => $this->movement->id]),
        ];
    }
}
