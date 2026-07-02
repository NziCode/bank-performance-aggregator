<?php

namespace App\Services\Report;

use App\Models\Branch;
use App\Models\BranchOffice;
use App\Models\Performance;
use App\Models\ServiceType;
use App\Models\StaffUnit;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

class PerformanceReportService
{
    public const LEVEL_PROVINCE      = 'province';
    public const LEVEL_ZONE          = 'zone';
    public const LEVEL_BRANCH        = 'branch';
    public const LEVEL_BRANCH_OFFICE = 'branch_office';
    public const LEVEL_STAFF         = 'staff';
    public const LEVEL_EMPLOYEE      = 'employee';

    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    // ─── Date Range ────────────────────────────────────────────────────────────

    public static function resolveDateRange(string $period, ?string $from = null, ?string $to = null): array
    {
        $today = Carbon::today();
        $now   = Jalalian::now();

        return match ($period) {
            'today'         => [$today->toDateString(), $today->toDateString()],
            'yesterday'     => [Carbon::yesterday()->toDateString(), Carbon::yesterday()->toDateString()],
            'current_month' => [
                (new Jalalian($now->getYear(), $now->getMonth(), 1))->toCarbon()->toDateString(),
                (new Jalalian($now->getYear(), $now->getMonth(), self::jalaliMonthDays($now->getMonth(), $now->getYear())))->toCarbon()->toDateString(),
            ],
            'prev_month'    => self::prevMonthRange($now),
            'current_year'  => [
                (new Jalalian($now->getYear(), 1, 1))->toCarbon()->toDateString(),
                $today->toDateString(),
            ],
            'past_year'     => [
                $today->copy()->subYear()->toDateString(),
                $today->toDateString(),
            ],
            'custom'        => [$from, $to],
            default         => [$today->toDateString(), $today->toDateString()],
        };
    }

    private static function prevMonthRange(Jalalian $now): array
    {
        $month = $now->getMonth();
        $year  = $now->getYear();

        if ($month === 1) {
            $prevYear  = $year - 1;
            $prevMonth = 12;
        } else {
            $prevYear  = $year;
            $prevMonth = $month - 1;
        }

        return [
            (new Jalalian($prevYear, $prevMonth, 1))->toCarbon()->toDateString(),
            (new Jalalian($prevYear, $prevMonth, self::jalaliMonthDays($prevMonth, $prevYear)))->toCarbon()->toDateString(),
        ];
    }

    private static function jalaliMonthDays(int $month, int $year): int
    {
        if ($month <= 6) return 31;
        if ($month <= 11) return 30;
        return (new Jalalian($year, 1, 1))->isLeapYear() ? 30 : 29;
    }

    // ─── Detailed ──────────────────────────────────────────────────────────────

    public function detailed(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false,
        array $serviceTypeIds = []
    ): Collection {
        return $this->baseQuery($level, $entityId, $from, $to, $includeSubOffices, $serviceTypeIds)
            ->with(['employee', 'serviceType', 'validationStatus'])
            ->orderBy('date')
            ->get();
    }

    // ─── Summary ───────────────────────────────────────────────────────────────

    public function summary(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false,
        array $serviceTypeIds = []
    ): array {
        $records = $this->baseQuery($level, $entityId, $from, $to, $includeSubOffices, $serviceTypeIds)
            ->with(['employee', 'serviceType'])
            ->get();

        return $this->buildSummaryResult($records, $serviceTypeIds);
    }

    // ─── Breakdown Summary (کل به جز) ─────────────────────────────────────────

    public function breakdownSummary(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        string $breakdownBy,
        array $serviceTypeIds = []
    ): array {
        $records = $this->hierarchicalQuery($level, $entityId, $from, $to, $serviceTypeIds)
            ->with(['employee', 'serviceType', 'branch', 'branchOffice.branch', 'zone'])
            ->get();

        $serviceTypes = $this->resolveServiceTypes($serviceTypeIds);

        $grouped = $records
            ->groupBy(fn($r) => $this->resolveBreakdownKey($r, $breakdownBy))
            ->filter(fn($g, $k) => $k !== null);

        $rows = $grouped->map(function ($group, $key) use ($serviceTypes, $breakdownBy) {
            $services = [];
            foreach ($serviceTypes as $type) {
                $services[$type] = $this->statusBreakdown($group->where('serviceType.name', $type));
            }
            return [
                'key'         => $key,
                'label'       => $this->resolveBreakdownLabel($key, $breakdownBy, $group->first()),
                'services'    => $services,
                'grand_total' => [
                    self::STATUS_APPROVED => $group->where('validation_status_id', self::STATUS_APPROVED)->count(),
                    self::STATUS_PENDING  => $group->where('validation_status_id', self::STATUS_PENDING)->count(),
                    self::STATUS_REJECTED => $group->where('validation_status_id', self::STATUS_REJECTED)->count(),
                    'all'                 => $group->count(),
                ],
            ];
        })->sortByDesc(fn($r) => $r['grand_total']['all'])->values();

        $grandTotal = [
            self::STATUS_APPROVED => $records->where('validation_status_id', self::STATUS_APPROVED)->count(),
            self::STATUS_PENDING  => $records->where('validation_status_id', self::STATUS_PENDING)->count(),
            self::STATUS_REJECTED => $records->where('validation_status_id', self::STATUS_REJECTED)->count(),
            'all'                 => $records->count(),
        ];

        return [
            'service_types'   => $serviceTypes->toArray(),
            'breakdown_by'    => $breakdownBy,
            'breakdown_label' => self::breakdownLabels()[$breakdownBy] ?? $breakdownBy,
            'rows'            => $rows->toArray(),
            'grand_total'     => $grandTotal,
        ];
    }

    // ─── Base Query (مستقیم — برای summary/detailed عادی) ──────────────────────

    private function baseQuery(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        bool $includeSubOffices = false,
        array $serviceTypeIds = []
    ) {
        $query = Performance::query()->whereBetween('date', [$from, $to]);

        if (!empty($serviceTypeIds)) {
            $query->whereIn('service_type_id', $serviceTypeIds);
        }

        return match ($level) {
            self::LEVEL_PROVINCE => $query,

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

    // ─── Hierarchical Query (برای breakdown — شامل زیرمجموعه‌ها) ───────────────

    private function hierarchicalQuery(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        array $serviceTypeIds = []
    ) {
        $query = Performance::query()->whereBetween('date', [$from, $to]);

        if (!empty($serviceTypeIds)) {
            $query->whereIn('service_type_id', $serviceTypeIds);
        }

        switch ($level) {
            case self::LEVEL_PROVINCE:
                break;

            case self::LEVEL_ZONE:
                $branchCodes = Branch::where('zone_code', $entityId)->pluck('code');
                $officeIds   = BranchOffice::whereIn('branch_code', $branchCodes)->pluck('id');
                $query->where(function ($q) use ($entityId, $branchCodes, $officeIds) {
                    $q->where(function ($qq) use ($entityId) {
                        $qq->where('workplace_type', 'zone')->where('zone_code', $entityId);
                    })->orWhere(function ($qq) use ($branchCodes) {
                        $qq->where('workplace_type', 'branch')->whereIn('branch_code', $branchCodes);
                    })->orWhere(function ($qq) use ($officeIds) {
                        $qq->where('workplace_type', 'branch_office')->whereIn('branch_office_id', $officeIds);
                    });
                });
                break;

            case self::LEVEL_BRANCH:
                $officeIds = BranchOffice::where('branch_code', $entityId)->pluck('id');
                $query->where(function ($q) use ($entityId, $officeIds) {
                    $q->where(function ($qq) use ($entityId) {
                        $qq->where('workplace_type', 'branch')->where('branch_code', $entityId);
                    })->orWhere(function ($qq) use ($officeIds) {
                        $qq->where('workplace_type', 'branch_office')->whereIn('branch_office_id', $officeIds);
                    });
                });
                break;

            case self::LEVEL_BRANCH_OFFICE:
                $query->where('workplace_type', 'branch_office')->where('branch_office_id', $entityId);
                break;

            case self::LEVEL_STAFF:
                $query->where('workplace_type', 'staff')->where('staff_unit_code', $entityId);
                break;

            case self::LEVEL_EMPLOYEE:
                $query->where('personnel_code', $entityId);
                break;
        }

        return $query;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function buildSummaryResult(Collection $records, array $serviceTypeIds = []): array
    {
        $serviceTypes = $this->resolveServiceTypes($serviceTypeIds);

        $totals = [];
        foreach ($serviceTypes as $type) {
            $totals[$type] = $this->statusBreakdown($records->where('serviceType.name', $type));
        }

        $grandTotal = [
            self::STATUS_APPROVED => $records->where('validation_status_id', self::STATUS_APPROVED)->count(),
            self::STATUS_PENDING  => $records->where('validation_status_id', self::STATUS_PENDING)->count(),
            self::STATUS_REJECTED => $records->where('validation_status_id', self::STATUS_REJECTED)->count(),
            'all'                 => $records->count(),
        ];

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
        })->sortByDesc(fn($r) => $r['total']['all'])->values();

        return [
            'service_types' => $serviceTypes->toArray(),
            'totals'        => $totals,
            'grand_total'   => $grandTotal,
            'by_employee'   => $byEmployee,
        ];
    }

    private function resolveBreakdownKey($record, string $breakdownBy): mixed
    {
        return match ($breakdownBy) {
            'zone'          => $record->zone_code
                                ?? $record->branch?->zone_code
                                ?? $record->branchOffice?->branch?->zone_code,
            'branch'        => $record->branch_code
                                ?? $record->branchOffice?->branch_code,
            'branch_office' => $record->branch_office_id,
            'employee'      => $record->personnel_code,
            default         => null,
        };
    }

    private function resolveBreakdownLabel(mixed $key, string $breakdownBy, $sampleRecord): string
    {
        return match ($breakdownBy) {
            'zone'          => (Zone::where('code', $key)->value('name') ?? '—') . ' - ' . $key,
            'branch'        => (Branch::where('code', $key)->value('name') ?? '—') . ' - ' . $key,
            'branch_office' => (BranchOffice::find($key)?->name ?? '—') . ' - ' . $key,
            'employee'      => ($sampleRecord->employee?->full_name ?? '—') . ' - ' . $key,
            default         => (string) $key,
        };
    }

    private function statusBreakdown(Collection $rows): array
    {
        return [
            self::STATUS_APPROVED => $rows->where('validation_status_id', self::STATUS_APPROVED)->count(),
            self::STATUS_PENDING  => $rows->where('validation_status_id', self::STATUS_PENDING)->count(),
            self::STATUS_REJECTED => $rows->where('validation_status_id', self::STATUS_REJECTED)->count(),
            'all'                 => $rows->count(),
        ];
    }

    private function resolveServiceTypes(array $serviceTypeIds): Collection
    {
        return $serviceTypeIds
            ? ServiceType::whereIn('id', $serviceTypeIds)->pluck('name')
            : ServiceType::orderBy('id')->pluck('name');
    }

    // ─── No Performance ───────────────────────────────────────────────────────

    public function noPerformance(
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        array $serviceTypeIds = []
    ): array {
        $performingCodes = $this->hierarchicalQuery($level, $entityId, $from, $to, $serviceTypeIds)
            ->distinct()
            ->pluck('personnel_code')
            ->toArray();

        $userQuery = User::with(['branch', 'branchOffice', 'zone', 'staffUnit'])
            ->whereNotIn('personnel_code', $performingCodes);
        $this->scopeUsersToLevel($userQuery, $level, $entityId);

        $users = $userQuery->orderBy('personnel_code')->get();

        return [
            'count'     => $users->count(),
            'employees' => $users->map(fn($u) => [
                'personnel_code' => $u->personnel_code,
                'full_name'      => $u->full_name,
                'workplace'      => $this->resolveUserWorkplace($u),
            ])->values()->toArray(),
        ];
    }

    private function scopeUsersToLevel(Builder $query, string $level, $entityId): void
    {
        switch ($level) {
            case self::LEVEL_PROVINCE:
                break;

            case self::LEVEL_ZONE:
                $branchCodes = Branch::where('zone_code', $entityId)->pluck('code');
                $officeIds   = BranchOffice::whereIn('branch_code', $branchCodes)->pluck('id');
                $query->where(function ($q) use ($entityId, $branchCodes, $officeIds) {
                    $q->where(function ($qq) use ($entityId) {
                        $qq->where('workplace_type', 'zone')->where('zone_code', $entityId);
                    })->orWhere(function ($qq) use ($branchCodes) {
                        $qq->where('workplace_type', 'branch')->whereIn('branch_code', $branchCodes);
                    })->orWhere(function ($qq) use ($officeIds) {
                        $qq->where('workplace_type', 'branch_office')->whereIn('branch_office_id', $officeIds);
                    });
                });
                break;

            case self::LEVEL_BRANCH:
                $officeIds = BranchOffice::where('branch_code', $entityId)->pluck('id');
                $query->where(function ($q) use ($entityId, $officeIds) {
                    $q->where(function ($qq) use ($entityId) {
                        $qq->where('workplace_type', 'branch')->where('branch_code', $entityId);
                    })->orWhere(function ($qq) use ($officeIds) {
                        $qq->where('workplace_type', 'branch_office')->whereIn('branch_office_id', $officeIds);
                    });
                });
                break;

            case self::LEVEL_BRANCH_OFFICE:
                $query->where('workplace_type', 'branch_office')->where('branch_office_id', $entityId);
                break;

            case self::LEVEL_STAFF:
                $query->where('workplace_type', 'staff')->where('staff_unit_code', $entityId);
                break;

            case self::LEVEL_EMPLOYEE:
                $query->where('personnel_code', $entityId);
                break;
        }
    }

    private function resolveUserWorkplace(User $user): string
    {
        return match ($user->workplace_type) {
            'branch'        => 'شعبه ' . ($user->branch?->name ?? '—') . ' - ' . $user->branch_code,
            'branch_office' => 'باجه ' . ($user->branchOffice?->name ?? '—'),
            'zone'          => 'حوزه ' . ($user->zone?->name ?? '—'),
            'staff'         => $user->staffUnit?->name ?? '—',
            default         => '—',
        };
    }

    // ─── Labels ────────────────────────────────────────────────────────────────

    public static function levelLabels(): array
    {
        return [
            self::LEVEL_PROVINCE      => 'کل استان',
            self::LEVEL_ZONE          => 'حوزه',
            self::LEVEL_BRANCH        => 'شعبه',
            self::LEVEL_BRANCH_OFFICE => 'باجه',
            self::LEVEL_STAFF         => 'واحد ستادی',
            self::LEVEL_EMPLOYEE      => 'کارمند',
        ];
    }

    public static function periodLabels(): array
    {
        return [
            'today'         => 'امروز',
            'yesterday'     => 'دیروز',
            'current_month' => 'ماه جاری',
            'prev_month'    => 'ماه قبل',
            'current_year'  => 'سال جاری',
            'past_year'     => 'یک سال گذشته',
            'custom'        => 'بازه انتخابی',
        ];
    }

    public static function breakdownLabels(): array
    {
        return [
            'zone'          => 'حوزه',
            'branch'        => 'شعبه',
            'branch_office' => 'باجه',
            'employee'      => 'کارمند',
        ];
    }

    public static function breakdownOptionsForLevel(string $level): array
    {
        return match ($level) {
            self::LEVEL_PROVINCE      => ['zone' => 'حوزه', 'branch' => 'شعبه', 'branch_office' => 'باجه', 'employee' => 'کارمند'],
            self::LEVEL_ZONE          => ['branch' => 'شعبه', 'branch_office' => 'باجه', 'employee' => 'کارمند'],
            self::LEVEL_BRANCH        => ['branch_office' => 'باجه', 'employee' => 'کارمند'],
            self::LEVEL_BRANCH_OFFICE => ['employee' => 'کارمند'],
            self::LEVEL_STAFF         => ['employee' => 'کارمند'],
            default                   => [],
        };
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
