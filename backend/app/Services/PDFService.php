<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class PDFService
{
    /**
     * Injecte le QR dans un PDF existant.
     * NOTE: Implémentation volontairement simple : on génère un nouveau PDF "certifié"
     * à partir d’une vue/HTML. Si vous devez *vraiment* modifier le PDF original,
     * on basculera vers FPDI/FPDF.
     */
    public function generateCertifiedPdfFromHtml(
        string $originalPdfPath,
        string $qrSvg,
        array $meta
    ): string {
        // Pour l’instant, on ne lit pas le PDF original : on génère un PDF certifié.
        // Cela permet de livrer une première version fonctionnelle.
        // La vraie injection dans le PDF original se fait ensuite avec FPDI.

        return $this->generatePdfWithDompdf($qrSvg, $meta);
    }

    private function generatePdfWithDompdf(string $qrSvg, array $meta): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certified', [
            'qrSvg' => $qrSvg,
            'meta' => $meta,
            'siteUrl' => config('app.url'),
        ]);

        $filename = 'certified_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/certified/' . $filename);

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        file_put_contents($outputPath, $pdf->output());

        return $outputPath;
    }
}

