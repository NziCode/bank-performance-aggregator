<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessUploadedFileJob;
use App\Models\Upload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class UploadPerformance extends Page
{
    protected string $view = 'filament.pages.upload-performance';

    protected static ?string $navigationLabel = 'آپلود فایل عملکرد';
    protected static ?string $title = 'آپلود فایل عملکرد';
    protected static ?int $navigationSort = 3;

    public ?array $data = [
        'original_filenames' => [],
    ];

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-arrow-up-tray';
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
            ->schema([
                TextInput::make('period')
                    ->label('دوره گزارش')
                    ->placeholder('مثال: 140501')
                    ->regex('/^\d{6}$/')
                    ->helperText('فرمت: YYYYMM — مثال: 140501')
                    ->required(),
                FileUpload::make('files')
                    ->label('فایل‌های Excel')
                    ->multiple()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240)
                    ->maxFiles(50)
                    ->storeFileNamesIn('original_filenames')
                    ->helperText('فقط فایل xlsx — حداکثر 10 مگابایت برای هر فایل')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data          = $this->form->getState();
        $period        = $data['period'];
        $files         = $data['files'];
        $originalNames = $data['original_filenames'] ?? [];
        $count         = 0;

        foreach ($files as $file) {
            $sourcePath   = storage_path('app/private/' . $file);
            $originalName = $originalNames[$file] ?? basename($file);
            $newPath      = "excel-uploads/{$period}/" . $originalName;

            Storage::put($newPath, file_get_contents($sourcePath));

            // حذف فایل موقت
            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }

            $upload = Upload::create([
                'original_filename' => $originalName,
                'stored_path'       => $newPath,
                'branch_code'       => $this->extractBranchCode($originalName),
                'period'            => $period,
                'uploaded_by'       => auth()->id(),
            ]);

            ProcessUploadedFileJob::dispatch($upload->id);
            $count++;
        }

        $this->form->fill();

        Notification::make()
            ->title("{$count} فایل با موفقیت در صف پردازش قرار گرفت")
            ->success()
            ->send();
    }

    private function extractBranchCode(string $filename): ?int
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return is_numeric($name) ? (int) $name : null;
    }
}
