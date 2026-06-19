<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class QRCodeService
{
    public function renderQrSvg(string $value, int $size = 300): string
    {
        $qrCode = new QrCode(
            data: $value,
            encoding: new Encoding('UTF-8'),
            size: $size,
            margin: 10,
        );

        return (new SvgWriter())->write($qrCode)->getString();
    }

    /**
     * Génère le QR en PNG (binaire brut) — utilisé pour l'intégration dans les PDF,
     * car DomPDF ne gère pas le SVG de façon fiable.
     */
    public function renderQrPng(string $value, int $size = 300): string
    {
        $qrCode = new QrCode(
            data: $value,
            encoding: new Encoding('UTF-8'),
            size: $size,
            margin: 10,
        );

        return (new PngWriter())->write($qrCode)->getString();
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}