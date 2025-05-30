<?php

namespace App\Notifications\Recruiter;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Jobs;
use Twilio\Rest\Client;

class CandidateInvitation extends Notification
{
    use Queueable;

    protected $name;
    protected $job_title;
    protected $company_name;
    protected $company_website;
    protected $location;
    protected $skill;
   

    public function __construct($name, $job_title, $company_name, $company_website, $location, $skill)
    {
        $this->name = $name;
        $this->job_title = $job_title;
        $this->company_name = $company_name;
        $this->company_website = $company_website;
        $this->location = $location;
        $this->skill = $skill;
      
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
      
        // ✅ Return Email Message
      
    return (new MailMessage)
        ->subject("Job Invitation – {$this->job_title} at {$this->company_name}")
        ->greeting("Hi {$this->name},")
        ->line("Your profile matches the position of {$this->job_title} at {$this->company_name}.")
        ->line('Best regards,')
        ->line($this->company_name)
        ->line($this->company_website);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
