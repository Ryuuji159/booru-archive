<?php

namespace App\Filament\Resources\ScrapeRequests;

use App\Filament\Resources\ScrapeRequests\Pages\CreateScrapeRequest;
use App\Filament\Resources\ScrapeRequests\Pages\EditScrapeRequest;
use App\Filament\Resources\ScrapeRequests\Pages\ListScrapeRequests;
use App\Filament\Resources\ScrapeRequests\Schemas\ScrapeRequestForm;
use App\Filament\Resources\ScrapeRequests\Tables\ScrapeRequestsTable;
use App\Models\ScrapeRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScrapeRequestResource extends Resource
{
    protected static ?string $model = ScrapeRequest::class;

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ScrapeRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScrapeRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScrapeRequests::route('/'),
            'create' => CreateScrapeRequest::route('/create'),
            'edit' => EditScrapeRequest::route('/{record}/edit'),
        ];
    }
}
