<?php
/**
 * Check if your Clever Cloud deployment method is configured
 */

echo "\n=== Clever Cloud Deployment Check ===\n\n";

// Check for git remotes
echo "1. Checking Git remotes...\n";
exec('git remote -v', $remotes);
$hasClever = false;
foreach ($remotes as $remote) {
    echo "   $remote\n";
    if (strpos($remote, 'clever-cloud') !== false) {
        $hasClever = true;
    }
}

if ($hasClever) {
    echo "   ✅ Clever Cloud git remote found!\n";
    echo "   → Deploy with: git push clever main\n\n";
} else {
    echo "   ⚠️  No Clever Cloud git remote found.\n\n";
}

// Check for Clever CLI
echo "2. Checking Clever CLI...\n";
exec('clever --version 2>&1', $cliOutput, $cliReturn);
if ($cliReturn === 0) {
    echo "   ✅ Clever CLI installed: " . implode(' ', $cliOutput) . "\n";
    echo "   → Deploy with: clever deploy\n\n";
} else {
    echo "   ⚠️  Clever CLI not installed.\n\n";
}

// Check for .clever.json
echo "3. Checking Clever config...\n";
if (file_exists('.clever.json')) {
    echo "   ✅ .clever.json found\n";
    $config = json_decode(file_get_contents('.clever.json'), true);
    echo "   Config: " . json_encode($config, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "   ⚠️  .clever.json not found.\n\n";
}

// Summary
echo "=== Summary ===\n";
if ($hasClever) {
    echo "✅ You can deploy using: git push clever main\n";
} elseif ($cliReturn === 0) {
    echo "✅ You can deploy using: clever deploy\n";
} else {
    echo "❌ No automatic deployment method found.\n";
    echo "   You need to:\n";
    echo "   1. Go to Clever Cloud console\n";
    echo "   2. Find your app's deployment settings\n";
    echo "   3. Get FTP or Git credentials\n";
}

echo "\n";
