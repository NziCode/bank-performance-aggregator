<?php

namespace App\Filament\Pages;

use App\Models\ServiceType;
use App\Services\Report\PerformanceReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class NoPerformanceDashboard extends Page
{
    protected string $view = 'filament.pages.no-performance-dashboard';

    protected static ?string $navigationLabel = 'داشبورد فاقد عملکرد';
    protected static ?string $title = 'داشبورد فاقد عملکرد';
    protected static ?int $navigationSort = 7;

    public ?array $data = ['period' => 'current_month'];
    public ?array $dashboardData = null;
    public int $generateCount = 0;
    public ?string $fromLabel = null;
    public ?string $toLabel = null;
    public ?string $periodLabel = null;

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-no-symbol';
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

                Select::make('service_type_ids')
                    ->label('نوع خدمت (برای لیست اصلی)')
                    ->placeholder('همه خدمات')
                    ->multiple()
                    ->options(fn() => ServiceType::orderBy('id')->pluck('name', 'id'))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        [$from, $to] = PerformanceReportService::resolveDateRange(
            $data['period'],
            isset($data['from']) ? Carbon::parse($data['from'])->toDateString() : null,
            isset($data['to'])   ? Carbon::parse($data['to'])->toDateString()   : null,
        );

        $this->periodLabel = PerformanceReportService::periodLabels()[$data['period']] ?? '';
        $this->fromLabel   = Jalalian::fromDateTime(new \DateTime($from))->format('Y/m/d');
        $this->toLabel     = Jalalian::fromDateTime(new \DateTime($to))->format('Y/m/d');

        $this->dashboardData = app(PerformanceReportService::class)
            ->noPerformanceDashboard($from, $to, $data['service_type_ids'] ?? []);

        $this->generateCount++;
    }
}
