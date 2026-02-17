<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public string $expiredAt;

    public function __construct(
        public User $user,
        string $planName,
        string $expiredAt,
        public ?string $renewUrl = null,
    ) {
        $this->planName = $planName;
        $this->expiredAt = $expiredAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Expired - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.expired',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'expiredAt' => $this->expiredAt,
                'renewUrl' => $this->renewUrl,
            ],
        );
    }
}
