<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\BranchOffice;
use App\Models\ServiceType;
use App\Models\StaffUnit;
use App\Models\User;
use App\Models\Zone;
use App\Services\Report\ChartSvgRenderer;
use App\Services\Report\PerformanceReportService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
        'period'                    => 'current_month',
        'report_type'               => 'summary',
        'sort_by'                   => 'total',
        'sort_dir'                  => 'desc',
        'low_performance_threshold' => 5,
    ];

    public ?array  $detailedResult      = null;
    public ?array  $summaryResult       = null;
    public ?array  $breakdownResult     = null;
    public ?array  $noPerformanceResult = null;
    public ?string $entityLabel     = null;
    public ?string $periodLabel     = null;
    public ?string $fromLabel       = null;
    public ?string $toLabel         = null;

    /** Named inline-SVG chart markup, one entry per result branch — reused verbatim in PDF exports. */
    public array $chartsSvg = [];

    public ?float  $approvalRate        = null;
    public ?int    $lowPerformanceCount = null;
    public ?float  $deltaPercent        = null;
    public ?string $deltaDirection      = null;
    public ?string $previousPeriodLabel = null;

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
            ->columns(1)
            ->components([

                Section::make('دامنه گزارش')
                    ->description('یک سطح مشخص را برای مشاهده گزارش انتخاب کنید (فقط یک سطح در هر بار).')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
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
                    ]),

                Section::make('ریزبندی گزارش')
                    ->description('گزارش را بر اساس یکی از زیرمجموعه‌های سطح انتخابی، ریز کنید (اختیاری — فقط یک محور در هر بار).')
                    ->columnSpanFull()
                    ->visible(fn($get) => filled($get('level')) && $get('level') !== 'employee')
                    ->schema([
                        Select::make('breakdown_by')
                            ->label(fn($get) => $get('report_type') === 'no_performance' ? 'موجودیت فاقد عملکرد' : 'ریزبندی بر اساس')
                            ->placeholder(fn($get) => $get('report_type') === 'no_performance' ? 'پرسنل (پیش‌فرض)' : 'بدون ریزبندی')
                            ->options(fn($get) => PerformanceReportService::breakdownOptionsForLevel($get('level') ?? ''))
                            ->live(),
                    ]),

                Section::make('بازه زمانی')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
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
                    ]),

                Section::make('نوع خدمت')
                    ->description('در صورت عدم انتخاب هیچ گزینه‌ای، همه خدمات لحاظ می‌شوند.')
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('service_type_ids')
                            ->hiddenLabel()
                            ->options(fn() => ServiceType::orderBy('id')->pluck('name', 'id'))
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row'),
                    ]),

                Section::make('نوع گزارش')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('report_type')
                            ->label('نوع گزارش')
                            ->options(PerformanceReportService::reportTypeLabels())
                            ->default('summary')
                            ->live()
                            ->required(),

                        TextInput::make('low_performance_threshold')
                            ->label('آستانه کم‌عملکردی (کمتر از)')
                            ->helperText('موجودیت‌هایی با مجموع رکورد کمتر از این عدد، «کم‌عملکرد» علامت‌گذاری می‌شوند.')
                            ->numeric()
                            ->minValue(0)
                            ->default(5)
                            ->required()
                            ->visible(fn($get) => $get('report_type') === 'summary'),
                    ]),

                Section::make('مرتب‌سازی')
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn($get) => $get('report_type') !== 'no_performance')
                    ->schema([
                        Select::make('sort_by')
                            ->label('مرتب‌سازی بر اساس')
                            ->options(PerformanceReportService::sortByLabels())
                            ->default('total')
                            ->required(),

                        Select::make('sort_dir')
                            ->label('ترتیب')
                            ->options(PerformanceReportService::sortDirLabels())
                            ->default('desc')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data    = $this->form->getState();
        $service = app(PerformanceReportService::class);

        $this->summaryResult       = null;
        $this->detailedResult      = null;
        $this->breakdownResult     = null;
        $this->noPerformanceResult = null;
        $this->chartsSvg            = [];
        $this->approvalRate         = null;
        $this->lowPerformanceCount  = null;
        $this->deltaPercent         = null;
        $this->deltaDirection       = null;
        $this->previousPeriodLabel  = null;

        [$from, $to] = PerformanceReportService::resolveDateRange(
            $data['period'],
            isset($data['from']) ? Carbon::parse($data['from'])->toDateString() : null,
            isset($data['to'])   ? Carbon::parse($data['to'])->toDateString()   : null,
        );

        $level          = $data['level'];
        $entityId       = $data['entity_id'] ?? 'all';
        $breakdownBy    = $data['breakdown_by'] ?? null;
        $serviceTypeIds = $data['service_type_ids'] ?? [];
        $sortBy         = $data['sort_by'] ?? 'total';
        $sortDir        = $data['sort_dir'] ?? 'desc';
        $threshold      = (int) ($data['low_performance_threshold'] ?? 5);

        $this->entityLabel = $this->resolveEntityLabel($level, $entityId);
        $this->periodLabel = PerformanceReportService::periodLabels()[$data['period']] ?? '';
        $this->fromLabel   = Jalalian::fromDateTime(new \DateTime($from))->format('Y/m/d');
        $this->toLabel     = Jalalian::fromDateTime(new \DateTime($to))->format('Y/m/d');

        // ─── فاقد عملکرد ──────────────────────────────────────────────────────
        if ($data['report_type'] === 'no_performance') {
            $checkBy = $breakdownBy ?: 'employee';
            $result  = $service->noPerformance($level, $entityId, $from, $to, $serviceTypeIds, $checkBy);
            $this->noPerformanceResult = $result;
            $this->chartsSvg['no_performance'] = ChartSvgRenderer::bar(
                $result['by_extra'],
                ['title' => 'توزیع فاقد عملکرد بر اساس گروه']
            );
            return;
        }

        // ─── ریزبندی (breakdown) ───────────────────────────────────────────────
        if ($breakdownBy && $data['report_type'] === 'summary') {
            $result = $service->breakdownSummary($level, $entityId, $from, $to, $breakdownBy, $serviceTypeIds, $threshold, $sortBy, $sortDir);
            $this->breakdownResult    = $result;
            $this->lowPerformanceCount = $result['low_performance_count'];
            $this->setApprovalAndDelta($service, $level, $entityId, $from, $to, $serviceTypeIds, $result['grand_total']);
            $this->chartsSvg['breakdown'] = ChartSvgRenderer::stackedBar(
                PerformanceReportService::chartTopRows($result['rows'], 'label', 'grand_total', 10),
                ['title' => 'برترین موارد به تفکیک ' . $result['breakdown_label']]
            );
            return;
        }

        // ─── گزارش جزئی ───────────────────────────────────────────────────────
        if ($data['report_type'] === 'detailed') {
            $records = $service->detailed($level, $entityId, $from, $to, false, $serviceTypeIds);
            $this->detailedResult = $records->toArray();
            $this->chartsSvg['detailed_status'] = ChartSvgRenderer::donut(
                PerformanceReportService::statusDistribution($records),
                ['title' => 'ترکیب وضعیت رکوردها']
            );
            return;
        }

        // ─── گزارش کلی ────────────────────────────────────────────────────────
        $result = $service->summary($level, $entityId, $from, $to, false, $serviceTypeIds, $threshold, $sortBy, $sortDir);
        $this->summaryResult       = $result;
        $this->lowPerformanceCount = $result['low_performance_count'];
        $this->setApprovalAndDelta($service, $level, $entityId, $from, $to, $serviceTypeIds, $result['grand_total']);
        $this->chartsSvg['composition']  = ChartSvgRenderer::donut(
            PerformanceReportService::chartServiceTypeComposition($result['totals']),
            ['title' => 'ترکیب بر اساس نوع خدمت']
        );
        $this->chartsSvg['top_entities'] = ChartSvgRenderer::stackedBar(
            PerformanceReportService::chartTopRows($result['by_employee'], 'full_name', 'total', 10),
            ['title' => 'همکاران برتر']
        );
    }

    private function setApprovalAndDelta(
        PerformanceReportService $service,
        string $level,
        string|int $entityId,
        string $from,
        string $to,
        array $serviceTypeIds,
        array $grandTotal
    ): void {
        $this->approvalRate = $grandTotal['all'] > 0
            ? round($grandTotal[PerformanceReportService::STATUS_APPROVED] / $grandTotal['all'] * 100, 1)
            : null;

        [$prevFrom, $prevTo] = PerformanceReportService::previousPeriodRange($from, $to);
        $prevTotal = $service->totalCount($level, $entityId, $prevFrom, $prevTo, $serviceTypeIds);

        $this->previousPeriodLabel = Jalalian::fromDateTime(new \DateTime($prevFrom))->format('Y/m/d')
            . ' تا ' . Jalalian::fromDateTime(new \DateTime($prevTo))->format('Y/m/d');

        if ($prevTotal > 0) {
            $this->deltaPercent   = round((($grandTotal['all'] - $prevTotal) / $prevTotal) * 100, 1);
            $this->deltaDirection = $this->deltaPercent >= 0 ? 'up' : 'down';
        } elseif ($grandTotal['all'] > 0) {
            $this->deltaPercent   = 100.0;
            $this->deltaDirection = 'up';
        }
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
            'level'                     => $data['level'],
            'entity_id'                 => $data['entity_id'] ?? 'all',
            'from'                      => $from,
            'to'                        => $to,
            'report_type'               => $data['report_type'],
            'breakdown_by'              => $data['breakdown_by'] ?? null,
            'service_type_ids'          => $data['service_type_ids'] ?? [],
            'sort_by'                   => $data['sort_by'] ?? 'total',
            'sort_dir'                  => $data['sort_dir'] ?? 'desc',
            'low_performance_threshold' => $data['low_performance_threshold'] ?? 5,
            'format'                    => $format,
        ]);
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
