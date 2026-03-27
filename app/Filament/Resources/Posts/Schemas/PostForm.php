<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Source')
                    ->schema([
                        TextInput::make('source_site')
                            ->required()
                            ->default('konachan')
                            ->maxLength(255),
                        TextInput::make('source_post_id')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('md5')
                            ->required()
                            ->length(32),
                        TextInput::make('file_ext')
                            ->nullable()
                            ->maxLength(10),
                        TextInput::make('author')
                            ->nullable()
                            ->maxLength(255),
                        DateTimePicker::make('source_created_at'),
                        TextInput::make('source_file_url')
                            ->required()
                            ->url()
                            ->columnSpanFull(),
                        TextInput::make('source_preview_url')
                            ->nullable()
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Image metadata')
                    ->schema([
                        TextInput::make('file_size')
                            ->nullable()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('width')
                            ->nullable()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('height')
                            ->nullable()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('rating')
                            ->nullable()
                            ->maxLength(2),
                        TextInput::make('score')
                            ->nullable()
                            ->numeric(),
                    ])
                    ->columns(3),
                Section::make('Local storage')
                    ->schema([
                        TextInput::make('storage_disk')
                            ->required()
                            ->default('local')
                            ->maxLength(255),
                        TextInput::make('storage_path')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('preview_path')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('download_status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'downloading' => 'Downloading',
                                'downloaded' => 'Downloaded',
                                'failed' => 'Failed',
                                'skipped' => 'Skipped',
                            ])
                            ->default('pending'),
                        TextInput::make('download_attempts')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        DateTimePicker::make('downloaded_at'),
                        Textarea::make('last_download_error')
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Tags')
                    ->schema([
                        Select::make('tags')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->relationship('tags', 'name')
                            ->columnSpanFull(),
                    ]),
                Section::make('Raw payload')
                    ->schema([
                        Textarea::make('source_payload')
                            ->nullable()
                            ->rows(12)
                            ->rules(['nullable', 'json'])
                            ->formatStateUsing(fn (?array $state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->dehydrateStateUsing(fn (?string $state): ?array => blank($state) ? null : json_decode($state, true))
                            ->helperText('JSON opcional con la respuesta original del sitio.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
