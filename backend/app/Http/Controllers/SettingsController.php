<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    private array $smtpKeys = [
        'mail_mailer', 'mail_host', 'mail_port',
        'mail_username', 'mail_password', 'mail_encryption',
        'mail_from_address', 'mail_from_name',
    ];

    /**
     * Retourne la configuration SMTP actuelle.
     * GET /api/admin/settings/smtp
     */
    public function getSmtp()
    {
        $settings = Setting::whereIn('key', $this->smtpKeys)->pluck('value', 'key');

        $result = $settings->toArray();

        // Ne jamais exposer le mot de passe en clair
        if (!empty($result['mail_password'])) {
            $result['mail_password'] = null;
        }

        return response()->json($result);
    }

    /**
     * Sauvegarde la configuration SMTP.
     * PUT /api/admin/settings/smtp
     */
    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_mailer'       => ['sometimes', 'string', 'in:smtp,sendmail,mailgun,ses,postmark'],
            'mail_host'         => ['required', 'string', 'max:255'],
            'mail_port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['required', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name'    => ['required', 'string', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            // Ne pas écraser le mot de passe si non fourni
            if ($key === 'mail_password' && empty($value)) {
                continue;
            }
            Setting::set($key, $value);
        }

        return response()->json(['message' => 'Configuration SMTP sauvegardée avec succès.']);
    }

    /**
     * Envoie un email de test pour valider la configuration SMTP.
     * POST /api/admin/settings/smtp/test
     */
    public function testSmtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->applySmtpConfig();

        try {
            Mail::raw(
                'Ceci est un email de test envoyé depuis DocVerify pour valider votre configuration SMTP.',
                function ($msg) use ($request) {
                    $msg->to($request->email)->subject('Test SMTP — DocVerify');
                }
            );

            return response()->json(['message' => 'Email de test envoyé avec succès.']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Échec de l\'envoi : ' . $e->getMessage(),
            ], 422);
        }
    }

    private function applySmtpConfig(): void
    {
        $settings = Setting::whereIn('key', $this->smtpKeys)->pluck('value', 'key');

        Config::set('mail.default',                 $settings->get('mail_mailer', 'smtp'));
        Config::set('mail.mailers.smtp.host',       $settings->get('mail_host'));
        Config::set('mail.mailers.smtp.port',       $settings->get('mail_port'));
        Config::set('mail.mailers.smtp.username',   $settings->get('mail_username'));
        Config::set('mail.mailers.smtp.password',   $settings->get('mail_password'));
        Config::set('mail.mailers.smtp.encryption', $settings->get('mail_encryption'));
        Config::set('mail.from.address',            $settings->get('mail_from_address'));
        Config::set('mail.from.name',               $settings->get('mail_from_name'));
    }
}
