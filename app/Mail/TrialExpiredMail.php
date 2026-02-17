<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public string $trialEndedAt;

    public function __construct(
        public User $user,
        string $planName,
        string $trialEndedAt,
        public ?string $upgradeUrl = null,
    ) {
        $this->planName = $planName;
        $this->trialEndedAt = $trialEndedAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Trial Has Expired - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.trial-expired',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'trialEndedAt' => $this->trialEndedAt,
                'upgradeUrl' => $this->upgradeUrl,
            ],
        );
    }
}
