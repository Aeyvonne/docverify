<?php

namespace App\Mail;

use App\Models\DemandesCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeApprouvee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemandesCertification $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DocVerify] Votre demande de certification a été approuvée',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.demande-approuvee',
            with: [
                'user'         => $this->demande->user,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
            ],
        );
    }
}
