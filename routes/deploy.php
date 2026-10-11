<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Deployment Routes (REMOVE AFTER DEPLOYMENT)
|--------------------------------------------------------------------------
| These routes help deploy to Clever Cloud via web browser
| DELETE THIS FILE after successful deployment for security!
*/

Route::get('/deploy/migrate', function () {
    if (app()->environment('production')) {
        return 'Disabled in production. Use CLI instead.';
    }
    
    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        return '<pre>' . $output . '</pre>';
    } catch (\Exception $e) {
        return '<pre>Error: ' . $e->getMessage() . '</pre>';
    }
});

Route::get('/deploy/seed', function () {
    if (app()->environment('production')) {
        return 'Disabled in production. Use CLI instead.';
    }
    
    try {
        Artisan::call('db:seed', [
            '--class' => 'QuestBuildingSeeder',
            '--force' => true
        ]);
        $output = Artisan::output();
        return '<pre>' . $output . '</pre>';
    } catch (\Exception $e) {
        return '<pre>Error: ' . $e->getMessage() . '</pre>';
    }
});

Route::get('/deploy/status', function () {
    return '<h1>Quest Building Deployment</h1>
    <p><strong>Environment:</strong> ' . app()->environment() . '</p>
    <p><strong>Database:</strong> ' . config('database.default') . '</p>
    <p><strong>Host:</strong> ' . config('database.connections.mysql.host') . '</p>
    <hr>
    <h2>Deployment Steps:</h2>
    <ol>
        <li><a href="/deploy/migrate">Run Migrations</a> - Creates all database tables</li>
        <li><a href="/deploy/seed">Run Seeder</a> - Populates Quest Building data</li>
    </ol>
    <p><em>After deployment, DELETE routes/deploy.php for security!</em></p>';
});
