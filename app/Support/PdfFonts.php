<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * dompdf ships without Thai glyphs (its bundled DejaVu fonts don't cover the
 * Thai script), so every PDF export needs the Sarabun font registered before
 * rendering. registerFont() also writes to dompdf's on-disk font cache, so
 * after the first call in this environment it's a no-op lookup.
 */
class PdfFonts
{
    public static function registerThai(): void
    {
        $fontMetrics = Pdf::getDomPDF()->getFontMetrics();

        $weights = [
            ['style' => 'normal', 'weight' => 'normal', 'file' => 'Sarabun-Regular.ttf'],
            ['style' => 'normal', 'weight' => 'bold', 'file' => 'Sarabun-Bold.ttf'],
            ['style' => 'italic', 'weight' => 'normal', 'file' => 'Sarabun-Italic.ttf'],
            ['style' => 'italic', 'weight' => 'bold', 'file' => 'Sarabun-BoldItalic.ttf'],
        ];

        foreach ($weights as $w) {
            $fontMetrics->registerFont(
                ['family' => 'Sarabun', 'style' => $w['style'], 'weight' => $w['weight']],
                resource_path('fonts/'.$w['file'])
            );
        }
    }
}
