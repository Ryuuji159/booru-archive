<?php

namespace App\Jobs;

use App\Actions\ProcessPendingPostDownloadAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPendingPostDownload implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function uniqueId(): string
    {
        return 'process-pending-post-download';
    }

    public function handle(ProcessPendingPostDownloadAction $action): void
    {
        $action->handle();
    }
}
