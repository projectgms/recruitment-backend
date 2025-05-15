<?php

namespace App\Notifications\JobSeeker;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Jobs;
use Twilio\Rest\Client;

class UpdateJobApplication extends Notification
{
    use Queueable;
  
  protected $candidate_name;
  protected $job_title;
  protected $candidate_email;
  protected $application_id;
  protected $dashboard_link;
  protected $recruiter_mobile;

  public function __construct($candidate_name, $job_title, $candidate_email,$recruiter_mobile)
  {
      $this->candidate_name = $candidate_name;
      $this->job_title = $job_title;
      $this->candidate_email = $candidate_email;
      $this->recruiter_mobile=$recruiter_mobile;
  }

  /**
   * Notification channel
   */
  public function via(object $notifiable): array
  {
      return ['mail'];
  }

  /**
   * Email content
   */
  public function toMail(object $notifiable): MailMessage
  {
   $whatsappMessage ="Hello,";
         $whatsappMessage .="\nYou’ve received a new job application.";
         $whatsappMessage .="\n**Candidate:** {$this->candidate_name}";
         $whatsappMessage .="\n**Position:** {$this->job_title}";
         $whatsappMessage .="\nLogin to your dashboard to view the full application details.";
         $whatsappMessage .="\nThank you for using our platform!";
      try {
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from = env('TWILIO_WHATSAPP_FROM');

            $twilio = new Client($sid, $token);

            $twilio->messages->create(
                "whatsapp:+91{$this->recruiter_mobile}",
                [
                    "from" => $from,
                    "body" => $whatsappMessage
                ]
            );
        } catch (\Exception $e) {
          //  \Log::error('WhatsApp message failed: ' . $e->getMessage());
        }
      return (new MailMessage)
          ->subject("New Application for {$this->job_title}")
          ->greeting("Hello ,")
          ->line("You’ve received a new job application.")
          ->line("**Candidate:** {$this->candidate_name}")
          ->line("**Email:** {$this->candidate_email}")
          ->line("**Position:** {$this->job_title}")
          
          ->line("Login to your dashboard to view the full application details.")
          ->line('Thank you for using our platform!');
  }
    /**
     * Optional: data for database notification (if needed)
     */
    public function toArray(object $notifiable): array
    {
        return [
            
        ];
    }
}
