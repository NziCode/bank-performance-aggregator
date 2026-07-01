<?php

namespace App\Services\Report;

use App\Models\BranchOffice;
use App\Models\Performance;
use App\Models\ServiceType;
use Illuminate\Support\Collection;

class PerformanceReportService
{
    public const LEVEL_EMPLOYEE      = 'employee';
    public const LEVEL_BRANCH        = 'branch';
    public const LEVEL_BRANCH_OFFICE = 'branch_office';
    public const LEVEL_ZONE          = 'zone';
    public const LEVEL_STAFF         = 'staff';

    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    /**
     * گزارش جزئی — لیست خام رکوردهای عملکرد (همه وضعیت‌ها، با ستون وضعیت روی هر رکورد).
     */
    public function detailed(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false
    ): Collection {
        return $this->baseQuery($level, $entityId, $from, $to, $includeSubOffices)
            ->with(['employee', 'serviceType', 'validationStatus'])
            ->orderBy('date')
            ->get();
    }

    /**
     * گزارش کلی — برای هر نوع خدمت سه عدد جدا (تایید‌شده/در انتظار/رد‌شده) برمی‌گرداند،
     * به‌علاوه ریز عملکرد هر کارمند زیرمجموعه با همان تفکیک.
     */
    public function summary(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false
    ): array {
        $records = $this->baseQuery($level, $entityId, $from, $to, $includeSubOffices)
            ->with(['employee', 'serviceType'])
            ->get();

        $serviceTypes = ServiceType::pluck('name');

        // جمع کل واحد — به تفکیک نوع خدمت و وضعیت
        $totals = [];
        foreach ($serviceTypes as $type) {
            $rowsOfType = $records->where('serviceType.name', $type);
            $totals[$type] = $this->statusBreakdown($rowsOfType);
        }

        $grandTotal = [
            self::STATUS_APPROVED => $records->where('validation_status_id', self::STATUS_APPROVED)->count(),
            self::STATUS_PENDING  => $records->where('validation_status_id', self::STATUS_PENDING)->count(),
            self::STATUS_REJECTED => $records->where('validation_status_id', self::STATUS_REJECTED)->count(),
            'all'                 => $records->count(),
        ];

        // ریز به تفکیک هر کارمند زیرمجموعه — همان تفکیک وضعیت برای هر نوع خدمت
        $byEmployee = $records->groupBy('personnel_code')->map(function ($rows) use ($serviceTypes) {
            $first    = $rows->first();
            $services = [];

            foreach ($serviceTypes as $type) {
                $services[$type] = $this->statusBreakdown($rows->where('serviceType.name', $type));
            }

            return [
                'personnel_code' => $first->personnel_code,
                'full_name'      => $first->employee?->full_name,
                'workplace'      => $first->workplace_label,
                'services'       => $services,
                'total'          => [
                    self::STATUS_APPROVED => $rows->where('validation_status_id', self::STATUS_APPROVED)->count(),
                    self::STATUS_PENDING  => $rows->where('validation_status_id', self::STATUS_PENDING)->count(),
                    self::STATUS_REJECTED => $rows->where('validation_status_id', self::STATUS_REJECTED)->count(),
                    'all'                 => $rows->count(),
                ],
            ];
        })->sortByDesc(fn($row) => $row['total']['all'])->values();

        return [
            'service_types' => $serviceTypes->toArray(),
            'totals'        => $totals,
            'grand_total'   => $grandTotal,
            'by_employee'   => $byEmployee,
        ];
    }

    /**
     * تفکیک سه‌گانه یک مجموعه رکورد بر اساس وضعیت اعتبارسنجی.
     */
    private function statusBreakdown(Collection $rows): array
    {
        return [
            self::STATUS_APPROVED => $rows->where('validation_status_id', self::STATUS_APPROVED)->count(),
            self::STATUS_PENDING  => $rows->where('validation_status_id', self::STATUS_PENDING)->count(),
            self::STATUS_REJECTED => $rows->where('validation_status_id', self::STATUS_REJECTED)->count(),
            'all'                 => $rows->count(),
        ];
    }

    /**
     * Query پایه فیلتر شده بر اساس سطح سازمانی و بازه تاریخ — بدون فیلتر وضعیت
     * (همه رکوردها بازگردانده می‌شوند؛ تفکیک وضعیت در لایه نمایش/جمع‌بندی انجام می‌شود).
     */
    private function baseQuery(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false
    ) {
        $query = Performance::query()->whereBetween('date', [$from, $to]);

        return match ($level) {
            'province'           => $query,

            self::LEVEL_EMPLOYEE => $query->where('personnel_code', $entityId),

            self::LEVEL_BRANCH => $includeSubOffices
                ? $query->where(function ($q) use ($entityId) {
                    $officeIds = BranchOffice::where('branch_code', $entityId)->pluck('id');

                    $q->where(function ($qq) use ($entityId) {
                        $qq->where('workplace_type', 'branch')->where('branch_code', $entityId);
                    })->orWhere(function ($qq) use ($officeIds) {
                        $qq->where('workplace_type', 'branch_office')->whereIn('branch_office_id', $officeIds);
                    });
                })
                : $query->where('workplace_type', 'branch')->where('branch_code', $entityId),

            self::LEVEL_BRANCH_OFFICE => $query->where('workplace_type', 'branch_office')->where('branch_office_id', $entityId),
            self::LEVEL_ZONE          => $query->where('workplace_type', 'zone')->where('zone_code', $entityId),
            self::LEVEL_STAFF         => $query->where('workplace_type', 'staff')->where('staff_unit_code', $entityId),

            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function levelLabels(): array
    {
        return [
            'province'              => 'کل استان',
            self::LEVEL_EMPLOYEE      => 'کارمند',
            self::LEVEL_BRANCH        => 'شعبه',
            self::LEVEL_BRANCH_OFFICE => 'باجه',
            self::LEVEL_ZONE          => 'حوزه',
            self::LEVEL_STAFF         => 'واحد ستادی',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_APPROVED => 'تایید',
            self::STATUS_PENDING  => 'انتظار',
            self::STATUS_REJECTED => 'رد',
        ];
    }
}
