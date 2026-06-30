<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessUploadedFileJob;
use App\Models\Upload;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class UploadPerformance extends Page
{
    protected string $view = 'filament.pages.upload-performance';

    protected static ?string $title = 'آپلود فایل عملکرد';

    // این صفحه دیگر آیتم مستقل منو نیست —
    // فقط از طریق دکمه «ایجاد آپلود جدید» در صفحه لیست آپلودها قابل دسترسی است
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [
        'original_filenames' => [],
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('period')
                    ->label('دوره گزارش')
                    ->jalali()
                    ->displayFormat('Y/m/d')
                    ->helperText('تاریخ دوره گزارش را انتخاب کنید — این فایل می‌تواند شامل عملکرد چند شعبه/باجه/حوزه/ستاد باشد')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('files')
                    ->label('فایل‌های Excel')
                    ->multiple()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240)
                    ->maxFiles(50)
                    ->storeFileNamesIn('original_filenames')
                    ->helperText('فقط فایل xlsx — حداکثر 10 مگابایت برای هر فایل')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(1);
    }

    public function submit(): void
    {
        $data          = $this->form->getState();
        $period        = Carbon::parse($data['period'])->toDateString();
        $files         = $data['files'];
        $originalNames = $data['original_filenames'] ?? [];
        $count         = 0;

        foreach ($files as $file) {
            $sourcePath   = storage_path('app/private/' . $file);
            $originalName = $originalNames[$file] ?? basename($file);
            $newPath      = "excel-uploads/{$period}/" . $originalName;

            Storage::put($newPath, file_get_contents($sourcePath));

            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }

            $upload = Upload::create([
                'original_filename' => $originalName,
                'stored_path'       => $newPath,
                'period'            => $period,
                'uploaded_by'       => auth()->id(),
            ]);

            ProcessUploadedFileJob::dispatch($upload->id);
            $count++;
        }

        Notification::make()
            ->title("{$count} فایل با موفقیت در صف پردازش قرار گرفت")
            ->success()
            ->send();

        $this->redirect(\App\Filament\Resources\Uploads\UploadResource::getUrl('index'));
    }
}
