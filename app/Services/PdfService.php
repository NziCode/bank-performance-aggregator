<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfService
{
    public static function make(string $html, string $orientation = 'L'): Mpdf
    {
        $defaultConfig   = (new ConfigVariables())->getDefaults();
        $fontDirs        = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4-' . $orientation,
            'orientation'       => $orientation === 'L' ? 'L' : 'P',
            'default_font'      => 'vazirmatn',
            'default_font_size' => 10,
            'fontDir'           => array_merge($fontDirs, [storage_path('fonts/')]),
            'fontdata'          => array_merge($fontData, [
                'vazirmatn' => [
                    'R'  => 'Vazirmatn-Regular.ttf',
                    'B'  => 'Vazirmatn-Bold.ttf',
                    'useOTL'    => 0xFF,
                    'useKashida' => 75,
                ],
            ]),
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;
        $mpdf->WriteHTML($html);

        return $mpdf;
    }

    public static function download(Mpdf $mpdf, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return response()->streamDownload(function () use ($mpdf, $filename) {
            $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
