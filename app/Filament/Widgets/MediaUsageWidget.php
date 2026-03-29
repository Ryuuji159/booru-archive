<?php

namespace App\Filament\Widgets;

use App\Services\MediaUsage;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;

class MediaUsageWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -6;

    protected ?string $heading = 'Storage';

    protected ?string $description = 'Media disk usage.';

    protected function getStats(): array
    {
        $usage = Cache::remember('dashboard:media-usage', now()->addMinutes(5), function (): array {
            return app(MediaUsage::class)->calculate();
        });
        $mediaDisk = (string) config('filesystems.media_disk', 'media');
        $mediaRoot = config('filesystems.disks.'.$mediaDisk.'.root');

        return [
            Stat::make('Space used', Number::fileSize($usage['bytes'], 2))
                ->description('Used space on the media disk.')
                ->descriptionColor('gray')
                ->icon(Heroicon::CircleStack)
                ->color('gray'),
            Stat::make('Files', Number::format($usage['files'], 0))
                ->description('Files on the media disk.')
                ->descriptionColor('gray')
                ->icon(Heroicon::DocumentText)
                ->color('gray'),
            Stat::make('Disk', $mediaDisk)
                ->description($this->formatMediaRoot($mediaRoot))
                ->descriptionColor('gray')
                ->icon(Heroicon::RectangleStack)
                ->color('gray'),
        ];
    }

    private function formatMediaRoot(mixed $mediaRoot): string
    {
        if (! is_string($mediaRoot) || $mediaRoot === '') {
            return 'Media disk root is not configured.';
        }

        if (! File::isDirectory($mediaRoot)) {
            return 'Media root: '.$mediaRoot;
        }

        return 'Media root: '.$mediaRoot;
    }
}
