<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\BranchOffice;
use App\Models\ServiceType;
use App\Models\StaffUnit;
use App\Models\User;
use App\Models\Zone;
use App\Services\Report\PerformanceReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class PerformanceCard extends Page
{
    protected string $view = 'filament.pages.performance-card';

    protected static ?string $navigationLabel = 'کارنامه عملکرد';
    protected static ?string $title = 'کارنامه عملکرد';
    protected static ?int $navigationSort = 5;

    public ?array $data = [
        'period'      => 'current_month',
        'report_type' => 'summary',
    ];

    public ?array  $detailedResult  = null;
    public ?array  $summaryResult   = null;
    public ?array  $breakdownResult = null;
    public ?string $entityLabel     = null;
    public ?string $periodLabel     = null;
    public ?string $fromLabel       = null;
    public ?string $toLabel         = null;

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'عملکرد';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ─── ردیف ۱: سطح، موجودیت، ریزبندی ───────────────────────────

                Select::make('level')
                    ->label('سطح گزارش')
                    ->options(PerformanceReportService::levelLabels())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('entity_id', $state === 'province' ? 'all' : null);
                        $set('breakdown_by', null);
                    }),

                Select::make('entity_id')
                    ->label('موجودیت')
                    ->required()
                    ->searchable()
                    ->options(function ($get) {
                        return match ($get('level')) {
                            'employee'      => User::orderBy('personnel_code')->get()
                                                ->mapWithKeys(fn($u) => [$u->personnel_code => $u->full_name . ' - ' . $u->personnel_code]),
                            'branch'        => Branch::orderBy('code')->get()
                                                ->mapWithKeys(fn($b) => [$b->code => $b->name . ' - ' . $b->code]),
                            'branch_office' => BranchOffice::orderBy('branch_code')->get()
                                                ->mapWithKeys(fn($b) => [$b->id => $b->name . ' - ' . $b->branch_code]),
                            'zone'          => Zone::orderBy('code')->get()
                                                ->mapWithKeys(fn($z) => [$z->code => $z->name . ' - ' . $z->code]),
                            'staff'         => StaffUnit::orderBy('code')->get()
                                                ->mapWithKeys(fn($s) => [$s->code => $s->name . ' - ' . $s->code]),
                            default         => [],
                        };
                    })
                    ->visible(fn($get) => filled($get('level')) && $get('level') !== 'province'),

                Select::make('breakdown_by')
                    ->label('ریزبندی بر اساس')
                    ->placeholder('بدون ریزبندی')
                    ->options(fn($get) => PerformanceReportService::breakdownOptionsForLevel($get('level') ?? ''))
                    ->live()
                    ->visible(fn($get) => filled($get('level')) && $get('level') !== 'employee'),

                // ─── ردیف ۲: بازه زمانی ──────────────────────────────────────

                Select::make('period')
                    ->label('بازه زمانی')
                    ->options(PerformanceReportService::periodLabels())
                    ->default('current_month')
                    ->required()
                    ->live(),

                DatePicker::make('from')
                    ->label('از تاریخ')
                    ->jalali()
                    ->displayFormat('Y/m/d')
                    ->required()
                    ->visible(fn($get) => $get('period') === 'custom'),

                DatePicker::make('to')
                    ->label('تا تاریخ')
                    ->jalali()
                    ->displayFormat('Y/m/d')
                    ->required()
                    ->visible(fn($get) => $get('period') === 'custom'),

                // ─── ردیف ۳: نوع خدمت ────────────────────────────────────────

                Select::make('service_type_ids')
                    ->label('نوع خدمت')
                    ->placeholder('همه خدمات')
                    ->multiple()
                    ->options(fn() => ServiceType::orderBy('id')->pluck('name', 'id'))
                    ->columnSpanFull(),

                // ─── ردیف ۴: تنظیمات ─────────────────────────────────────────

                Select::make('report_type')
                    ->label('نوع گزارش')
                    ->options([
                        'summary'  => 'کلی (خلاصه)',
                        'detailed' => 'جزئی (ریز رکوردها)',
                    ])
                    ->default('summary')
                    ->required(),

                Toggle::make('include_sub_offices')
                    ->label('شامل باجه‌های زیرمجموعه')
                    ->helperText('فقط در سطح شعبه و بدون ریزبندی')
                    ->visible(fn($get) => $get('level') === 'branch' && !filled($get('breakdown_by')))
                    ->default(false),

            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data    = $this->form->getState();
        $service = app(PerformanceReportService::class);

        [$from, $to] = PerformanceReportService::resolveDateRange(
            $data['period'],
            isset($data['from']) ? Carbon::parse($data['from'])->toDateString() : null,
            isset($data['to'])   ? Carbon::parse($data['to'])->toDateString()   : null,
        );

        $entityId        = $data['entity_id'] ?? 'all';
        $breakdownBy     = $data['breakdown_by'] ?? null;
        $serviceTypeIds  = $data['service_type_ids'] ?? [];
        $includeSubOffices = $data['include_sub_offices'] ?? false;

        $this->entityLabel = $this->resolveEntityLabel($data['level'], $entityId);
        $this->periodLabel = PerformanceReportService::periodLabels()[$data['period']] ?? '';
        $this->fromLabel   = Jalalian::fromDateTime(new \DateTime($from))->format('Y/m/d');
        $this->toLabel     = Jalalian::fromDateTime(new \DateTime($to))->format('Y/m/d');

        // ─── ریزبندی (breakdown) ───────────────────────────────────────────────
        if ($breakdownBy && $data['report_type'] === 'summary') {
            $result = $service->breakdownSummary($data['level'], $entityId, $from, $to, $breakdownBy, $serviceTypeIds);

            if ($result['grand_total']['all'] === 0) {
                Notification::make()->title('رکوردی یافت نشد')->warning()->send();
                $this->clearResults();
                return;
            }

            $this->breakdownResult = $result;
            $this->summaryResult   = null;
            $this->detailedResult  = null;
            return;
        }

        // ─── گزارش جزئی ───────────────────────────────────────────────────────
        if ($data['report_type'] === 'detailed') {
            $records = $service->detailed($data['level'], $entityId, $from, $to, $includeSubOffices, $serviceTypeIds);

            if ($records->isEmpty()) {
                Notification::make()->title('رکوردی یافت نشد')->warning()->send();
                $this->clearResults();
                return;
            }

            $this->detailedResult  = $records->toArray();
            $this->summaryResult   = null;
            $this->breakdownResult = null;
            return;
        }

        // ─── گزارش کلی ────────────────────────────────────────────────────────
        $result = $service->summary($data['level'], $entityId, $from, $to, $includeSubOffices, $serviceTypeIds);

        if ($result['grand_total']['all'] === 0) {
            Notification::make()->title('رکوردی یافت نشد')->warning()->send();
            $this->clearResults();
            return;
        }

        $this->summaryResult   = $result;
        $this->detailedResult  = null;
        $this->breakdownResult = null;
    }

    public function exportExcel(): mixed
    {
        return $this->redirectExport('excel');
    }

    public function exportPdf(): mixed
    {
        return $this->redirectExport('pdf');
    }

    private function redirectExport(string $format): mixed
    {
        $data = $this->form->getState();

        [$from, $to] = PerformanceReportService::resolveDateRange(
            $data['period'],
            isset($data['from']) ? Carbon::parse($data['from'])->toDateString() : null,
            isset($data['to'])   ? Carbon::parse($data['to'])->toDateString()   : null,
        );

        return redirect()->route('reports.performance-card.export', [
            'level'               => $data['level'],
            'entity_id'           => $data['entity_id'] ?? 'all',
            'from'                => $from,
            'to'                  => $to,
            'report_type'         => $data['report_type'],
            'breakdown_by'        => $data['breakdown_by'] ?? null,
            'service_type_ids'    => $data['service_type_ids'] ?? [],
            'include_sub_offices' => $data['include_sub_offices'] ?? false,
            'format'              => $format,
        ]);
    }

    private function clearResults(): void
    {
        $this->summaryResult   = null;
        $this->detailedResult  = null;
        $this->breakdownResult = null;
    }

    private function resolveEntityLabel(string $level, string|int $entityId): ?string
    {
        return match ($level) {
            'province'      => 'کل استان',
            'employee'      => User::where('personnel_code', $entityId)->first()?->full_name,
            'branch'        => Branch::where('code', $entityId)->value('name'),
            'branch_office' => BranchOffice::find($entityId)?->name,
            'zone'          => Zone::where('code', $entityId)->value('name'),
            'staff'         => StaffUnit::where('code', $entityId)->value('name'),
            default         => null,
        };
    }
}
