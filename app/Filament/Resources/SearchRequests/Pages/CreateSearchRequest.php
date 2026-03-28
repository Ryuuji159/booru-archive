<?php

namespace App\Filament\Resources\SearchRequests\Pages;

use App\Filament\Resources\SearchRequests\SearchRequestResource;
use App\Services\KonachanService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;

class CreateSearchRequest extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SearchRequestResource::class;

    public function getTitle(): string
    {
        return 'Create Search Request';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Tags')
                ->description('Define the search.')
                ->schema([
                    Select::make('site')
                        ->label('Source')
                        ->required()
                        ->default('konachan')
                        ->live()
                        ->options([
                            'konachan' => 'Konachan',
                        ]),
                    TagsInput::make('tags')
                        ->label('Search tags')
                        ->required()
                        ->live()
                        ->reorderable()
                        ->splitKeys(['Tab', 'Enter', ','])
                        ->helperText('Enter one or more tags. The next step will query Konachan and show up to 6 real previews.')
                        ->columnSpanFull(),
                ]),
            Step::make('Preview')
                ->description('Results returned by the search.')
                ->schema([
                    View::make('filament.resources.search-requests.preview-posts')
                        ->viewData(fn (Get $get, KonachanService $konachanService): array => [
                            'site' => $get('site') ?? 'konachan',
                            'tags' => $get('tags') ?? [],
                            'posts' => ($get('site') ?? 'konachan') === 'konachan'
                                ? $konachanService->posts($get('tags') ?? [], 6)
                                : [],
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            'site' => $data['site'] ?? 'konachan',
            'parameters' => [
                'tags' => array_values(array_filter($data['tags'] ?? [])),
            ],
            'status' => 'pending',
            'discovered_posts_count' => 0,
            'started_at' => null,
            'finished_at' => null,
            'last_error' => null,
        ];
    }
}
