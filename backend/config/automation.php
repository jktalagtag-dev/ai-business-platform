<?php

return [

    // Applied to every automation_jobs row created for an event-triggered
    // or scheduled workflow run — see ExecuteWorkflowJob's self-managed
    // retry (attempts/max_attempts), not Laravel's queue-level $tries.
    'default_max_attempts' => (int) env('AUTOMATION_DEFAULT_MAX_ATTEMPTS', 3),

];
