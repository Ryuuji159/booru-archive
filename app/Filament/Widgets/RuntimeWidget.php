<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

class RuntimeWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -4;

    protected ?string $heading = 'Runtime';

    protected ?string $description = 'Request handling and storage defaults.';

    protected function getStats(): array
    {
        return [
            Stat::make('Runtime', $this->getRuntimeLabel())
                ->description($this->getRuntimeDescription())
                ->descriptionColor($this->isRunningUnderOctane() ? 'success' : 'gray')
                ->icon($this->isRunningUnderOctane() ? Heroicon::Bolt : Heroicon::ComputerDesktop)
                ->color($this->isRunningUnderOctane() ? 'success' : 'gray'),
            Stat::make('Octane server', $this->getOctaneServerLabel())
                ->description($this->isRunningUnderOctane() ? 'Enabled for web requests.' : 'Octane is not handling this request.')
                ->descriptionColor($this->isRunningUnderOctane() ? 'success' : 'warning')
                ->icon(Heroicon::CircleStack)
                ->color($this->isRunningUnderOctane() ? 'success' : 'warning'),
            Stat::make('Queue connection', (string) config('queue.default', 'database'))
                ->description('Current queue driver.')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray'),
            Stat::make('Cache store', (string) config('cache.default', 'database'))
                ->description('Current cache driver.')
                ->icon(Heroicon::ArchiveBox)
                ->color('gray'),
        ];
    }

    private function getRuntimeLabel(): string
    {
        if ($this->isRunningUnderOctane()) {
            return 'Octane';
        }

        if (php_sapi_name() === 'cli-server') {
            return 'PHP built-in server';
        }

        if (app()->runningInConsole()) {
            return 'Console';
        }

        return 'PHP-FPM';
    }

    private function getRuntimeDescription(): string
    {
        if ($this->isRunningUnderOctane()) {
            return 'This request is being served by Octane.';
        }

        if (php_sapi_name() === 'cli-server') {
            return 'Local development server.';
        }

        if (app()->runningInConsole()) {
            return 'Command-line execution.';
        }

        return 'Standard PHP request lifecycle.';
    }

    private function getOctaneServerLabel(): string
    {
        if (! $this->isRunningUnderOctane()) {
            return 'Disabled';
        }

        return match ((string) config('octane.server', 'frankenphp')) {
            'frankenphp' => 'FrankenPHP',
            'roadrunner' => 'RoadRunner',
            'swoole' => 'Swoole',
            default => Str::headline((string) config('octane.server', 'frankenphp')),
        };
    }

    private function isRunningUnderOctane(): bool
    {
        return filled($_SERVER['LARAVEL_OCTANE'] ?? $_ENV['LARAVEL_OCTANE'] ?? null);
    }
}
