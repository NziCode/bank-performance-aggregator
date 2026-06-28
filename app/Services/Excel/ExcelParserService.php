<?php

namespace App\Services\Excel;

use App\Exceptions\InvalidExcelStructureException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParserService
{
    private const SHEET_BANKING  = 'خدمات نوین';
    private const SHEET_POS      = 'پایش پایانه های فروش';
    private const DATA_START_ROW = 3;

    private const VALID_ACCOUNT_PREFIXES = ['01', '02', '03', '04', '08'];

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
        $rows       = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowIndex = self::DATA_START_ROW; $rowIndex <= $highestRow; $rowIndex++) {
            $raw = $this->getRowValues($sheet, $rowIndex, 11);

            if ($this->isEmpty($raw[5]) && $this->isEmpty($raw[8])) {
                continue;
            }

            $rows[] = [
                'row_number'       => $rowIndex,
                'date'             => $this->clean($raw[1]),
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
            $cell = $sheet->getCell($coordinate);
            // مقدار رو به string تبدیل کن تا صفر اول حذف نشه
            $values[$col - 1] = $cell->getFormattedValue();
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

    public function normalizeAccount(mixed $value): string
    {
        if ($this->isEmpty($value)) return '';

        // حذف فاصله و خط تیره
        $clean = preg_replace('/[\s\-]/', '', (string) $value);

        // فقط اعداد بمونن
        $clean = preg_replace('/[^0-9]/', '', $clean);

        if (empty($clean)) return '';

        // اگه 12 رقمه، 0 اول رو اضافه کن
        if (strlen($clean) === 12) {
            $clean = '0' . $clean;
        }

        // اگه 13 رقم نشد، نامعتبره — برمیگردونیم تا validation خطا بده
        if (strlen($clean) !== 13) {
            return $clean;
        }

        // بررسی پیشوند معتبر
        $prefix = substr($clean, 0, 2);
        if (! in_array($prefix, self::VALID_ACCOUNT_PREFIXES)) {
            return $clean; // برمیگردونیم تا validation خطا بده
        }

        return $clean;
    }

    public function isValidAccount(string $account): bool
    {
        if (strlen($account) !== 13) return false;

        $prefix = substr($account, 0, 2);
        return in_array($prefix, self::VALID_ACCOUNT_PREFIXES);
    }
}
