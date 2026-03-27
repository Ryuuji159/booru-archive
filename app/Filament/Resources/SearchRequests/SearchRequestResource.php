<?php

namespace App\Filament\Resources\SearchRequests;

use App\Filament\Resources\SearchRequests\Pages\CreateSearchRequest;
use App\Filament\Resources\SearchRequests\Pages\EditSearchRequest;
use App\Filament\Resources\SearchRequests\Pages\ListSearchRequests;
use App\Filament\Resources\SearchRequests\Schemas\SearchRequestForm;
use App\Filament\Resources\SearchRequests\Tables\SearchRequestsTable;
use App\Models\ScrapeRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SearchRequestResource extends Resource
{
    protected static ?string $model = ScrapeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Search Requests';

    protected static ?string $modelLabel = 'Search Request';

    protected static ?string $pluralModelLabel = 'Search Requests';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SearchRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SearchRequestsTable::configure($table);
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
            'index' => ListSearchRequests::route('/'),
            'create' => CreateSearchRequest::route('/create'),
            'edit' => EditSearchRequest::route('/{record}/edit'),
        ];
    }
}
