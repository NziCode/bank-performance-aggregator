<?php

namespace App\Services\Excel;

use App\Exceptions\InvalidExcelStructureException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParserService
{
    private const SHEET_BANKING  = 'خدمات نوین';
    private const SHEET_POS      = 'پایش پایانه های فروش';
    private const HEADER_ROW     = 2; // سطر هدر (1-indexed)
    private const DATA_START_ROW = 3; // اولین سطر داده

    public function parse(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new InvalidExcelStructureException('خواندن فایل امکان‌پذیر نیست: ' . $e->getMessage());
        }

        $sheetNames = array_map(fn($s) => $s->getTitle(), $spreadsheet->getAllSheets());

        if (! in_array(self::SHEET_BANKING, $sheetNames)) {
            throw new InvalidExcelStructureException('شیت «خدمات نوین» در فایل یافت نشد');
        }

        if (! in_array(self::SHEET_POS, $sheetNames)) {
            throw new InvalidExcelStructureException('شیت «پایش پایانه های فروش» در فایل یافت نشد');
        }

        return [
            'banking_service' => $this->parseBankingSheet(
                $spreadsheet->getSheetByName(self::SHEET_BANKING)
            ),
            'pos_monitoring'  => $this->parsePosSheet(
                $spreadsheet->getSheetByName(self::SHEET_POS)
            ),
        ];
    }

    private function parseBankingSheet($sheet): array
    {
        // ستون‌ها (0-indexed):
        // 0:ردیف | 1:تاریخ | 2:کد حوزه | 3:کد شعبه | 4:نام شعبه
        // 5:کد کارمندی | 6:نام کارمند | 7:نوع خدمات
        // 8:شماره حساب مشتری | 9:نام مشتری | 10:توضیحات

        $rows       = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowIndex = self::DATA_START_ROW; $rowIndex <= $highestRow; $rowIndex++) {
            $raw = $this->getRowValues($sheet, $rowIndex, 11);

            // سطر خالی — کد کارمندی و شماره حساب هر دو خالی
            if ($this->isEmpty($raw[5]) && $this->isEmpty($raw[8])) {
                continue;
            }

            $rows[] = [
                'row_number'       => $rowIndex,
                'date'             => $this->clean($raw[1]),
                'zone'             => $this->clean($raw[2]),
                'branch_code'      => $this->clean($raw[3]),
                'branch_name'      => $this->clean($raw[4]),
                'personnel_code'   => $this->clean($raw[5]),
                'employee_name'    => $this->clean($raw[6]),
                'service_type'     => $this->clean($raw[7]),
                'customer_account' => $this->normalizeAccount($raw[8]),
                'customer_name'    => $this->clean($raw[9]),
                'notes'            => $this->clean($raw[10]),
            ];
        }

        return $rows;
    }

    private function parsePosSheet($sheet): array
    {
        // ستون‌ها (0-indexed):
        // 0:ردیف | 1:تاریخ | 2:کد حوزه | 3:کد شعبه | 4:نام شعبه
        // 5:کد کارمندی | 6:نام کارمند | 7:شماره پایانه
        // 8:شماره حساب مشتری | 9:نام مشتری | 10:شماره حساب همکار | 11:توضیحات

        $rows       = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowIndex = self::DATA_START_ROW; $rowIndex <= $highestRow; $rowIndex++) {
            $raw = $this->getRowValues($sheet, $rowIndex, 12);

            if ($this->isEmpty($raw[5]) && $this->isEmpty($raw[7])) {
                continue;
            }

            $rows[] = [
                'row_number'        => $rowIndex,
                'date'              => $this->clean($raw[1]),
                'zone'              => $this->clean($raw[2]),
                'branch_code'       => $this->clean($raw[3]),
                'branch_name'       => $this->clean($raw[4]),
                'personnel_code'    => $this->clean($raw[5]),
                'employee_name'     => $this->clean($raw[6]),
                'terminal_number'   => $this->clean($raw[7]),
                'customer_account'  => $this->normalizeAccount($raw[8]),
                'customer_name'     => $this->clean($raw[9]),
                'colleague_account' => $this->normalizeAccount($raw[10]),
                'notes'             => $this->clean($raw[11]),
            ];
        }

        return $rows;
    }

    private function getRowValues($sheet, int $rowIndex, int $colCount): array
    {
        $values = [];
        for ($col = 1; $col <= $colCount; $col++) {
            $coordinate = Coordinate::stringFromColumnIndex($col) . $rowIndex;
            $values[$col - 1] = $sheet->getCell($coordinate)->getValue();
        }
        return $values;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/[\r\n\t]+/', ' ', (string) ($value ?? '')));
    }

    private function normalizeAccount(mixed $value): string
    {
        if ($this->isEmpty($value)) return '';
        $clean = preg_replace('/[\s\-]/', '', (string) $value);
        return ltrim($clean, '0');
    }
}
