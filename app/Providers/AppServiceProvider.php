<?php

namespace App\Providers;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Builder::defaultStringLength(191);

        $this->registerDompdfFonts();
    }

    private function registerDompdfFonts(): void
    {
        $fontDir = storage_path('fonts/');

        $vazirRegular = $fontDir . 'Vazirmatn-Regular.ttf';
        $vazirBold    = $fontDir . 'Vazirmatn-Bold.ttf';

        if (! file_exists($vazirRegular) || ! file_exists($vazirBold)) {
            return;
        }

        app()->resolving('dompdf.wrapper', function ($pdf) use ($fontDir, $vazirRegular, $vazirBold) {
            $dompdf  = $pdf->getDomPDF();
            $options = $dompdf->getOptions();
            $options->setFontDir($fontDir);
            $options->setFontCache($fontDir);
            $options->setDefaultFont('vazirmatn');
            $dompdf->setOptions($options);

            $fontMetrics = $dompdf->getFontMetrics();
            $fontMetrics->registerFont(
                ['family' => 'vazirmatn', 'weight' => 'normal', 'style' => 'normal'],
                $vazirRegular,
            );
            $fontMetrics->registerFont(
                ['family' => 'vazirmatn', 'weight' => 'bold', 'style' => 'normal'],
                $vazirBold,
            );
        });
    }
}
