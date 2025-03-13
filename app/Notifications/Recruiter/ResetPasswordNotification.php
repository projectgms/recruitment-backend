<?php

namespace App\Notifications\Recruiter;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
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
        $frontendUrl = env('RECRUITER_FRONTEND_URL'); // e.g., https://gms-sdv-oem.vercel.app/auth/newpassword
        $encryptedEmail = encrypt($notifiable->getEmailForPasswordReset());

        // Build the URL with the encrypted & URL-encoded email
        $url = "{$frontendUrl}?token={$this->token}&email=" .$encryptedEmail;
        // Construct the reset link
      //  $url = "{$frontendUrl}?token={$this->token}&email=" . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('If you did not request a password reset, no further action is required.');
 
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
