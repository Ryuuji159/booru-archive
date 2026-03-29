<?php

namespace App\Filament\Widgets;

use App\Services\KonachanDownloadRateLimit;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class DownloadRateLimitWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'Download rate limit';

    protected ?string $description = 'Current cooldown for Konachan file and preview requests.';

    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $rateLimit = app(KonachanDownloadRateLimit::class);
        $blocked = $rateLimit->isBlocked();
        $availableIn = $rateLimit->availableIn();
        $cooldownSeconds = $rateLimit->cooldownSeconds();
        $nextAvailableAt = $rateLimit->nextAvailableAt();

        return [
            Stat::make('State', $blocked ? 'Blocked' : 'Ready')
                ->description($blocked
                    ? 'The next source request must wait for the cooldown window to expire.'
                    : 'A new source request can be issued now.')
                ->descriptionColor($blocked ? 'danger' : 'success')
                ->icon($blocked ? Heroicon::ExclamationTriangle : Heroicon::Bolt)
                ->color($blocked ? 'danger' : 'success'),
            Stat::make('Retry in', $blocked ? Number::format($availableIn).'s' : '0s')
                ->description($nextAvailableAt ? 'Next slot at '.$nextAvailableAt->format('H:i:s') : 'No wait time is active.')
                ->icon(Heroicon::ArrowPath)
                ->color('gray'),
            Stat::make('Cooldown', Number::format($cooldownSeconds).'s')
                ->description('Configured `services.konachan.download_request_cooldown_seconds`.')
                ->icon(Heroicon::CircleStack)
                ->color('gray'),
            Stat::make('Attempts in window', Number::format($rateLimit->attempts()))
                ->description('Rate limiter hits recorded for the current key.')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray'),
        ];
    }
}
