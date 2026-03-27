<?php

use App\Jobs\ProcessPendingPostDownload;
use App\Jobs\ProcessPendingScrapeRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessPendingScrapeRequest)->everyMinute();
Schedule::job(new ProcessPendingPostDownload)->everyMinute();
