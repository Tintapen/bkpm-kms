<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MailSetting;
use Illuminate\Support\Facades\Log;

class ArticleCreatedNotification extends Notification
{
    use Queueable;

    public $article;

    public function __construct($article)
    {
        $this->article = $article;
    }

    public function via($notifiable)
    {
        $channels = ['database', 'broadcast'];
        // Only send mail if user wants email notification
        if (isset($notifiable->emailnotif) && $notifiable->emailnotif === 'Y') {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase($notifiable)
    {
        return [
            'article_id' => $this->article->id,
            'title' => $this->article->title,
            'message' => 'Artikel baru diterbitkan!',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'article_id' => $this->article->id,
            'title' => $this->article->title,
            'message' => 'Artikel baru diterbitkan!',
        ]);
    }

    public function toMail($notifiable)
    {
        $mailSetting = MailSetting::first();

        if (!$mailSetting || empty($mailSetting->host) || empty($mailSetting->from_address)) {
            Log::error('[ArticleCreatedNotification] Gagal mengirim email: MailSetting belum dikonfigurasi.', [
                'mail_setting' => $mailSetting
            ]);
            return null;
        }

        try {
            return (new MailMessage)
                ->subject('Artikel Baru Diterbitkan')
                ->greeting('Halo ' . $notifiable->name . '!')
                ->line("Artikel baru telah diterbitkan: {$this->article->title}")
                ->action('Lihat Artikel', route('filament.admin.resources.articles.view', $this->article->id))
                ->line('Terima kasih telah menggunakan aplikasi kami!');
        } catch (\Throwable $e) {
            Log::error('[ArticleCreatedNotification] Exception saat kirim email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
