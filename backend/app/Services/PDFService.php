<?php

namespace App\Services;

use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PDFService
{
    /**
     * Estampille le PDF avec le QR Code sur chaque page.
     *
     * @param  string      $originalPdfPath  Chemin absolu du PDF original
     * @param  string      $qrPngBinary      Données binaires du QR Code en PNG
     * @param  int         $qrSizeMm         Taille du QR en mm (défaut 25)
     * @param  int         $marginMm         Marge pour la position automatique
     * @param  float|null  $positionX        X en mm depuis le bord gauche (null = automatique)
     * @param  float|null  $positionY        Y en mm depuis le bord supérieur (null = automatique)
     */
    /**
     * @param  array<int,array{x_mm:float|null,y_mm:float|null}>  $qrPositions
     *         Positions indexées par numéro de page (1-based). Si vide ou page absente → bas-droit auto.
     */
    public function certifyPdf(
        string $originalPdfPath,
        string $qrPngBinary,
        int    $qrSizeMm    = 25,
        int    $marginMm    = 10,
        array  $qrPositions = []
    ): string {
        $qrTempPath = tempnam(sys_get_temp_dir(), 'qr_');
        if ($qrTempPath === false) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire.');
        }
        file_put_contents($qrTempPath, $qrPngBinary);

        try {
            $pdf       = new Fpdi();
            $pageCount = $pdf->setSourceFile($originalPdfPath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size  = $pdf->getTemplateSize($tplId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                $posPage = $qrPositions[$pageNo] ?? null;
                $x = ($posPage && $posPage['x_mm'] !== null)
                    ? $posPage['x_mm']
                    : ($size['width']  - $qrSizeMm - $marginMm);
                $y = ($posPage && $posPage['y_mm'] !== null)
                    ? $posPage['y_mm']
                    : ($size['height'] - $qrSizeMm - $marginMm);

                $pdf->Image($qrTempPath, $x, $y, $qrSizeMm, $qrSizeMm, 'PNG');
            }

            $filename   = 'certified_' . Str::random(10) . '.pdf';
            $outputPath = storage_path('app/public/certified/' . $filename);

            if (!is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $pdf->Output('F', $outputPath);
        } finally {
            @unlink($qrTempPath);
        }

        return $outputPath;
    }

    /**
     * Génère un rapport de vérification PDF.
     */
    public function generateVerificationReport(string $qrSvg, array $meta): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certified', [
            'qrImage' => $qrSvg,
            'meta'    => $meta,
            'siteUrl' => config('app.url'),
        ]);

        $filename   = 'report_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/reports/' . $filename);

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        file_put_contents($outputPath, $pdf->output());

        return $outputPath;
    }
}
