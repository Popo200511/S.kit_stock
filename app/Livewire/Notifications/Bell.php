<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationType;
use Livewire\Component;

class Bell extends Component
{
    public string $tab = 'all';

    // Notification type preferences — a positive ("which do I want") list in the UI,
    // stored on the user as its opt-out complement (see saveSettings()).
    public bool $showSettings = false;

    public array $enabledTypes = [];

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return null;
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? null;

        return $url ? $this->redirect($url) : null;
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->delete();
    }

    public function deleteAllRead(): void
    {
        auth()->user()->readNotifications()->delete();
    }

    public function openSettings(): void
    {
        $muted = auth()->user()->notification_preferences ?? [];
        $this->enabledTypes = collect(NotificationType::values())->diff($muted)->values()->all();
        $this->showSettings = true;
    }

    public function closeSettings(): void
    {
        $this->showSettings = false;
    }

    public function saveSettings(): void
    {
        $muted = collect(NotificationType::values())->diff($this->enabledTypes)->values()->all();
        auth()->user()->update(['notification_preferences' => $muted]);
        $this->showSettings = false;
    }

    public function render()
    {
        $query = auth()->user()->notifications()->latest();

        if ($this->tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->limit(30)->get();

        $grouped = $notifications->groupBy(function ($n) {
            if ($n->created_at->isToday()) {
                return 'วันนี้';
            }
            if ($n->created_at->isYesterday()) {
                return 'เมื่อวานนี้';
            }

            return 'ก่อนหน้านี้';
        });

        return view('livewire.notifications.bell', [
            'grouped' => $grouped,
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'hasRead' => auth()->user()->readNotifications()->exists(),
            'notificationTypes' => NotificationType::cases(),
        ]);
    }
}
