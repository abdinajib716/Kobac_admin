<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public string $startsAt;
    public string $endsAt;
    public ?string $paymentMethod;

    public function __construct(
        public User $user,
        string $planName,
        string $startsAt,
        string $endsAt,
        ?string $paymentMethod = null,
        public ?string $dashboardUrl = null,
    ) {
        $this->planName = $planName;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->paymentMethod = $paymentMethod;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Activated - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.activated',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'startsAt' => $this->startsAt,
                'endsAt' => $this->endsAt,
                'paymentMethod' => $this->paymentMethod,
                'dashboardUrl' => $this->dashboardUrl,
            ],
        );
    }
}
