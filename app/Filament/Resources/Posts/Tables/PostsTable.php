<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_post_id')
                    ->label('Source ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_site')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('md5')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('file_ext')
                    ->badge(),
                TextColumn::make('rating')
                    ->badge(),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dimensions')
                    ->state(fn (Post $record): string => $record->width && $record->height ? "{$record->width}x{$record->height}" : '-'),
                TextColumn::make('download_status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_downloaded')
                    ->label('Downloaded')
                    ->state(fn (Post $record): bool => $record->downloaded_at !== null)
                    ->boolean(),
                TextColumn::make('tags.name')
                    ->badge(),
                TextColumn::make('downloaded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
