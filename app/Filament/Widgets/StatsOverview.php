<?php

namespace App\Filament\Widgets;

use App\Models\Performance;
use App\Models\Upload;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('کل عملکردها', Performance::count())
                ->description('مجموع ثبت‌شده')
                ->color('info')
                ->icon('heroicon-o-rectangle-stack'),

            Stat::make('در انتظار بررسی', Performance::pending()->count())
                ->description('نیاز به اعتبارسنجی')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('تایید شده', Performance::approved()->count())
                ->description('عملکردهای معتبر')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('رد شده', Performance::rejected()->count())
                ->description('عملکردهای نامعتبر')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('فایل‌های آپلود', Upload::count())
                ->description('مجموع فایل‌های دریافتی')
                ->color('gray')
                ->icon('heroicon-o-arrow-up-tray'),

            Stat::make('پردازش ناموفق', Upload::failed()->count())
                ->description('نیاز به بررسی')
                ->color('danger')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
