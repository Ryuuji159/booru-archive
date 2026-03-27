<?php

namespace App\Jobs;

use App\Actions\ProcessPendingScrapeRequestAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPendingScrapeRequest implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function uniqueId(): string
    {
        return 'process-pending-scrape-request';
    }

    public function handle(ProcessPendingScrapeRequestAction $action): void
    {
        $action->handle();
    }
}
