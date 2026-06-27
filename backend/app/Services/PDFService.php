<?php

namespace App\Services;

use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class PDFService
{
    /**
     * Estampille le PDF avec le QR Code sur CHAQUE page à la même position relative.
     *
     * La position (positionX, positionY) est définie sur la page 1 en mm.
     * Pour les pages suivantes, la position est recalculée proportionnellement
     * par rapport aux dimensions de chaque page — le QR apparaît au même
     * endroit visuel sur toutes les pages même si les formats diffèrent.
     *
     * @param  string      $originalPdfPath  Chemin absolu du PDF original
     * @param  string      $qrPngBinary      Données binaires du QR Code en PNG
     * @param  int         $qrSizeMm         Taille du QR en mm (15–60, défaut 25)
     * @param  int         $marginMm         Marge pour la position automatique
     * @param  float|null  $positionX        X en mm sur la page 1 (null = automatique)
     * @param  float|null  $positionY        Y en mm sur la page 1 (null = automatique)
     */
    public function certifyPdf(
        string $originalPdfPath,
        string $qrPngBinary,
        int    $qrSizeMm  = 25,
        int    $marginMm  = 10,
        ?float $positionX = null,
        ?float $positionY = null
    ): string {
        // FPDI a besoin d'un fichier image sur le disque
        $qrTempPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($qrTempPath, $qrPngBinary);

        $pdf       = new Fpdi();
        $pageCount = $pdf->setSourceFile($originalPdfPath);

        // Récupérer les dimensions de la page 1 pour calculer les ratios
        $firstTplId   = $pdf->importPage(1);
        $firstSize    = $pdf->getTemplateSize($firstTplId);
        $page1Width   = $firstSize['width'];
        $page1Height  = $firstSize['height'];

        // Position sur la page 1 (mm) → calculer le ratio par rapport aux dimensions
        $xAuto = $page1Width  - $qrSizeMm - $marginMm;
        $yAuto = $page1Height - $qrSizeMm - $marginMm;

        $x1 = $positionX ?? $xAuto;
        $y1 = $positionY ?? $yAuto;

        // Ratio position/dimensions page 1 (entre 0 et 1)
        // Permet de reproduire la même position visuelle sur toutes les pages
        $ratioX = $x1 / $page1Width;
        $ratioY = $y1 / $page1Height;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($pageNo === 1) {
                // Page 1 : position exacte choisie par l'émetteur
                $x = $x1;
                $y = $y1;
            } else {
                // Pages suivantes : position proportionnelle
                $x = $ratioX * $size['width'];
                $y = $ratioY * $size['height'];
            }

            // S'assurer que le QR reste dans les limites de la page
            $x = max(0, min($x, $size['width']  - $qrSizeMm));
            $y = max(0, min($y, $size['height'] - $qrSizeMm));

            $pdf->Image($qrTempPath, $x, $y, $qrSizeMm, $qrSizeMm, 'PNG');
        }

        @unlink($qrTempPath);

        $filename   = 'certified_' . Str::random(10) . '.pdf';
        $outputPath = storage_path('app/public/certified/' . $filename);

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Génère un rapport de vérification PDF (métadonnées + QR).
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

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        file_put_contents($outputPath, $pdf->output());

        return $outputPath;
    }
}
