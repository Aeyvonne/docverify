<?php

namespace App\Mail;

use App\Models\DemandesCertification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelleDemandeAdmin extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  DemandesCertification  $demande   La demande soumise (avec user chargé)
     * @param  string                 $roleDestinataire  'validateur' ou 'admin'
     */
    public function __construct(
        public DemandesCertification $demande,
        public string $roleDestinataire = 'validateur'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DocVerify] Nouvelle demande de certification — ' . ($this->demande->user->nom_institution ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        $traitementUrl = $this->roleDestinataire === 'validateur'
            ? config('app.frontend_url') . '/validateur/demandes'
            : config('app.frontend_url') . '/admin/demandes';

        return new Content(
            markdown: 'emails.nouvelle-demande-admin',
            with: [
                'demande'          => $this->demande,
                'user'             => $this->demande->user,
                'adminUrl'         => $traitementUrl,
                'roleDestinataire' => $this->roleDestinataire,
            ],
        );
    }

    /**
     * Attache le justificatif en pièce jointe si le fichier existe.
     */
    public function attachments(): array
    {
        $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->demande->fichier_preuve);

        // Chercher dans private/ (Laravel 11) puis app/ (Laravel 10)
        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $relativePath);
        if (!file_exists($path)) {
            $path = storage_path('app' . DIRECTORY_SEPARATOR . $relativePath);
        }

        if (!file_exists($path)) {
            return [];
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'pdf'         => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            default       => 'application/octet-stream',
        };

        $nomFichier = 'justificatif_'
            . \Illuminate\Support\Str::slug($this->demande->user->nom_institution ?? 'institution')
            . '.' . $extension;

        return [
            Attachment::fromPath($path)
                ->as($nomFichier)
                ->withMime($mime),
        ];
    }
}
