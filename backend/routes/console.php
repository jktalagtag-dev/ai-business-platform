<?php

use App\Application\Jobs\Automation\RunScheduledWorkflowsJob;
use App\Application\Jobs\Ticket\SlaMonitoringJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SlaMonitoringJob)->everyFifteenMinutes();
Schedule::job(new RunScheduledWorkflowsJob)->everyMinute();
