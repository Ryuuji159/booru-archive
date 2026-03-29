<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\ScrapeRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -7;

    protected ?string $heading = 'Operations';

    protected ?string $description = 'Current backlog and recent failures.';

    protected function getStats(): array
    {
        return [
            Stat::make('Pending scrape requests', ScrapeRequest::query()->pending()->count())
                ->icon(Heroicon::DocumentMagnifyingGlass)
                ->color('warning'),
            Stat::make('Pending downloads', Post::query()->pendingDownload()->count())
                ->icon(Heroicon::ArrowDownTray)
                ->color('warning'),
            Stat::make('Failed scrape requests (24h)', ScrapeRequest::query()
                ->where('status', ScrapeRequest::STATUS_FAILED)
                ->where('updated_at', '>=', now()->subDay())
                ->count())
                ->icon(Heroicon::ExclamationTriangle)
                ->color('danger'),
            Stat::make('Failed downloads (24h)', Post::query()
                ->where('download_status', Post::STATUS_FAILED)
                ->where('updated_at', '>=', now()->subDay())
                ->count())
                ->icon(Heroicon::ExclamationCircle)
                ->color('danger'),
        ];
    }
}
