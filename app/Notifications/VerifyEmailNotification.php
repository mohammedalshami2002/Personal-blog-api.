<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد البريد الإلكتروني')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('لقد أنشأت حساب جديد في موقعنا، لتفعيل حسابك يرجى استخدام رمز التحقق التالي:')
            ->line('🔑 رمز التحقق الخاص بك هو: ' . $this->code )
            ->line('رمز التحقق صالح لمدة 10 دقائق فقط.')
            ->line('إذا لم تقم بإنشاء حساب، يرجى تجاهل هذا البريد.');
    }
}
