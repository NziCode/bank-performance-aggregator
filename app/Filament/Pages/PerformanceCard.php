<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\BranchOffice;
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

class PerformanceCard extends Page
{
    protected string $view = 'filament.pages.performance-card';

    protected static ?string $navigationLabel = 'کارنامه عملکرد';
    protected static ?string $title = 'کارنامه عملکرد';
    protected static ?int $navigationSort = 5;

    public ?array $data = [
        'report_type' => 'summary',
    ];

    public ?array $detailedResult = null;
    public ?array $summaryResult  = null;
    public ?string $entityLabel   = null;

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
                Select::make('level')
                    ->label('سطح')
                    ->options(PerformanceReportService::levelLabels())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('entity_id', null)),

                Select::make('entity_id')
                    ->label('انتخاب موجودیت')
                    ->required()
                    ->searchable()
                    ->options(function ($get) {
                        return match ($get('level')) {
                            'employee'      => User::all()->mapWithKeys(fn($u) => [$u->personnel_code => $u->full_name . ' - ' . $u->personnel_code]),
                            'branch'        => Branch::orderBy('code')->get()->mapWithKeys(fn($b) => [$b->code => $b->name . ' - ' . $b->code]),
                            'branch_office' => BranchOffice::orderBy('name')->pluck('name', 'id'),
                            'zone'          => Zone::pluck('name', 'code'),
                            'staff'         => StaffUnit::pluck('name', 'code'),
                            default         => [],
                        };
                    })
                    ->visible(fn($get) => filled($get('level'))),

                Select::make('report_type')
                    ->label('نوع کارنامه')
                    ->options([
                        'summary'  => 'کلی (خلاصه)',
                        'detailed' => 'جزئی (ریز رکوردها)',
                    ])
                    ->default('summary')
                    ->required(),

                DatePicker::make('from')
                    ->label('از تاریخ')
                    ->jalali()
                    ->displayFormat('Y/m/d')
                    ->required(),

                DatePicker::make('to')
                    ->label('تا تاریخ')
                    ->jalali()
                    ->displayFormat('Y/m/d')
                    ->required(),

                Toggle::make('include_sub_offices')
                    ->label('شامل باجه‌های زیرمجموعه شود')
                    ->helperText('فقط برای سطح شعبه')
                    ->visible(fn($get) => $get('level') === 'branch')
                    ->default(false),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $service = app(PerformanceReportService::class);

        // DatePicker با jalali() مقدار state را به صورت تاریخ میلادی استاندارد (Y-m-d)
        // نگه می‌دارد و فقط نمایش را به شمسی تبدیل می‌کند — نیازی به تبدیل دستی نیست.
        $from = Carbon::parse($data['from'])->toDateString();
        $to   = Carbon::parse($data['to'])->toDateString();

        $includeSubOffices = $data['include_sub_offices'] ?? false;

        $this->entityLabel = $this->resolveEntityLabel($data['level'], $data['entity_id']);

        if ($data['report_type'] === 'detailed') {
            $records = $service->detailed($data['level'], $data['entity_id'], $from, $to, $includeSubOffices);

            if ($records->isEmpty()) {
                Notification::make()->title('رکوردی یافت نشد')->warning()->send();
                $this->detailedResult = null;
                $this->summaryResult  = null;
                return;
            }

            $this->detailedResult = $records->toArray();
            $this->summaryResult  = null;
        } else {
            $result = $service->summary($data['level'], $data['entity_id'], $from, $to, $includeSubOffices);

            if ($result['grand_total']['all'] === 0) {
                Notification::make()->title('رکوردی یافت نشد')->warning()->send();
                $this->summaryResult  = null;
                $this->detailedResult = null;
                return;
            }

            $this->summaryResult  = $result;
            $this->detailedResult = null;
        }
    }

    public function exportExcel(): mixed
    {
        $data = $this->form->getState();

        return redirect()->route('reports.performance-card.export', [
            'level'                => $data['level'],
            'entity_id'            => $data['entity_id'],
            'from'                 => Carbon::parse($data['from'])->toDateString(),
            'to'                   => Carbon::parse($data['to'])->toDateString(),
            'report_type'          => $data['report_type'],
            'include_sub_offices'  => $data['include_sub_offices'] ?? false,
            'format'               => 'excel',
        ]);
    }

    public function exportPdf(): mixed
    {
        $data = $this->form->getState();

        return redirect()->route('reports.performance-card.export', [
            'level'                => $data['level'],
            'entity_id'            => $data['entity_id'],
            'from'                 => Carbon::parse($data['from'])->toDateString(),
            'to'                   => Carbon::parse($data['to'])->toDateString(),
            'report_type'          => $data['report_type'],
            'include_sub_offices'  => $data['include_sub_offices'] ?? false,
            'format'               => 'pdf',
        ]);
    }

    private function resolveEntityLabel(string $level, string|int $entityId): ?string
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
