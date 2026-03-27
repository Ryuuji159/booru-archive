<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Images')
                    ->schema([
                        ImageEntry::make('preview_path')
                            ->label('Preview')
                            ->disk(fn (Post $record): string => $record->storage_disk)
                            ->imageWidth('100%')
                            ->imageHeight(220)
                            ->placeholder('-'),
                        ImageEntry::make('storage_path')
                            ->label('Full')
                            ->disk(fn (Post $record): string => $record->storage_disk)
                            ->imageWidth('100%')
                            ->imageHeight(420)
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Source')
                    ->schema([
                        TextEntry::make('source_site'),
                        TextEntry::make('source_post_id')
                            ->numeric(),
                        TextEntry::make('md5'),
                        TextEntry::make('author')
                            ->placeholder('-'),
                        TextEntry::make('source_created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('source_file_url')
                            ->columnSpanFull(),
                        TextEntry::make('source_preview_url')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Download')
                    ->schema([
                        TextEntry::make('download_status')
                            ->badge(),
                        TextEntry::make('download_attempts')
                            ->numeric(),
                        TextEntry::make('storage_disk'),
                        TextEntry::make('storage_path')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('preview_path')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('last_download_error')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('downloaded_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('file_ext')
                            ->placeholder('-'),
                        TextEntry::make('file_size')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('width')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('height')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('rating')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('score')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('tags.name')
                            ->label('Tags')
                            ->badge()
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
