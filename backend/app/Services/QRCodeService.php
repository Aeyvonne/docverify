<?php

namespace App\Services;

use Endroid\QrCode\QrCode;

class QRCodeService
{
    /**
     * Génère le QR sous forme de SVG.
     */
    public function renderQrSvg(string $value, int $size = 300): string
    {
        $qr = QrCode::create($value)
            ->setSize($size)
            ->setMargin(10);

        return $qr->toString();
    }

    /**
     * Retourne une valeur unique pour le champ qr_token.
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}

