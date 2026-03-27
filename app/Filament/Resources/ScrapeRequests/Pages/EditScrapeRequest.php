<?php

namespace App\Filament\Resources\ScrapeRequests\Pages;

use App\Filament\Resources\ScrapeRequests\ScrapeRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScrapeRequest extends EditRecord
{
    protected static string $resource = ScrapeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
