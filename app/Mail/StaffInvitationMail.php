<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Business;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use SerializesModels;

    public string $role;
    public ?string $branchName;
    public ?string $temporaryPassword;

    public function __construct(
        public User $user,
        public Business $business,
        string $role,
        ?string $branchName = null,
        ?string $temporaryPassword = null,
        public ?string $loginUrl = null,
    ) {
        $this->role = $role;
        $this->branchName = $branchName;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve Been Invited to Join ' . $this->business->name . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff.invitation',
            with: [
                'user' => $this->user,
                'business' => $this->business,
                'role' => $this->role,
                'branchName' => $this->branchName,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $this->loginUrl ?? config('app.url'),
            ],
        );
    }
}
