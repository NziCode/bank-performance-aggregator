<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function performances(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $performances = Performance::approved()
            ->forPeriod($request->from, $request->to)
            ->with(['employee', 'branch', 'serviceType', 'validator'])
            ->when($request->filled('branch_code'), fn($q) => $q->forBranch($request->branch_code))
            ->when($request->filled('personnel_code'), fn($q) => $q->forEmployee($request->personnel_code))
            ->orderBy('branch_code')
            ->orderBy('personnel_code')
            ->orderBy('date')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('عملکرد');

        // هدر
        $headers = [
            'ردیف', 'تاریخ', 'کد شعبه', 'نام شعبه',
            'کد پرسنلی', 'نام همکار', 'نوع خدمت',
            'شماره حساب مشتری', 'نام مشتری',
            'شماره پایانه', 'شماره حساب همکار',
            'توضیحات', 'تایید کننده', 'تاریخ تایید',
        ];

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // استایل هدر
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1e3a5f'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // داده‌ها
        $row = 2;
        foreach ($performances as $index => $p) {
            $sheet->setCellValueByColumnAndRow(1,  $row, $index + 1);
            $sheet->setCellValueByColumnAndRow(2,  $row, $p->date->format('Y/m/d'));
            $sheet->setCellValueByColumnAndRow(3,  $row, $p->branch_code);
            $sheet->setCellValueByColumnAndRow(4,  $row, $p->branch?->name);
            $sheet->setCellValueByColumnAndRow(5,  $row, $p->personnel_code);
            $sheet->setCellValueByColumnAndRow(6,  $row, $p->employee?->full_name);
            $sheet->setCellValueByColumnAndRow(7,  $row, $p->serviceType?->name);
            $sheet->setCellValueByColumnAndRow(8,  $row, $p->customer_account);
            $sheet->setCellValueByColumnAndRow(9,  $row, $p->customer_name);
            $sheet->setCellValueByColumnAndRow(10, $row, $p->terminal_number);
            $sheet->setCellValueByColumnAndRow(11, $row, $p->colleague_account);
            $sheet->setCellValueByColumnAndRow(12, $row, $p->notes);
            $sheet->setCellValueByColumnAndRow(13, $row, $p->validator?->full_name);
            $sheet->setCellValueByColumnAndRow(14, $row, $p->validated_at?->format('Y/m/d'));

            // رنگ‌بندی ردیف‌های زوج
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'f0f4f8'],
                    ],
                ]);
            }

            $row++;
        }

        // تنظیم عرض ستون‌ها
        foreach (range(1, 14) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = "performances_{$request->from}_{$request->to}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function byEmployee(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $data = Performance::approved()
            ->forPeriod($request->from, $request->to)
            ->with(['employee.branch', 'serviceType'])
            ->get()
            ->groupBy('personnel_code');

        $serviceTypes = \App\Models\ServiceType::pluck('name');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('عملکرد به تفکیک همکار');

        // هدر
        $headers = array_merge(
            ['ردیف', 'کد پرسنلی', 'نام همکار', 'شعبه'],
            $serviceTypes->toArray(),
            ['جمع کل']
        );

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1e3a5f'],
            ],
        ]);

        $row = 2;
        foreach ($data as $personnelCode => $records) {
            $employee = $records->first()->employee;
            $total    = $records->count();

            $sheet->setCellValueByColumnAndRow(1, $row, $row - 1);
            $sheet->setCellValueByColumnAndRow(2, $row, $personnelCode);
            $sheet->setCellValueByColumnAndRow(3, $row, $employee?->full_name);
            $sheet->setCellValueByColumnAndRow(4, $row, $employee?->branch?->name);

            $col = 5;
            foreach ($serviceTypes as $type) {
                $count = $records->where('serviceType.name', $type)->count();
                $sheet->setCellValueByColumnAndRow($col, $row, $count ?: 0);
                $col++;
            }

            $sheet->setCellValueByColumnAndRow($col, $row, $total);

            $row++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = "by_employee_{$request->from}_{$request->to}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
