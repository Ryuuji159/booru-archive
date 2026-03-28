<?php

namespace App\Filament\Resources\SearchRequests\Tables;

use App\Models\ScrapeRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SearchRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('site')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('search_tags')
                    ->label('Tags')
                    ->state(fn (ScrapeRequest $record): string => implode(', ', $record->parameters['tags'] ?? []))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $escapedSearch = addcslashes($search, '%_\\');

                        return $query->whereRaw('CAST(parameters AS TEXT) LIKE ?', ["%{$escapedSearch}%"]);
                    })
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('discovered_posts_count')
                    ->label('Discovered')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
