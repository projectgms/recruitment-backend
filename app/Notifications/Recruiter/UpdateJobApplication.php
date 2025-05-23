<?php

namespace App\Notifications\Recruiter;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Jobs;
use Twilio\Rest\Client;

class UpdateJobApplication extends Notification
{
    use Queueable;

    protected $name;
    protected $job_title;
    protected $company_name;
    protected $company_website;
    protected $status;
    protected $round_name;
    protected $interview_date;
    protected $interview_mode;
    protected $interview_link;
    protected $mobile;

    public function __construct($name, $job_title, $company_name, $company_website, $status, $round_name, $interview_date, $interview_mode, $interview_link, $mobile)
    {
        $this->name = $name;
        $this->job_title = $job_title;
        $this->company_name = $company_name;
        $this->company_website = $company_website;
        $this->status = $status;
        $this->round_name = $round_name;
        $this->interview_date = $interview_date;
        $this->interview_mode = $interview_mode;
        $this->interview_link = $interview_link;
        $this->mobile = $mobile;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jobTitle = $this->job_title ?? 'Job Title';
        $companyName = $this->company_name ?? 'Company Name';
        $companyWebsite = $this->company_website ?? '#';
        $roundName = $this->round_name ?? 'recruitment round';

        $interviewDate = $this->interview_date 
            ? \Carbon\Carbon::parse($this->interview_date)->format('F j, Y \a\t g:i A') 
            : 'TBD';

        $platform = $this->interview_mode ?? 'To be confirmed';
        $link = $this->interview_link;
        $hasLink = !empty($link);

        $whatsappMessage = '';

        switch ($this->status) {
            case 'Shortlisted':
                $whatsappMessage = "Hi {$this->name},\nYou’ve been shortlisted for the {$roundName} round of the {$jobTitle} role at {$companyName}.\nInterview Date: {$interviewDate}\nPlatform: {$platform}";
                break;

            case 'Hold':
                $whatsappMessage = "Hi {$this->name},\nYour application for the {$jobTitle} role at {$companyName} is currently on hold after the {$roundName} round. We'll keep you updated.";
                break;

            case 'Rejected':
                $whatsappMessage = "Hi {$this->name},\nThank you for applying for the {$jobTitle} role at {$companyName}. After the {$roundName}, we’ve decided to move forward with other candidates.";
                break;

            case 'Hired':
                $whatsappMessage = "Hi {$this->name},\nCongratulations! You've been hired for the {$jobTitle} role at {$companyName} after completing all interview rounds!";
                break;

            case 'Scheduled':
                $whatsappMessage = "Hi {$this->name},\nYour interview for the {$jobTitle} role at {$companyName} is scheduled.\nRound: {$roundName}\nDate: {$interviewDate}\nPlatform: {$platform}";
                break;

            case 'Completed':
                $whatsappMessage = "Hi {$this->name},\nYou have completed your {$roundName} interview for the {$jobTitle} role at {$companyName}. We'll contact you soon with next steps.";
                break;

            case 'Selected':
                $whatsappMessage = "Hi {$this->name},\nYou've been selected for the next round of the {$jobTitle} role at {$companyName}.\nRound: {$roundName}\nDate: {$interviewDate}\nPlatform: {$platform}";
                break;

            case 'Cancelled':
                $whatsappMessage = "Hi {$this->name},\nYour interview for the {$jobTitle} role at {$companyName} ({$roundName}) has been cancelled. We’ll inform you about next steps soon.";
                break;

            default:
                $whatsappMessage = "Hi {$this->name},\nYour application status for the {$jobTitle} role at {$companyName} has been updated to: {$this->status}.";
                break;
        }

        if ($hasLink) {
            $whatsappMessage .= "\nJoin Link: {$link}";
        }
         $whatsappMessage .="\nNote - Please do not reply to this number. It is not monitored.";
         $whatsappMessage .="\nBest regards";
         $whatsappMessage .="\n{$companyName}";
         $whatsappMessage .="\n{$companyWebsite}";
        // ✅ Send WhatsApp Message via Twilio
        try {
           $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from = env('TWILIO_WHATSAPP_FROM');

            $twilio = new Client($sid, $token);

            $twilio->messages->create(
                "whatsapp:+91{$this->mobile}",
                [
                    "from" => $from,
                    "body" => $whatsappMessage
                ]
            );
        } catch (\Exception $e) {
           // \Log::error('WhatsApp message failed: ' . $e->getMessage());
        }

        // ✅ Return Email Message
        $mail = (new MailMessage)
            ->subject("Application Update – $jobTitle at $companyName")
            ->greeting("Hi $this->name,");

        switch ($this->status) {
            case 'Shortlisted':
            case 'Scheduled':
            case 'Selected':
                $mail->line("You’ve been {$this->status} for the **$roundName** interview for the $jobTitle position at $companyName.")
                    ->line("**Interview Details:**")
                    ->line("- **Date & Time**: $interviewDate")
                    ->line("- **Platform**: $platform");

                // if ($hasLink) {
                //     $mail->line("- **Link**: [Click to Join Interview]($link)")
                //          ->action('Join Interview', $link);
                // } else {
                    $mail->line("- **Link**: Please check your dashboard for the interview link.");
                //}
                break;

            case 'Hold':
                $mail->line("Your application has been placed on hold at the **$roundName** stage for the $jobTitle position at $companyName.")
                    ->line("We’re still reviewing applications and will reach out with updates.");
                break;

            case 'Rejected':
                $mail->line("After the **$roundName** for the $jobTitle role at $companyName, we’ve decided to move forward with other candidates.")
                    ->line("We appreciate your interest and wish you success in your job search.");
                break;

            case 'Hired':
                $mail->line("🎉 Congratulations! You’ve been hired for the $jobTitle position at $companyName.")
                    ->line("We’ll send your offer letter and onboarding instructions shortly.");
                break;

            case 'Completed':
                $mail->line("You’ve completed the **$roundName** interview for the $jobTitle at $companyName.")
                    ->line("We’ll review your interview and get back with updates soon.");
                break;

            case 'Cancelled':
                $mail->line("We regret to inform you that your **$roundName** interview for the $jobTitle role has been cancelled.")
                    ->line("We apologize for the inconvenience and will share alternative steps soon.");
                break;

            default:
                $mail->line("Your application status has been updated to **{$this->status}** at the **{$roundName}** stage.");
        }

        return $mail->line('Best regards,')->line($companyName)->line($companyWebsite);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
