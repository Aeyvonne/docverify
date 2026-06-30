<?php

namespace App\Mail;

use App\Models\DemandesCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeRefusee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemandesCertification $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DocVerify] Votre demande de certification a été refusée',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.demande-refusee',
            with: [
                'user'        => $this->demande->user,
                'motif'       => $this->demande->motif_refus,
                'retryUrl'    => config('app.frontend_url') . '/certification',
            ],
        );
    }
}
