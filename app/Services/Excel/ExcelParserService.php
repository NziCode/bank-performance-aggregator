<?php

namespace App\Services\Excel;

use App\Exceptions\InvalidExcelStructureException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParserService
{
    private const SHEET_PERFORMANCE  = 'عملکرد';
    private const DATA_START_ROW = 3;

    public function parse(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new InvalidExcelStructureException('خواندن فایل امکان‌پذیر نیست: ' . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($rowIndex = self::DATA_START_ROW; $rowIndex <= $highestRow; $rowIndex++) {
            $raw = $this->getRowValues($sheet, $rowIndex, 12);

            if ($this->isEmptyRow($raw)) {
                continue;
            }

            $rows[] = [
                'row_number'        => $rowIndex,
                'date'              => $this->clean($raw[0]),
                'branch_code'       => $this->clean($raw[1]),
                'personnel_code'    => $this->clean($raw[2]),
                'service_type'      => $this->clean($raw[3]),
                'customer_account'  => $this->clean($raw[4]),
                'customer_name'     => $this->clean($raw[5]),
                'terminal_number'   => $this->clean($raw[6]),
                'colleague_account' => $this->clean($raw[7]),
                'notes'             => $this->clean($raw[8]),
            ];
        }

        return $rows;
    }

    private function getRowValues($sheet, int $rowIndex, int $colCount): array
    {
        $values = [];
        for ($col = 1; $col <= $colCount; $col++) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $rowIndex;
            $values[$col - 1] = $sheet->getCell($coordinate)->getValue();
        }
        return $values;
    }

    private function isEmptyRow(array $row): bool
    {
        return empty(trim((string) $row[2])) && empty(trim((string) $row[4]));
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/[\r\n\t]+/', ' ', (string) ($value ?? '')));
    }
}
