<?php

namespace App\Notifications\Recruiter;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Jobs;

class UpdateJobApplication extends Notification
{
    use Queueable;
  protected $name;
    protected $job_title;
    protected $company_name;
    protected $company_website;
    protected $status; // e.g., "shortlisted", "rejected", etc.

    public function __construct($name,$job_title, $company_name, $company_website, $status)
    {
        $this->job_title = $job_title;
          $this->name = $name;
        $this->company_name = $company_name;
        $this->company_website = $company_website;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail message based on application status.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $jobTitle = $this->job_title ?? 'Job Title';
        $companyName = $this->company_name ?? 'Company Name';
        $companyWebsite = $this->company_website ?? '#';

        switch ($this->status) {
            case 'Shortlisted':
                return (new MailMessage)
                    ->subject("You’ve Been Shortlisted – $jobTitle at $companyName")
                    ->greeting("Hi  $this->name,")
                    ->line("Great news! After reviewing your application, we’re pleased to let you know that you’ve been shortlisted for the $jobTitle role at $companyName.")
                    ->action('View Company Website', $companyWebsite)
                    ->line("Our team will reach out to you soon with the next steps.")
                    ->line('Best regards,')
                    ->line($companyName);

             case 'Hold':
                        return (new MailMessage)
                            ->subject(" Update on Your Application – $jobTitle at $companyName")
                            ->greeting("Hi  $this->name,")
                            ->line("Thank you again for your interest in the $jobTitle role. At this time, we’ve decided to place your application on hold as we continue reviewing other candidates.")
                            ->line("Please know this doesn’t reflect negatively on your profile — we’re keeping your application in consideration and will reach out if there’s any update.")
                            ->line("Thanks for your patience and understanding.")
        
                            ->line('Warm regards,')
                            ->line($companyName);
        
            case 'Rejected':
                return (new MailMessage)
                    ->subject("Application Update – $jobTitle at $companyName")
                    ->greeting("Hi  $this->name,")
                    ->line("Thank you for taking the time to apply for the $jobTitle position at $companyName. After careful consideration, we’ve decided to move forward with other candidates at this time.")
                    ->line("We truly appreciate your interest and wish you all the best in your job search. Please feel free to apply for future opportunities with us.")
                    
                    ->line('Kind regards,')
                    ->line($companyName);

               case 'Hired':
                return (new MailMessage)
                    ->subject("Welcome to the Team,  $this->name!")
                    ->greeting("Hi  $this->name,")
                    ->line("Congratulations! We’re thrilled to offer you the position of $jobTitle at $companyName. We’re excited about the potential you bring and look forward to having you on the team.")
                    ->line("We’ll be sending over the offer letter and onboarding details shortly.")
                    
                    ->line('Best regards,')
                    ->line($companyName);

                    
       

            // Add more cases for 'applied', 'hold', 'hired' if needed

            default:
                return (new MailMessage)
                    ->subject("Application Status – $jobTitle at $companyName")
                    ->line("Your application status has been updated to: {$this->status}.");
        }
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
