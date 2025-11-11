<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public $notifications;
    public $newArticlesCount = 0;
    public $openDropdown = false;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $user = Auth::user();
        $this->notifications = $user->unreadNotifications()->take(10)->get();
        $this->newArticlesCount = $this->notifications->count();
    }

    public function toggleDropdown()
    {
        $this->openDropdown = !$this->openDropdown;
        if ($this->openDropdown) {
            $this->loadNotifications();
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAsReadAndGo($notificationId, $articleId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->route('filament.admin.resources.articles.view', $articleId);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
