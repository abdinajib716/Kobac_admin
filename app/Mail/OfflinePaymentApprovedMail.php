<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OfflinePaymentApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public float $amount;
    public string $currency;
    public string $referenceId;
    public string $approvedAt;

    public function __construct(
        public User $user,
        string $planName,
        float $amount,
        string $currency,
        string $referenceId,
        string $approvedAt,
        public ?string $dashboardUrl = null,
    ) {
        $this->planName = $planName;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->referenceId = $referenceId;
        $this->approvedAt = $approvedAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Approved - Subscription Activated! - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.offline-approved',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'referenceId' => $this->referenceId,
                'approvedAt' => $this->approvedAt,
                'dashboardUrl' => $this->dashboardUrl,
            ],
        );
    }
}
