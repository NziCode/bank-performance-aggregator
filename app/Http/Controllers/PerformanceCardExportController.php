<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchOffice;
use App\Models\StaffUnit;
use App\Models\User;
use App\Models\Zone;
use App\Services\PdfService;
use App\Services\Report\PerformanceReportService;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class PerformanceCardExportController extends Controller
{
    private string $preparedBy = 'واحد بازاریابی استان';

    public function __invoke(Request $request, PerformanceReportService $service): Response
    {
        $request->validate([
            'level'       => ['required', 'string'],
            'entity_id'   => ['required_unless:level,province'],
            'from'        => ['required', 'date'],
            'to'          => ['required', 'date'],
            'report_type' => ['required', 'in:summary,detailed,no_performance'],
            'format'      => ['required', 'in:excel,pdf'],
        ]);

        $level           = $request->level;
        $entityId        = $request->entity_id ?? 'all';
        $from            = $request->from;
        $to              = $request->to;
        $includeSubOff   = $request->boolean('include_sub_offices');
        $breakdownBy     = $request->breakdown_by;
        $serviceTypeIds  = array_filter((array) $request->input('service_type_ids', []));
        $reportType      = $request->report_type;
        $format          = $request->format;

        $entityLabel = $this->resolveEntityLabel($level, $entityId);
        $levelLabel  = PerformanceReportService::levelLabels()[$level] ?? $level;

        // ─── breakdown summary ─────────────────────────────────────────────────
        $breakdownKeys = array_filter((array) $request->input('breakdown_by', []));
        if ($reportType === 'summary' && !empty($breakdownKeys)) {
            $results = [];
            foreach ($breakdownKeys as $bd) {
                $results[] = $service->breakdownSummary($level, $entityId, $from, $to, $bd, $serviceTypeIds);
            }
            return $format === 'pdf'
                ? $this->breakdownPdf($results, $entityLabel, $levelLabel, $entityId, $from, $to)
                : $this->breakdownExcel($results, $entityLabel, $levelLabel, $entityId, $from, $to);
        }

        // ─── normal summary ────────────────────────────────────────────────────
        if ($reportType === 'summary') {
            $result = $service->summary($level, $entityId, $from, $to, $includeSubOff, $serviceTypeIds);
            return $format === 'pdf'
                ? $this->summaryPdf($result, $entityLabel, $levelLabel, $entityId, $from, $to)
                : $this->summaryExcel($result, $entityLabel, $levelLabel, $entityId, $from, $to);
        }

        // ─── no performance ────────────────────────────────────────────────────
        if ($reportType === 'no_performance') {
            $bk      = array_filter((array) $request->input('breakdown_by', []));
            $checkBy = !empty($bk) ? reset($bk) : 'employee';
            $result  = $service->noPerformance($level, $entityId, $from, $to, $serviceTypeIds, $checkBy);
            return $format === 'pdf'
                ? $this->noPerformancePdf($result, $entityLabel, $levelLabel, $entityId, $from, $to)
                : $this->noPerformanceExcel($result, $entityLabel, $levelLabel, $entityId, $from, $to);
        }

        // ─── detailed ──────────────────────────────────────────────────────────
        $records = $service->detailed($level, $entityId, $from, $to, $includeSubOff, $serviceTypeIds);
        return $format === 'pdf'
            ? $this->detailedPdf($records, $entityLabel, $levelLabel, $entityId, $from, $to)
            : $this->detailedExcel($records, $entityLabel, $levelLabel, $entityId, $from, $to);
    }

    // ─── PDF ───────────────────────────────────────────────────────────────────

    private function breakdownPdf(array $results, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $html = view('exports.performance-card-breakdown-pdf', [
            'results'     => $results,
            'entityLabel' => $entityLabel,
            'levelLabel'  => $levelLabel,
            'entityId'    => $entityId,
            'fromJalali'  => $this->jalali($from),
            'toJalali'    => $this->jalali($to),
            'reportDate'  => $this->jalali(now()->toDateString()),
            'preparedBy'  => $this->preparedBy,
        ])->render();

        return PdfService::download(
            PdfService::make($html, 'L'),
            'performance-card-breakdown.pdf'
        );
    }

    private function summaryPdf(array $result, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $html = view('exports.performance-card-summary-pdf', [
            'result'      => $result,
            'entityLabel' => $entityLabel,
            'levelLabel'  => $levelLabel,
            'entityId'    => $entityId,
            'fromJalali'  => $this->jalali($from),
            'toJalali'    => $this->jalali($to),
            'reportDate'  => $this->jalali(now()->toDateString()),
            'preparedBy'  => $this->preparedBy,
        ])->render();

        return PdfService::download(
            PdfService::make($html, 'L'),
            'performance-card-summary.pdf'
        );
    }

    private function detailedPdf($records, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $html = view('exports.performance-card-detailed-pdf', [
            'records'     => $records,
            'entityLabel' => $entityLabel,
            'levelLabel'  => $levelLabel,
            'entityId'    => $entityId,
            'fromJalali'  => $this->jalali($from),
            'toJalali'    => $this->jalali($to),
            'reportDate'  => $this->jalali(now()->toDateString()),
            'preparedBy'  => $this->preparedBy,
        ])->render();

        return PdfService::download(
            PdfService::make($html, 'L'),
            'performance-card-detailed.pdf'
        );
    }

    // ─── Excel ─────────────────────────────────────────────────────────────────

    private function breakdownExcel(array $results, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('ریزبندی');

        $maxServiceCount = max(array_map(fn($r) => count($r['service_types']), $results));
        $totalCols       = 1 + $maxServiceCount * 4 + 4;
        $lastLetter      = Coordinate::stringFromColumnIndex($totalCols);

        $labels = implode('، ', array_column($results, 'breakdown_label'));
        $sheet->getCell('A1')->setValue('کارنامه — ' . $levelLabel . ': ' . $entityLabel . ' — ریزبندی: ' . $labels);
        $sheet->mergeCells('A1:' . $lastLetter . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->getCell('A2')->setValue('بازه گزارش: ' . $this->jalali($from) . ' تا ' . $this->jalali($to) . '     تاریخ اخذ: ' . $this->jalali(now()->toDateString()));
        $sheet->mergeCells('A2:' . $lastLetter . '2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6b7280');

        $row = 4;
        foreach ($results as $result) {
            $serviceCount = count($result['service_types']);
            $secCols      = 1 + $serviceCount * 4 + 4;
            $secLetter    = Coordinate::stringFromColumnIndex($secCols);

            // Section title
            $sheet->getCell('A' . $row)->setValue('آمار به تفکیک ' . $result['breakdown_label']);
            $sheet->mergeCells('A' . $row . ':' . $secLetter . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('dbeafe');
            $row++;

            $hr1 = $row;
            $hr2 = $row + 1;

            $sheet->mergeCells('A' . $hr1 . ':A' . $hr2);
            $sheet->getCell('A' . $hr1)->setValue($result['breakdown_label']);

            $col = 2;
            foreach ($result['service_types'] as $type) {
                $s = Coordinate::stringFromColumnIndex($col);
                $e = Coordinate::stringFromColumnIndex($col + 3);
                $sheet->mergeCells($s . $hr1 . ':' . $e . $hr1);
                $sheet->getCell($s . $hr1)->setValue($type);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $hr2)->setValue('کل');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $hr2)->setValue('تایید');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $hr2)->setValue('انتظار');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $hr2)->setValue('رد');
                $col += 4;
            }
            $s = Coordinate::stringFromColumnIndex($col);
            $e = Coordinate::stringFromColumnIndex($col + 3);
            $sheet->mergeCells($s . $hr1 . ':' . $e . $hr1);
            $sheet->getCell($s . $hr1)->setValue('جمع کل');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $hr2)->setValue('کل');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $hr2)->setValue('تایید');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $hr2)->setValue('انتظار');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $hr2)->setValue('رد');

            $this->styleHeader($sheet, $hr1, $secCols);
            $this->styleHeader($sheet, $hr2, $secCols);
            $sheet->getStyle('A' . $hr1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row = $hr2 + 1;
            foreach ($result['rows'] as $idx => $breakRow) {
                $sheet->getCell('A' . $row)->setValue($breakRow['label']);
                $col = 2;
                foreach ($result['service_types'] as $type) {
                    $cell = $breakRow['services'][$type];
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($cell['all']);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($cell[2]);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($cell[1]);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($cell[3]);
                    $col += 4;
                }
                $gt = $breakRow['grand_total'];
                $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($gt['all']);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($gt[2]);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($gt[1]);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($gt[3]);
                if ($idx % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $secLetter . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f9fafb');
                }
                $row++;
            }

            // Total row for this section
            $sheet->getCell('A' . $row)->setValue('جمع کل');
            $col = 2;
            foreach ($result['service_types'] as $type) {
                $totAll = array_sum(array_column(array_map(fn($r) => $r['services'][$type], $result['rows']), 'all'));
                $totApp = array_sum(array_column(array_map(fn($r) => $r['services'][$type], $result['rows']), 2));
                $totPen = array_sum(array_column(array_map(fn($r) => $r['services'][$type], $result['rows']), 1));
                $totRej = array_sum(array_column(array_map(fn($r) => $r['services'][$type], $result['rows']), 3));
                $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($totAll);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($totApp);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($totPen);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($totRej);
                $col += 4;
            }
            $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($result['grand_total']['all']);
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($result['grand_total'][2]);
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($result['grand_total'][1]);
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($result['grand_total'][3]);
            $sheet->getStyle('A' . $row . ':' . $secLetter . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':' . $secLetter . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');

            $row += 2; // blank row between sections
        }

        foreach (range(1, $totalCols) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $this->streamExcel($spreadsheet, 'performance-card-breakdown.xlsx');
    }

    private function summaryExcel(array $result, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('کارنامه کلی');

        // ─── اطلاعات گزارش ───
        $sheet->getCell('A1')->setValue('کارنامه کلی عملکرد — ' . $levelLabel . ': ' . $entityLabel . ' (کد: ' . $entityId . ')');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->getCell('A2')->setValue('بازه گزارش: ' . $this->jalali($from) . ' تا ' . $this->jalali($to));
        $sheet->mergeCells('A2:E2');

        $sheet->getCell('A3')->setValue('تاریخ اخذ گزارش: ' . $this->jalali(now()->toDateString()) . '     تهیه‌کننده: ' . $this->preparedBy);
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6b7280');

        // ─── خلاصه جمع کل ───
        $sheet->getCell('A4')->setValue(
            'مجموع کل: ' . $result['grand_total']['all'] .
            '    تایید: ' . $result['grand_total'][2] .
            '    انتظار: ' . $result['grand_total'][1] .
            '    رد: ' . $result['grand_total'][3]
        );
        $sheet->mergeCells('A4:E4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');

        // ─── جمع به تفکیک نوع خدمت (ردیف = نوع خدمت) ───
        $sheet->getCell('A5')->setValue('جمع کل به تفکیک نوع خدمت');
        $sheet->mergeCells('A5:E5');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);

        $headerRow = 6;
        $sheet->getCell('A' . $headerRow)->setValue('نوع خدمت');
        $sheet->getCell('B' . $headerRow)->setValue('کل');
        $sheet->getCell('C' . $headerRow)->setValue('تایید');
        $sheet->getCell('D' . $headerRow)->setValue('انتظار');
        $sheet->getCell('E' . $headerRow)->setValue('رد');
        $this->styleHeader($sheet, $headerRow, 5);
        $sheet->getStyle('A' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $row = $headerRow + 1;
        foreach ($result['service_types'] as $i => $type) {
            $cell = $result['totals'][$type];
            $sheet->getCell('A' . $row)->setValue($type);
            $sheet->getCell('B' . $row)->setValue($cell['all']);
            $sheet->getCell('C' . $row)->setValue($cell[2]);
            $sheet->getCell('D' . $row)->setValue($cell[1]);
            $sheet->getCell('E' . $row)->setValue($cell[3]);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            if ($i % 2 === 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f9fafb');
            }
            $row++;
        }

        // ردیف جمع کل
        $sheet->getCell('A' . $row)->setValue('جمع کل');
        $sheet->getCell('B' . $row)->setValue($result['grand_total']['all']);
        $sheet->getCell('C' . $row)->setValue($result['grand_total'][2]);
        $sheet->getCell('D' . $row)->setValue($result['grand_total'][1]);
        $sheet->getCell('E' . $row)->setValue($result['grand_total'][3]);
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');

        // ─── ریز همکاران ───
        if (count($result['by_employee']) > 1) {
            $serviceCount  = count($result['service_types']);
            $empTotalCols  = 3 + $serviceCount * 4 + 4; // code + name + workplace + (4 per service) + 4 grand total
            $empLastLetter = Coordinate::stringFromColumnIndex($empTotalCols);

            $row += 2;
            $sheet->getCell('A' . $row)->setValue('ریز عملکرد به تفکیک همکار');
            $sheet->mergeCells('A' . $row . ':' . $empLastLetter . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);

            $hr1 = $row + 1;
            $hr2 = $row + 2;

            $sheet->mergeCells('A' . $hr1 . ':A' . $hr2);
            $sheet->getCell('A' . $hr1)->setValue('کد پرسنلی');
            $sheet->mergeCells('B' . $hr1 . ':B' . $hr2);
            $sheet->getCell('B' . $hr1)->setValue('نام همکار');
            $sheet->mergeCells('C' . $hr1 . ':C' . $hr2);
            $sheet->getCell('C' . $hr1)->setValue('محل خدمت');

            $col = 4;
            foreach ($result['service_types'] as $type) {
                $s = Coordinate::stringFromColumnIndex($col);
                $e = Coordinate::stringFromColumnIndex($col + 3);
                $sheet->mergeCells($s . $hr1 . ':' . $e . $hr1);
                $sheet->getCell($s . $hr1)->setValue($type);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $hr2)->setValue('کل');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $hr2)->setValue('تایید');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $hr2)->setValue('انتظار');
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $hr2)->setValue('رد');
                $col += 4;
            }
            $s = Coordinate::stringFromColumnIndex($col);
            $e = Coordinate::stringFromColumnIndex($col + 3);
            $sheet->mergeCells($s . $hr1 . ':' . $e . $hr1);
            $sheet->getCell($s . $hr1)->setValue('جمع');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $hr2)->setValue('کل');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $hr2)->setValue('تایید');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $hr2)->setValue('انتظار');
            $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $hr2)->setValue('رد');

            $this->styleHeader($sheet, $hr1, $empTotalCols);
            $this->styleHeader($sheet, $hr2, $empTotalCols);
            $sheet->getStyle('A' . $hr1 . ':C' . $hr1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row = $hr2 + 1;
            foreach ($result['by_employee'] as $idx => $emp) {
                $sheet->getCell('A' . $row)->setValue($emp['personnel_code']);
                $sheet->getCell('B' . $row)->setValue($emp['full_name']);
                $sheet->getCell('C' . $row)->setValue($emp['workplace']);
                $col = 4;
                foreach ($result['service_types'] as $type) {
                    $cell = $emp['services'][$type];
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($cell['all']);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($cell[2]);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($cell[1]);
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($cell[3]);
                    $col += 4;
                }
                $sheet->getCell(Coordinate::stringFromColumnIndex($col)   . $row)->setValue($emp['total']['all']);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+1) . $row)->setValue($emp['total'][2]);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+2) . $row)->setValue($emp['total'][1]);
                $sheet->getCell(Coordinate::stringFromColumnIndex($col+3) . $row)->setValue($emp['total'][3]);
                if ($idx % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $empLastLetter . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f9fafb');
                }
                $row++;
            }

            foreach (range(1, $empTotalCols) as $c) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
            }
        } else {
            foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return $this->streamExcel($spreadsheet, 'performance-card-summary.xlsx');
    }

    private function detailedExcel($records, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('کارنامه جزئی');

        $sheet->getCell('A1')->setValue('کارنامه جزئی عملکرد — ' . $levelLabel . ': ' . $entityLabel . ' (کد: ' . $entityId . ')');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->getCell('A2')->setValue('بازه گزارش: ' . $this->jalali($from) . ' تا ' . $this->jalali($to));
        $sheet->mergeCells('A2:F2');

        $sheet->getCell('A3')->setValue('تاریخ اخذ گزارش: ' . $this->jalali(now()->toDateString()) . '     تهیه‌کننده: ' . $this->preparedBy);
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6b7280');

        $headerRow = 5;
        $headers = ['A' => 'تاریخ', 'B' => 'همکار', 'C' => 'نوع خدمت', 'D' => 'نام مشتری', 'E' => 'شماره حساب', 'F' => 'وضعیت'];
        foreach ($headers as $col => $label) {
            $sheet->getCell($col . $headerRow)->setValue($label);
        }
        $this->styleHeader($sheet, $headerRow, 6);

        $row = $headerRow + 1;
        foreach ($records as $r) {
            $sheet->getCell('A' . $row)->setValue(Jalalian::fromCarbon($r->date)->format('Y/m/d'));
            $sheet->getCell('B' . $row)->setValue($r->employee?->full_name);
            $sheet->getCell('C' . $row)->setValue($r->serviceType?->name);
            $sheet->getCell('D' . $row)->setValue($r->customer_name);
            $sheet->getCell('E' . $row)->setValue($r->customer_account);
            $sheet->getCell('F' . $row)->setValue($r->validationStatus?->name);
            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamExcel($spreadsheet, 'performance-card-detailed.xlsx');
    }

    private function noPerformancePdf(array $result, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $html = view('exports.performance-card-no-performance-pdf', [
            'result'      => $result,
            'entityLabel' => $entityLabel,
            'levelLabel'  => $levelLabel,
            'entityId'    => $entityId,
            'fromJalali'  => $this->jalali($from),
            'toJalali'    => $this->jalali($to),
            'reportDate'  => $this->jalali(now()->toDateString()),
            'preparedBy'  => $this->preparedBy,
        ])->render();

        return PdfService::download(
            PdfService::make($html, 'P'),
            'performance-card-no-performance.pdf'
        );
    }

    private function noPerformanceExcel(array $result, ?string $entityLabel, string $levelLabel, $entityId, string $from, string $to): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('فاقد عملکرد');

        $checkBy   = $result['check_by'] ?? 'employee';
        $entities  = $result['entities'] ?? [];
        $countUnit = match ($checkBy) { 'branch' => 'شعبه', 'branch_office' => 'باجه', 'zone' => 'حوزه', default => 'نفر' };
        $col1Label = match ($checkBy) { 'branch' => 'کد شعبه', 'branch_office' => 'کد شعبه', 'zone' => 'کد حوزه', default => 'کد پرسنلی' };
        $col2Label = match ($checkBy) { 'branch' => 'نام شعبه', 'branch_office' => 'نام باجه', 'zone' => 'نام حوزه', default => 'نام همکار' };
        $col3Label = match ($checkBy) { 'branch' => 'حوزه', 'branch_office' => 'شعبه مادر', default => 'محل خدمت فعلی' };
        $hasExtra  = $checkBy !== 'zone';
        $totalCols = $hasExtra ? 4 : 3;
        $lastCol   = Coordinate::stringFromColumnIndex($totalCols);

        $sheet->getCell('A1')->setValue('فاقد عملکرد — ' . $levelLabel . ': ' . $entityLabel . ' (کد: ' . $entityId . ')');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->getCell('A2')->setValue('بازه گزارش: ' . $this->jalali($from) . ' تا ' . $this->jalali($to));
        $sheet->mergeCells('A2:' . $lastCol . '2');

        $sheet->getCell('A3')->setValue('تعداد فاقد عملکرد: ' . $result['count'] . ' ' . $countUnit . '     تاریخ اخذ: ' . $this->jalali(now()->toDateString()));
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6b7280');

        $headerRow = 5;
        $sheet->getCell('A' . $headerRow)->setValue('#');
        $sheet->getCell('B' . $headerRow)->setValue($col1Label);
        $sheet->getCell('C' . $headerRow)->setValue($col2Label);
        if ($hasExtra) {
            $sheet->getCell('D' . $headerRow)->setValue($col3Label);
        }
        $this->styleHeader($sheet, $headerRow, $totalCols);
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7f1d1d']],
        ]);
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $row = $headerRow + 1;
        foreach ($entities as $idx => $item) {
            $sheet->getCell('A' . $row)->setValue($idx + 1);
            $sheet->getCell('B' . $row)->setValue($item['code']);
            $sheet->getCell('C' . $row)->setValue($item['name']);
            if ($hasExtra) {
                $sheet->getCell('D' . $row)->setValue($item['extra']);
            }
            if ($idx % 2 === 0) {
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('fff5f5');
            }
            $row++;
        }

        foreach (range(1, $totalCols) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return $this->streamExcel($spreadsheet, 'performance-card-no-performance.xlsx');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function styleHeader(object $sheet, int $row, int $lastCol): void
    {
        $range = 'A' . $row . ':' . Coordinate::stringFromColumnIndex($lastCol) . $row;
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function fmtCell(array $cell): string
    {
        return "{$cell['all']} ({$cell[2]} / {$cell[1]} / {$cell[3]})";
    }

    private function jalali(string $date): string
    {
        return Jalalian::fromDateTime(new \DateTime($date))->format('Y/m/d');
    }

    private function streamExcel(Spreadsheet $spreadsheet, string $filename): Response
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function resolveEntityLabel(string $level, $entityId): ?string
    {
        return match ($level) {
            'province'      => 'کل استان',
            'employee'      => User::where('personnel_code', $entityId)->first()?->full_name,
            'branch'        => Branch::where('code', $entityId)->first()?->name,
            'branch_office' => BranchOffice::find($entityId)?->name,
            'zone'          => Zone::where('code', $entityId)->first()?->name,
            'staff'         => StaffUnit::where('code', $entityId)->first()?->name,
            default         => null,
        };
    }
}
