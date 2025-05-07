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
      protected $round_name;
    protected $interview_date;
    protected $interview_mode;
    protected $interview_link;

    public function __construct($name,$job_title, $company_name, $company_website, $status,$round_name,$interview_date,$interview_mode,$interview_link)
    {
        $this->job_title = $job_title;
          $this->name = $name;
        $this->company_name = $company_name;
        $this->company_website = $company_website;
        $this->status = $status;
        $this->round_name = $round_name;
        $this->interview_date= $interview_date;
        $this->interview_mode = $interview_mode;
        $this->interview_link = $interview_link;
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
    $roundName = $this->round_name ?? 'recruitment round';
   
    switch ($this->status) {
        case 'Shortlisted':
           $interviewDate = $this->interview_date 
        ? \Carbon\Carbon::parse($this->interview_date)->format('F j, Y \a\t g:i A') 
        : 'TBD';

    $platform = $this->interview_mode ?? 'To be confirmed';
    $link = $this->interview_link;
    $hasLink = !empty($link);

    $mail = (new MailMessage)
        ->subject("Interview Scheduled – $jobTitle at $companyName")
        ->greeting("Hi $this->name,")
        ->line("You’ve been Shortlisted for the **$roundName** interview for the $jobTitle position at $companyName.")
        ->line("**Interview Details:**")
        ->line("- **Date & Time**: $interviewDate")
        ->line("- **Platform**: $platform");

    if ($hasLink) {
        $mail->line("- **Link**: [Click to Join Interview]($link)")
             ->action('Join Interview', $link);
    } else {
        $mail->line("- **Link**: Please log in to your dashboard and check under your applied jobs to access the interview link.");
    }

    return $mail
        ->line("If you have any questions, feel free to reply to this email.")
        ->line('Best regards,')
        ->line($companyName);


        case 'Hold':
            return (new MailMessage)
                ->subject("Update on Your Application – $jobTitle at $companyName")
                ->greeting("Hi $this->name,")
                ->line("Thank you again for your interest in the $jobTitle role. At this stage ($roundName), we’ve decided to place your application on hold as we continue reviewing other candidates.")
                ->line("Please know this doesn’t reflect negatively on your profile — we’re keeping your application in consideration and will reach out if there’s any update.")
                ->line("Thanks for your patience and understanding.")
                ->line('Warm regards,')
                ->line($companyName);

        case 'Rejected':
            return (new MailMessage)
                ->subject("Application Update – $jobTitle at $companyName")
                ->greeting("Hi $this->name,")
                ->line("Thank you for taking the time to apply for the $jobTitle position at $companyName. After reviewing your performance in the $roundName, we’ve decided to move forward with other candidates at this time.")
                ->line("We truly appreciate your interest and wish you all the best in your job search.")
                ->line('Kind regards,')
                ->line($companyName);

        case 'Hired':
            return (new MailMessage)
                ->subject("Welcome to the Team, $this->name!")
                ->greeting("Hi $this->name,")
                ->line("Congratulations! We’re thrilled to offer you the position of $jobTitle at $companyName. You successfully completed all rounds, including the $roundName.")
                ->line("We’ll be sending over the offer letter and onboarding details shortly.")
                ->line('Best regards,')
                ->line($companyName);

       case 'Scheduled':
    $interviewDate = $this->interview_date 
        ? \Carbon\Carbon::parse($this->interview_date)->format('F j, Y \a\t g:i A') 
        : 'TBD';

    $platform = $this->interview_mode ?? 'To be confirmed';
    $link = $this->interview_link;
    $hasLink = !empty($link);

    $mail = (new MailMessage)
        ->subject("Interview Scheduled – $jobTitle at $companyName")
        ->greeting("Hi $this->name,")
        ->line("You’ve been scheduled for the **$roundName** interview for the $jobTitle position at $companyName.")
        ->line("**Interview Details:**")
        ->line("- **Date & Time**: $interviewDate")
        ->line("- **Platform**: $platform");

    if ($hasLink) {
        $mail->line("- **Link**: [Click to Join Interview]($link)")
             ->action('Join Interview', $link);
    } else {
        $mail->line("- **Link**: Please log in to your dashboard and check under your applied jobs to access the interview link.");
    }

    return $mail
        ->line("If you have any questions, feel free to reply to this email.")
        ->line('Best regards,')
        ->line($companyName);

        case 'Completed':
            return (new MailMessage)
                ->subject("Interview Completed – $jobTitle at $companyName")
                ->greeting("Hi $this->name,")
                ->line("Thank you for attending your **$roundName** interview for the $jobTitle position at $companyName.")
                ->line("Our team will be reviewing your interview and will reach out with the next steps.")
                ->line('Best regards,')
                ->line($companyName);

        case 'Selected':
         

$interviewDate = $this->interview_date 
        ? \Carbon\Carbon::parse($this->interview_date)->format('F j, Y \a\t g:i A') 
        : 'TBD';

    $platform = $this->interview_mode ?? 'To be confirmed';
    $link = $this->interview_link;
    $hasLink = !empty($link);

    $mail = (new MailMessage)
       ->subject("You’ve Been Selected – $jobTitle at $companyName")
        ->greeting("Hi $this->name,")
         ->line("We’re excited to inform you that you’ve been selected after the **$roundName** for the $jobTitle role at $companyName.")
        ->line("You’ve been scheduled for the next round interview for the $jobTitle position at $companyName.")
        ->line("**Interview Details:**")
        ->line("- **Date & Time**: $interviewDate")
        ->line("- **Platform**: $platform");

    if ($hasLink) {
        $mail->line("- **Link**: [Click to Join Interview]($link)")
             ->action('Join Interview', $link);
    } else {
        $mail->line("- **Link**: Please log in to your dashboard and check under your applied jobs to access the interview link.");
    }
      return $mail
        ->line("If you have any questions, feel free to reply to this email.")
        ->line('Best regards,')
        ->line($companyName);

        case 'Cancelled':
            return (new MailMessage)
                ->subject("Interview Cancelled – $jobTitle at $companyName")
                ->greeting("Hi $this->name,")
                ->line("We regret to inform you that your **$roundName** interview for the $jobTitle role at $companyName has been cancelled.")
                ->line("We apologize for the inconvenience and will let you know if an alternative can be arranged.")
                ->line('Thank you for your understanding.')
                ->line($companyName);

        default:
            return (new MailMessage)
                ->subject("Application Status – $jobTitle at $companyName")
                ->line("Your application status has been updated to: {$this->status}, during the $roundName.");
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
