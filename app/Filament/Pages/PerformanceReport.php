<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Performance;
use App\Models\ServiceType;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class PerformanceReport extends Page
{
    protected string $view = 'filament.pages.performance-report';

    protected static ?string $navigationLabel = 'گزارش عملکرد';
    protected static ?string $title = 'گزارش عملکرد';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];
    public ?array $reportData = null;
    public array $serviceTypes = [];
    public array $branches = [];

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'عملکرد';
    }

    public function mount(): void
    {
        $this->serviceTypes = ServiceType::pluck('name')->toArray();
        $this->branches     = Branch::orderBy('name')->pluck('name', 'code')->toArray();
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('from')
                    ->label('از تاریخ')
                    ->placeholder('14050101')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('to')
                    ->label('تا تاریخ')
                    ->placeholder('14050130')
                    ->required(),
                \Filament\Forms\Components\Select::make('branch_code')
                    ->label('شعبه')
                    ->options(fn() => $this->branches)
                    ->placeholder('همه شعب')
                    ->searchable()
                    ->native(false),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function generate(): void
    {
        $data       = $this->form->getState();
        $from       = $data['from'];
        $to         = $data['to'];
        $branchCode = $data['branch_code'] ?? null;

        if (empty($from) || empty($to)) {
            Notification::make()
                ->title('لطفاً بازه تاریخ را وارد کنید')
                ->warning()
                ->send();
            return;
        }

        $serviceTypes = ServiceType::pluck('name');

        $query = Performance::approved()
            ->whereBetween('date', [$from, $to]);

        if ($branchCode) {
            $query->where('branch_code', (int) $branchCode);
        }

        $records = $query
            ->select(['personnel_code', 'service_type_id', DB::raw('COUNT(*) as count')])
            ->with(['employee.branch', 'serviceType'])
            ->groupBy('personnel_code', 'service_type_id')
            ->get()
            ->groupBy('personnel_code');

        $result = [];
        foreach ($records as $personnelCode => $rows) {
            $first    = $rows->first();
            $services = [];

            foreach ($serviceTypes as $type) {
                $services[$type] = $rows->first(fn($r) => $r->serviceType?->name === $type)?->count ?? 0;
            }

            $result[] = [
                'personnel_code' => $personnelCode,
                'full_name'      => $first->employee?->full_name,
                'branch'         => $first->employee?->branch?->name,
                'services'       => $services,
                'total'          => $rows->sum('count'),
            ];
        }

        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);

        if (empty($result)) {
            Notification::make()
                ->title('رکوردی یافت نشد')
                ->warning()
                ->send();
            return;
        }

        $this->reportData   = $result;
        $this->serviceTypes = $serviceTypes->toArray();
    }

    public function exportExcel(): mixed
    {
        $data = $this->form->getState();

        return redirect()->route('exports.by-employee', [
            'from'        => $data['from'],
            'to'          => $data['to'],
            'branch_code' => $data['branch_code'] ?? null,
        ]);
    }
}
