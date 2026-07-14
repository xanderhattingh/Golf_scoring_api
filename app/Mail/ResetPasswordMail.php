<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Golf Scoring password');
    }

    public function content(): Content
    {
        // Deep link into the app: golfscoring://reset?token=…&email=…
        // The Capacitor app intercepts this scheme and opens the reset screen.
        $deepLink = 'golfscoring://reset?token='.urlencode($this->token)
            .'&email='.urlencode($this->user->email);

        return new Content(
            view: 'emails.reset-password',
            with: [
                'firstName' => $this->user->name,
                'deepLink' => $deepLink,
                'expiresInMinutes' => 60,
            ],
        );
    }
}
