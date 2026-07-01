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
            'entity_id'   => ['required'],
            'from'        => ['required', 'date'],
            'to'          => ['required', 'date'],
            'report_type' => ['required', 'in:summary,detailed'],
            'format'      => ['required', 'in:excel,pdf'],
        ]);

        $level             = $request->level;
        $entityId          = $request->entity_id;
        $from              = $request->from;
        $to                = $request->to;
        $includeSubOffices = $request->boolean('include_sub_offices');
        $reportType        = $request->report_type;
        $format            = $request->format;

        $entityLabel = $this->resolveEntityLabel($level, $entityId);
        $levelLabel  = PerformanceReportService::levelLabels()[$level] ?? $level;

        if ($reportType === 'summary') {
            $result = $service->summary($level, $entityId, $from, $to, $includeSubOffices);
            return $format === 'pdf'
                ? $this->summaryPdf($result, $entityLabel, $levelLabel, $entityId, $from, $to)
                : $this->summaryExcel($result, $entityLabel, $levelLabel, $entityId, $from, $to);
        }

        $records = $service->detailed($level, $entityId, $from, $to, $includeSubOffices);
        return $format === 'pdf'
            ? $this->detailedPdf($records, $entityLabel, $levelLabel, $entityId, $from, $to)
            : $this->detailedExcel($records, $entityLabel, $levelLabel, $entityId, $from, $to);
    }

    // ─── PDF ───────────────────────────────────────────────────────────────────

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
            $empLastCol    = $serviceCount + 4; // A=کد، B=نام، C=محل + services + جمع
            $empLastLetter = Coordinate::stringFromColumnIndex($empLastCol);

            $row += 2;
            $sheet->getCell('A' . $row)->setValue('ریز عملکرد به تفکیک همکار');
            $sheet->mergeCells('A' . $row . ':' . $empLastLetter . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);

            $row++;
            $sheet->getCell('A' . $row)->setValue('کد پرسنلی');
            $sheet->getCell('B' . $row)->setValue('نام همکار');
            $sheet->getCell('C' . $row)->setValue('محل خدمت');
            $col = 4;
            foreach ($result['service_types'] as $type) {
                $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->setValue($type);
                $col++;
            }
            $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->setValue('جمع');
            $this->styleHeader($sheet, $row, $empLastCol);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            foreach ($result['by_employee'] as $idx => $emp) {
                $row++;
                $sheet->getCell('A' . $row)->setValue($emp['personnel_code']);
                $sheet->getCell('B' . $row)->setValue($emp['full_name']);
                $sheet->getCell('C' . $row)->setValue($emp['workplace']);
                $col = 4;
                foreach ($result['service_types'] as $type) {
                    $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->setValue($this->fmtCell($emp['services'][$type]));
                    $col++;
                }
                $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->setValue($emp['total']['all']);
                $sheet->getStyle(Coordinate::stringFromColumnIndex($col) . $row)->getFont()->setBold(true);
                if ($idx % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $empLastLetter . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f9fafb');
                }
            }

            foreach (range(1, $empLastCol) as $c) {
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
            'employee'      => User::where('personnel_code', $entityId)->first()?->full_name,
            'branch'        => Branch::where('code', $entityId)->first()?->name,
            'branch_office' => BranchOffice::find($entityId)?->name,
            'zone'          => Zone::where('code', $entityId)->first()?->name,
            'staff'         => StaffUnit::where('code', $entityId)->first()?->name,
            default         => null,
        };
    }
}
