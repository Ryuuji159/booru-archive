<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePosts;
use App\Filament\Resources\Posts\Pages\EditPosts;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPosts;
use App\Filament\Resources\Posts\Schemas\PostsForm;
use App\Filament\Resources\Posts\Schemas\PostsInfolist;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Posts;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PostsResource extends Resource
{
    protected static ?string $model = Posts::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return PostsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
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
            'index' => ListPosts::route('/'),
            'create' => CreatePosts::route('/create'),
            'view' => ViewPosts::route('/{record}'),
            'edit' => EditPosts::route('/{record}/edit'),
        ];
    }
}
