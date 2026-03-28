<?php

namespace App\Filament\Widgets;

use App\Models\ScrapeRequest;
use Filament\Widgets\ChartWidget;

class ScrapeRequestStatusChart extends ChartWidget
{
    protected static ?int $sort = -1;

    protected ?string $heading = 'Scrape request status';

    protected int|string|array $columnSpan = ['lg' => 1];

    protected ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        $counts = ScrapeRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [
            'Pending',
            'Running',
            'Failed',
            'Cancelled',
            'Completed',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => [
                        (int) ($counts[ScrapeRequest::STATUS_PENDING] ?? 0),
                        (int) ($counts[ScrapeRequest::STATUS_RUNNING] ?? 0),
                        (int) ($counts[ScrapeRequest::STATUS_FAILED] ?? 0),
                        (int) ($counts[ScrapeRequest::STATUS_CANCELLED] ?? 0),
                        (int) ($counts[ScrapeRequest::STATUS_COMPLETED] ?? 0),
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
