<?php

namespace App\Filament\Resources\ScrapeRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScrapeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request')
                    ->schema([
                        TextInput::make('site')
                            ->required()
                            ->default('konachan')
                            ->maxLength(255),
                        Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'running' => 'Running',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                        TextInput::make('discovered_posts_count')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('last_processed_page')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Last scrape page saved before the request stopped.')
                            ->visibleOn('edit'),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('finished_at'),
                        Textarea::make('parameters')
                            ->required()
                            ->rows(10)
                            ->rule('json')
                            ->formatStateUsing(fn (?array $state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->dehydrateStateUsing(fn (?string $state): array => blank($state) ? [] : json_decode($state, true) ?? [])
                            ->helperText('JSON with the search parameters used to discover posts.')
                            ->columnSpanFull(),
                        Textarea::make('last_error')
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
