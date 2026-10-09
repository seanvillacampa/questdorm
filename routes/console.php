<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Daily scheduler — two commands, run in sequence each morning.
 *
 * On Windows dev: php artisan schedule:run
 * In production:  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 *
 * Order matters:
 *  1. generate-scheduled — creates invoices X days before each contract's due date
 *                          and emails tenants "Your bill is ready, due in X days"
 *  2. process-statuses   — flips pending→late→overdue and sends missed/overdue emails
 */
Schedule::command('invoices:generate-scheduled')->dailyAt('00:01');
Schedule::command('invoices:process-statuses')->dailyAt('00:05');
