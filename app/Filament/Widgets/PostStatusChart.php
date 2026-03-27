<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\ChartWidget;

class PostStatusChart extends ChartWidget
{
    protected ?string $heading = 'Post download status';

    protected ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        $counts = Post::query()
            ->selectRaw('download_status, count(*) as aggregate')
            ->groupBy('download_status')
            ->pluck('aggregate', 'download_status');

        $labels = [
            'Pending',
            'Downloading',
            'Failed',
            'Skipped',
            'Downloaded',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Posts',
                    'data' => [
                        (int) ($counts[Post::STATUS_PENDING] ?? 0),
                        (int) ($counts[Post::STATUS_DOWNLOADING] ?? 0),
                        (int) ($counts[Post::STATUS_FAILED] ?? 0),
                        (int) ($counts[Post::STATUS_SKIPPED] ?? 0),
                        (int) ($counts[Post::STATUS_DOWNLOADED] ?? 0),
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
