<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class MediaUsageWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'Storage';

    protected ?string $description = 'Media disk usage.';

    protected function getStats(): array
    {
        $usage = Cache::remember('dashboard:media-usage', now()->addMinutes(5), function (): array {
            return $this->calculateMediaUsage();
        });

        return [
            Stat::make('Space used', Number::fileSize($usage['bytes']))
                ->description($usage['files'].' files on the media disk.')
                ->descriptionColor('gray')
                ->icon(Heroicon::CircleStack)
                ->color('gray'),
        ];
    }

    /**
     * @return array{bytes: int, files: int}
     */
    private function calculateMediaUsage(): array
    {
        $root = config('filesystems.disks.'.config('filesystems.media_disk', 'media').'.root');

        if (! is_string($root) || ! File::isDirectory($root)) {
            return [
                'bytes' => 0,
                'files' => 0,
            ];
        }

        $bytes = 0;
        $files = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $bytes += $file->getSize() ?: 0;
                $files++;
            }
        } catch (Throwable) {
            return [
                'bytes' => 0,
                'files' => 0,
            ];
        }

        return [
            'bytes' => $bytes,
            'files' => $files,
        ];
    }
}
