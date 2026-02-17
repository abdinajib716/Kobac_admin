<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OfflinePaymentRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public float $amount;
    public string $currency;
    public string $referenceId;
    public ?string $reason;

    public function __construct(
        public User $user,
        string $planName,
        float $amount,
        string $currency,
        string $referenceId,
        ?string $reason = null,
        public ?string $retryUrl = null,
    ) {
        $this->planName = $planName;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->referenceId = $referenceId;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Rejected - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.offline-rejected',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'referenceId' => $this->referenceId,
                'reason' => $this->reason,
                'retryUrl' => $this->retryUrl,
            ],
        );
    }
}
