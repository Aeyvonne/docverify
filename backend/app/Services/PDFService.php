<?php

namespace App\Services;

use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PDFService
{
    /**
     * Estampille le PDF original avec le QR Code sur CHAQUE page,
     * sans toucher au contenu existant du document.
     */
    public function certifyPdf(string $originalPdfPath, string $qrPngBinary, int $qrSizeMm = 25, int $marginMm = 10): string
    {
        // FPDI a besoin d'un vrai fichier image, pas de données binaires en mémoire
        $qrTempPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($qrTempPath, $qrPngBinary);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($originalPdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Position : coin bas-droit de la page
            $x = $size['width'] - $qrSizeMm - $marginMm;
            $y = $size['height'] - $qrSizeMm - $marginMm;

            $pdf->Image($qrTempPath, $x, $y, $qrSizeMm, $qrSizeMm, 'PNG');
        }

        @unlink($qrTempPath);

        $filename = 'certified_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/certified/' . $filename);

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Génère un rapport de vérification PDF (métadonnées + QR), pas le document original.
     */
    public function generateVerificationReport(string $qrSvg, array $meta): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certified', [
            'qrImage' => $qrSvg,
            'meta' => $meta,
            'siteUrl' => config('app.url'),
        ]);

        $filename = 'report_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/reports/' . $filename);

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        file_put_contents($outputPath, $pdf->output());

        return $outputPath;
    }
}