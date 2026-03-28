<?php

namespace App\Filament\Resources\SearchRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SearchRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Search tags')
                    ->description('Edit the tags that will be used for this search request.')
                    ->schema([
                        Select::make('site')
                            ->required()
                            ->options([
                                'konachan' => 'Konachan',
                            ]),
                        TagsInput::make('tags')
                            ->required()
                            ->reorderable()
                            ->splitKeys(['Tab', 'Enter', ','])
                            ->helperText('Each tag is stored inside parameters.tags.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
