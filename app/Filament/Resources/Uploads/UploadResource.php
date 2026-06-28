<?php

namespace App\Filament\Resources\Uploads;

use App\Filament\Resources\Uploads\Pages\CreateUpload;
use App\Filament\Resources\Uploads\Pages\ListUploads;
use App\Filament\Resources\Uploads\Pages\ViewUpload;
use App\Filament\Resources\Uploads\Schemas\UploadForm;
use App\Filament\Resources\Uploads\Schemas\UploadInfolist;
use App\Filament\Resources\Uploads\Tables\UploadsTable;
use App\Models\Upload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UploadResource extends Resource
{
    protected static ?string $model = Upload::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;
    protected static ?string $recordTitleAttribute = 'original_filename';
    protected static ?string $navigationLabel = 'آپلودها';
    protected static ?string $modelLabel = 'آپلود';
    protected static ?string $pluralModelLabel = 'آپلودها';
    public static function getNavigationGroup(): ?string
    {
        return 'عملکرد';
    }
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return UploadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UploadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UploadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUploads::route('/'),
            'create' => CreateUpload::route('/create'),
            'view'   => ViewUpload::route('/{record}'),
        ];
    }
}
