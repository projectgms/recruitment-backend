<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegisterNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $email;
    public $password;

    public function __construct($email,$password)
    {
        $this->email = $email;
        $this->password=$password;
      
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $frontendUrl = env('SUPERADMIN_FRONTEND_URL'); 
        return (new MailMessage)
                    ->line('Thank you for the registration.')
                    ->line('Your Login Details Below,')
                    ->line('Email -'.$this->email)
                    ->line('Password -'.$this->password)
                 
                   // ->action('URL', url('/'))
                   ->action('URL', $frontendUrl)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
