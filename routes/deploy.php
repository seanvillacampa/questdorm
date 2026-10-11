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
    // Security: Check for deploy key or allow in non-production
    $deployKey = request()->query('key');
    $expectedKey = env('DEPLOY_KEY', 'quest2026deploy');
    
    if (app()->environment('production') && $deployKey !== $expectedKey) {
        return '<h1>Access Denied</h1><p>Invalid or missing deploy key.</p>';
    }
    
    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        return '<h1>✅ Migrations Complete</h1><pre>' . $output . '</pre><hr><p><a href="/deploy/status">Back to Deployment Status</a></p>';
    } catch (\Exception $e) {
        return '<h1>❌ Migration Error</h1><pre>Error: ' . $e->getMessage() . '</pre>';
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
    $deployKey = env('DEPLOY_KEY', 'quest2026deploy');
    return '<html><head><title>Quest Building Deployment</title><style>
    body { font-family: system-ui, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    h1 { color: #2563eb; }
    .success { background: #dcfce7; border-left: 4px solid #16a34a; padding: 15px; margin: 10px 0; }
    .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 10px 0; }
    .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 10px 0; }
    a { color: #2563eb; text-decoration: none; font-weight: 600; }
    a:hover { text-decoration: underline; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style></head><body>
    <h1>🚀 Quest Building Deployment</h1>
    <div class="info">
        <strong>Environment:</strong> ' . app()->environment() . '<br>
        <strong>Database:</strong> ' . config('database.default') . '<br>
        <strong>Host:</strong> ' . config('database.connections.mysql.host') . '
    </div>
    <hr>
    <h2>📋 Deployment Steps:</h2>
    <div class="warning">
        <strong>⚠️ Important:</strong> If running on production (Render/Clever Cloud), append <code>?key=' . $deployKey . '</code> to migration URL.
    </div>
    <ol style="line-height: 2;">
        <li><a href="/deploy/migrate?key=' . $deployKey . '">Run Migrations</a> - Creates/updates all database tables (including middle_name nullable fix)</li>
        <li><a href="/deploy/seed?key=' . $deployKey . '">Run Seeder</a> - Populates Quest Building data (only if needed)</li>
    </ol>
    <div class="success">
        <strong>✅ Current Issue Fix:</strong> The migration will make <code>middle_name</code> nullable, fixing the integrity constraint error.
    </div>
    <hr>
    <p><em><strong>Security Note:</strong> Delete <code>routes/deploy.php</code> after successful deployment!</em></p>
    </body></html>';
});
