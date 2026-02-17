<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $planName;
    public float $amount;
    public string $currency;
    public string $paymentMethod;
    public string $referenceId;
    public ?string $errorMessage;

    public function __construct(
        public User $user,
        string $planName,
        float $amount,
        string $currency,
        string $paymentMethod,
        string $referenceId,
        ?string $errorMessage = null,
        public ?string $retryUrl = null,
    ) {
        $this->planName = $planName;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
        $this->referenceId = $referenceId;
        $this->errorMessage = $errorMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Failed - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.failed',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'paymentMethod' => $this->paymentMethod,
                'referenceId' => $this->referenceId,
                'errorMessage' => $this->errorMessage,
                'retryUrl' => $this->retryUrl,
            ],
        );
    }
}
