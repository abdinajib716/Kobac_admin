<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public string $trialEndsAt;
    public int $daysRemaining;

    public function __construct(
        public User $user,
        string $planName,
        string $trialEndsAt,
        int $daysRemaining,
        public ?string $upgradeUrl = null,
    ) {
        $this->planName = $planName;
        $this->trialEndsAt = $trialEndsAt;
        $this->daysRemaining = $daysRemaining;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Trial Ends in {$this->daysRemaining} Day" . ($this->daysRemaining !== 1 ? 's' : '') . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.trial-expiring',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'trialEndsAt' => $this->trialEndsAt,
                'daysRemaining' => $this->daysRemaining,
                'upgradeUrl' => $this->upgradeUrl,
            ],
        );
    }
}
