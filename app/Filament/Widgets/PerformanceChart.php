<?php

namespace App\Filament\Widgets;

use App\Models\Performance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PerformanceChart extends ChartWidget
{
    public ?string $heading = 'عملکرد به تفکیک نوع خدمت';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Performance::approved()
            ->select('service_type_id', DB::raw('COUNT(*) as count'))
            ->groupBy('service_type_id')
            ->with('serviceType')
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'تعداد',
                    'data'            => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#06b6d4',
                        '#f97316',
                    ],
                ],
            ],
            'labels' => $data->map(fn($r) => $r->serviceType?->name)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
