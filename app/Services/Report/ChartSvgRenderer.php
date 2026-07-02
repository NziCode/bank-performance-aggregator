<?php

namespace App\Services\Report;

/**
 * Renders small self-contained <svg> charts from plain label=>value arrays.
 * No JS, no canvas, no external service — so the exact same markup can be
 * embedded in the on-screen Blade view and in the mpdf-generated PDF exports
 * (mpdf renders inline SVG natively), guaranteeing charts always show up in
 * the PDF instead of relying on client-side canvas capture.
 */
class ChartSvgRenderer
{
    private const STATUS_COLORS = [
        'approved' => '#16a34a',
        'pending'  => '#ca8a04',
        'rejected' => '#dc2626',
    ];

    private const STATUS_LABELS = [
        'approved' => 'تایید',
        'pending'  => 'انتظار',
        'rejected' => 'رد',
    ];

    private const CATEGORICAL = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316'];

    private const TEXT_PRIMARY   = '#1f2937';
    private const TEXT_SECONDARY = '#374151';
    private const TEXT_MUTED     = '#9ca3af';
    private const GRID_COLOR     = '#e5e7eb';

    // ─── Horizontal bar (single series) ───────────────────────────────────────

    public static function bar(array $data, array $opts = []): string
    {
        if (empty($data)) {
            return self::emptyState($opts['empty_message'] ?? 'داده‌ای برای نمایش نمودار وجود ندارد.');
        }

        $width      = $opts['width'] ?? 640;
        $rowHeight  = 30;
        $barHeight  = 20;
        $gap        = 8;
        $color      = $opts['color'] ?? '#1e3a5f';
        $title      = $opts['title'] ?? null;
        $showGrid   = $opts['gridlines'] ?? true;

        $labelW   = self::labelColumnWidth($data);
        $valueW   = 56;
        $barX0    = $labelW + 10;
        $barAreaW = max(40, $width - $labelW - 10 - $valueW);

        $max = max(1, max($data));
        $step = self::niceStep($max);

        $titleH = $title ? 26 : 0;
        $gridH  = $showGrid ? 18 : 6;
        $rows   = count($data);
        $height = $titleH + $gridH + $rows * ($rowHeight + $gap);

        $svg  = self::svgOpen($width, $height);
        if ($title) {
            $svg .= self::text($width / 2, 16, $title, self::TEXT_SECONDARY, 13, 'middle', true);
        }

        $chartTop = $titleH;

        if ($showGrid) {
            for ($v = 0; $v <= $max + $step; $v += $step) {
                $x = $barX0 + ($v / $max) * $barAreaW;
                if ($x > $barX0 + $barAreaW + 1) {
                    break;
                }
                $svg .= '<line x1="' . self::n($x) . '" y1="' . self::n($chartTop + 12) . '" x2="' . self::n($x) . '" y2="' . self::n($height) . '" stroke="' . self::GRID_COLOR . '" stroke-width="1"/>';
                $svg .= self::text($x, $chartTop + 10, self::fmt($v), self::TEXT_MUTED, 9, 'middle');
            }
        }

        $y = $chartTop + $gridH;
        foreach ($data as $label => $value) {
            $barW = $max > 0 ? max(2, ($value / $max) * $barAreaW) : 0;
            $cy   = $y + $rowHeight / 2;

            $svg .= self::text($labelW, $cy + 4, (string) $label, self::TEXT_SECONDARY, 11, 'end');
            if ($barW > 0) {
                $svg .= self::roundedEndRect($barX0, $cy - $barHeight / 2, $barW, $barHeight, 4, $color);
            }
            $svg .= self::text($barX0 + $barW + 6, $cy + 4, self::fmt($value), self::TEXT_PRIMARY, 11, 'start', true);

            $y += $rowHeight + $gap;
        }

        $svg .= '</svg>';
        return $svg;
    }

    // ─── Horizontal stacked bar (approved / pending / rejected) ───────────────

    /**
     * @param array $rows [['label' => string, 'approved' => int, 'pending' => int, 'rejected' => int], ...]
     */
    public static function stackedBar(array $rows, array $opts = []): string
    {
        if (empty($rows)) {
            return self::emptyState($opts['empty_message'] ?? 'داده‌ای برای نمایش نمودار وجود ندارد.');
        }

        $width     = $opts['width'] ?? 640;
        $rowHeight = 30;
        $barHeight = 20;
        $gapRow    = 8;
        $segGap    = 2;
        $title     = $opts['title'] ?? null;

        $labels   = array_column($rows, 'label');
        $labelW   = self::labelColumnWidth(array_combine($labels, array_fill(0, count($labels), 0)));
        $barX0    = $labelW + 10;
        $barAreaW = max(40, $width - $labelW - 10 - 12);

        $totals   = array_map(fn($r) => $r['approved'] + $r['pending'] + $r['rejected'], $rows);
        $maxTotal = max(1, max($totals));

        $legendH = 24;
        $titleH  = $title ? 24 : 0;
        $height  = $titleH + $legendH + count($rows) * ($rowHeight + $gapRow);

        $svg = self::svgOpen($width, $height);
        if ($title) {
            $svg .= self::text($width / 2, 16, $title, self::TEXT_SECONDARY, 13, 'middle', true);
        }

        // Legend (multi-series -> always present)
        $lx = $width - 10;
        $ly = $titleH + 14;
        foreach (['approved', 'pending', 'rejected'] as $key) {
            $swatchW = 10;
            $labelText = self::STATUS_LABELS[$key];
            $textW = self::estimateTextWidth($labelText, 10) + $swatchW + 6;
            $svg .= '<rect x="' . self::n($lx - $swatchW) . '" y="' . self::n($ly - 8) . '" width="' . $swatchW . '" height="' . $swatchW . '" rx="2" fill="' . self::STATUS_COLORS[$key] . '"/>';
            $svg .= self::text($lx - $swatchW - 4, $ly, $labelText, self::TEXT_SECONDARY, 10, 'end');
            $lx -= $textW + 14;
        }

        $y = $titleH + $legendH;
        foreach ($rows as $row) {
            $total = $row['approved'] + $row['pending'] + $row['rejected'];
            $barW  = $maxTotal > 0 ? ($total / $maxTotal) * $barAreaW : 0;
            $cy    = $y + $rowHeight / 2;

            $svg .= self::text($labelW, $cy + 4, (string) $row['label'], self::TEXT_SECONDARY, 11, 'end');

            if ($barW > 0) {
                $segments = ['approved' => $row['approved'], 'pending' => $row['pending'], 'rejected' => $row['rejected']];
                $nonZero  = array_filter($segments, fn($v) => $v > 0);
                $lastKey  = array_key_last($nonZero);
                $x = $barX0;
                $i = 0;
                $n = count($nonZero);
                foreach ($segments as $key => $val) {
                    if ($val <= 0) {
                        continue;
                    }
                    $i++;
                    $segW = ($val / $total) * $barW;
                    $drawW = $segW - ($i < $n ? $segGap : 0);
                    $drawW = max(1, $drawW);
                    if ($key === $lastKey) {
                        $svg .= self::roundedEndRect($x, $cy - $barHeight / 2, $drawW, $barHeight, 4, self::STATUS_COLORS[$key]);
                    } else {
                        $svg .= '<rect x="' . self::n($x) . '" y="' . self::n($cy - $barHeight / 2) . '" width="' . self::n($drawW) . '" height="' . self::n($barHeight) . '" fill="' . self::STATUS_COLORS[$key] . '"/>';
                    }
                    $x += $segW;
                }
                $svg .= self::text($barX0 + $barW + 6, $cy + 4, self::fmt($total), self::TEXT_PRIMARY, 11, 'start', true);
            }

            $y += $rowHeight + $gapRow;
        }

        $svg .= '</svg>';
        return $svg;
    }

    // ─── Donut (categorical composition) ──────────────────────────────────────

    public static function donut(array $data, array $opts = []): string
    {
        $data = array_filter($data, fn($v) => $v > 0);
        if (empty($data)) {
            return self::emptyState($opts['empty_message'] ?? 'داده‌ای برای نمایش نمودار وجود ندارد.');
        }

        // Fold anything past the 7 fixed categorical hues into "سایر" (Other).
        if (count($data) > 7) {
            arsort($data);
            $head  = array_slice($data, 0, 6, true);
            $other = array_sum(array_slice($data, 6, null, true));
            $data  = $head + ['سایر' => $other];
        }

        $title  = $opts['title'] ?? null;
        $width  = $opts['width'] ?? 640;
        $size   = 180;
        $cx     = $size / 2 + 10;
        $cy     = $title ? $size / 2 + 30 : $size / 2 + 10;
        $rOuter = 70;
        $rInner = 42;

        $total    = array_sum($data);
        $titleH   = $title ? 26 : 0;
        $height   = $titleH + $size;

        $svg = self::svgOpen($width, $height);
        if ($title) {
            $svg .= self::text($width / 2, 16, $title, self::TEXT_SECONDARY, 13, 'middle', true);
        }

        $colors = self::CATEGORICAL;
        $angle  = -90; // start at 12 o'clock
        $i      = 0;
        foreach ($data as $label => $value) {
            $sweep = ($value / $total) * 360;
            $color = $label === 'سایر' ? '#9ca3af' : ($colors[$i] ?? '#9ca3af');
            $svg .= self::donutSlice($cx, $cy, $rInner, $rOuter, $angle, $angle + $sweep, $color);
            $angle += $sweep;
            $i++;
        }

        // Center total (hero-figure style)
        $svg .= self::text($cx, $cy - 3, self::fmt($total), self::TEXT_PRIMARY, 16, 'middle', true);
        $svg .= self::text($cx, $cy + 13, 'مجموع', self::TEXT_MUTED, 9, 'middle');

        // Legend to the right: swatch + label + value + percentage
        $lx = $size + 30;
        $ly = $titleH + 18;
        $i  = 0;
        foreach ($data as $label => $value) {
            $color = $label === 'سایر' ? '#9ca3af' : ($colors[$i] ?? '#9ca3af');
            $pct   = round(($value / $total) * 100);
            $svg  .= '<rect x="' . self::n($lx) . '" y="' . self::n($ly - 9) . '" width="10" height="10" rx="2" fill="' . $color . '"/>';
            $svg  .= self::text($lx + 16, $ly, (string) $label . ' — ' . self::fmt($value) . ' (' . $pct . '%)', self::TEXT_SECONDARY, 11, 'start');
            $ly   += 20;
            $i++;
        }

        $svg .= '</svg>';
        return $svg;
    }

    // ─── SVG primitives ────────────────────────────────────────────────────────

    private static function svgOpen(float $width, float $height): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . self::n($width) . '" height="' . self::n($height)
            . '" viewBox="0 0 ' . self::n($width) . ' ' . self::n($height) . '" style="font-family: vazirmatn, sans-serif;">';
    }

    private static function emptyState(string $message): string
    {
        $svg  = self::svgOpen(640, 80);
        $svg .= self::text(320, 44, $message, self::TEXT_MUTED, 12, 'middle');
        $svg .= '</svg>';
        return $svg;
    }

    private static function text(float $x, float $y, string $content, string $color, int $size, string $anchor = 'start', bool $bold = false): string
    {
        return '<text x="' . self::n($x) . '" y="' . self::n($y) . '" font-size="' . $size . '" fill="' . $color . '" text-anchor="' . $anchor . '"'
            . ($bold ? ' font-weight="600"' : '') . '>' . self::esc($content) . '</text>';
    }

    /** Rectangle rounded only on the far (right) end — square at the baseline, per bar/column mark spec. */
    private static function roundedEndRect(float $x, float $y, float $w, float $h, float $r, string $color): string
    {
        $r = min($r, $w / 2, $h / 2);
        if ($r <= 0) {
            return '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="' . $color . '"/>';
        }
        $x2   = $x + $w;
        $y2   = $y + $h;
        $rStr = self::n($r);
        $d = 'M ' . self::n($x) . ',' . self::n($y) . ' '
            . 'L ' . self::n($x2 - $r) . ',' . self::n($y) . ' '
            . 'A ' . $rStr . ',' . $rStr . ' 0 0 1 ' . self::n($x2) . ',' . self::n($y + $r) . ' '
            . 'L ' . self::n($x2) . ',' . self::n($y2 - $r) . ' '
            . 'A ' . $rStr . ',' . $rStr . ' 0 0 1 ' . self::n($x2 - $r) . ',' . self::n($y2) . ' '
            . 'L ' . self::n($x) . ',' . self::n($y2) . ' Z';
        return '<path d="' . $d . '" fill="' . $color . '"/>';
    }

    private static function donutSlice(float $cx, float $cy, float $rInner, float $rOuter, float $a0, float $a1, string $color): string
    {
        $large = ($a1 - $a0) > 180 ? 1 : 0;
        [$x0, $y0] = self::polar($cx, $cy, $rOuter, $a0);
        [$x1, $y1] = self::polar($cx, $cy, $rOuter, $a1);
        [$x2, $y2] = self::polar($cx, $cy, $rInner, $a1);
        [$x3, $y3] = self::polar($cx, $cy, $rInner, $a0);

        $d = "M " . self::n($x0) . "," . self::n($y0) . " "
            . "A {$rOuter},{$rOuter} 0 {$large} 1 " . self::n($x1) . "," . self::n($y1) . " "
            . "L " . self::n($x2) . "," . self::n($y2) . " "
            . "A {$rInner},{$rInner} 0 {$large} 0 " . self::n($x3) . "," . self::n($y3) . " Z";

        // 2px surface-color ring so adjacent slices read as distinct (spec: spacer, not a stroke).
        return '<path d="' . $d . '" fill="' . $color . '" stroke="#ffffff" stroke-width="2"/>';
    }

    private static function polar(float $cx, float $cy, float $r, float $angleDeg): array
    {
        $rad = deg2rad($angleDeg);
        return [$cx + $r * cos($rad), $cy + $r * sin($rad)];
    }

    private static function labelColumnWidth(array $data): int
    {
        $longest = 0;
        foreach (array_keys($data) as $label) {
            $longest = max($longest, self::estimateTextWidth((string) $label, 11));
        }
        return (int) min(180, max(60, $longest + 10));
    }

    private static function estimateTextWidth(string $text, int $fontSize): float
    {
        return mb_strlen($text) * $fontSize * 0.62;
    }

    private static function niceStep(float $max): float
    {
        $roughStep = $max / 4;
        $magnitude = 10 ** floor(log10(max($roughStep, 1)));
        $residual  = $roughStep / $magnitude;

        $niceResidual = match (true) {
            $residual >= 5 => 10,
            $residual >= 2 => 5,
            $residual >= 1 => 2,
            default        => 1,
        };

        return max(1, $niceResidual * $magnitude);
    }

    private static function fmt(float $value): string
    {
        return number_format($value, $value == (int) $value ? 0 : 1);
    }

    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
