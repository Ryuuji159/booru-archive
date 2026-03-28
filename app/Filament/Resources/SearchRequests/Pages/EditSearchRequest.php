<?php

namespace App\Filament\Resources\SearchRequests\Pages;

use App\Filament\Resources\SearchRequests\SearchRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSearchRequest extends EditRecord
{
    protected static string $resource = SearchRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['site'] = $data['site'] ?? 'konachan';
        $data['tags'] = $data['parameters']['tags'] ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            ...$this->record->only([
                'status',
                'discovered_posts_count',
                'started_at',
                'finished_at',
                'last_error',
            ]),
            'site' => $data['site'] ?? 'konachan',
            'parameters' => [
                ...($this->record->parameters ?? []),
                'tags' => array_values(array_filter($data['tags'] ?? [])),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
