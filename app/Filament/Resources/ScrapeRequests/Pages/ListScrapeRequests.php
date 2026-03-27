<?php

namespace App\Filament\Resources\ScrapeRequests\Pages;

use App\Filament\Resources\ScrapeRequests\ScrapeRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScrapeRequests extends ListRecords
{
    protected static string $resource = ScrapeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
