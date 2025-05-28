<?php

namespace App\Notifications\JobSeeker;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Jobs;
use Twilio\Rest\Client;

class JobSeekerJobUpdate extends Notification
{
    use Queueable;
  
  protected $candidate_name;
  protected $candidate_mobile;
  protected $job_title;
  protected $company_email;
  protected $recruiter_mobile;
  protected $company_name;
  protected $website;

  public function __construct($candidate_name,$candidate_mobile, $job_title, $company_email,$recruiter_mobile,$company_name,$website)
  {
      $this->candidate_name = $candidate_name;
      $this->candidate_mobile = $candidate_mobile;
      $this->job_title = $job_title;
      $this->company_email = $company_email;
      $this->recruiter_mobile=$recruiter_mobile;
      $this->company_name=$company_name;
      $this->website=$website;
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
   $whatsappMessage ="Hello  {$this->candidate_name},";
         $whatsappMessage .="\nYour Application is received for.";
       
         $whatsappMessage .="\n**Position:** {$this->job_title}";
         $whatsappMessage .="\n**Company Name:** {$this->company_name}";
          $whatsappMessage .="\n**Company Website:** {$this->website}";
           $whatsappMessage .="\n**Contact Number:** {$this->recruiter_mobile}";
            $whatsappMessage .="\n**Contact Email:** {$this->company_email}";
         $whatsappMessage .="\nLogin to your dashboard to view the full application details.";
         $whatsappMessage .="\nThank you for using our platform!";
      try {
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from = env('TWILIO_WHATSAPP_FROM');

            $twilio = new Client($sid, $token);

            $twilio->messages->create(
                "whatsapp:+91{$this->candidate_mobile}",
                [
                    "from" => $from,
                    "body" => $whatsappMessage
                ]
            );
        } catch (\Exception $e) {
          //  \Log::error('WhatsApp message failed: ' . $e->getMessage());
        }
      return (new MailMessage)
          ->subject("Your Application is received for {$this->job_title}")
          ->greeting("Hello {$this->candidate_name},")
          
          ->line("**Position:** {$this->job_title}")
          ->line("**Company Name:** {$this->company_name}")
          ->line("**Company Website:** {$this->website}")
          ->line("**Contact Number:** {$this->recruiter_mobile}")
          ->line("**Contact Email:** {$this->company_email}")
          
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
