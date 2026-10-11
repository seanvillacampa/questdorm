<?php
// Fix the users table structure in the SQL file

$inputFile = 'rental_management_dummy_data.sql';
$outputFile = 'rental_management_dummy_data_fixed.sql';

$handle = fopen($inputFile, 'r');
$output = fopen($outputFile, 'w');

if ($handle && $output) {
    while (($line = fgets($handle)) !== false) {
        // Fix the INSERT INTO users header
        if (strpos($line, "INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone_number`, `emergency_contact`, `role`, `is_active`, `created_at`, `updated_at`) VALUES") !== false) {
            $line = "INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `phone_country_code`, `is_active`, `created_at`, `updated_at`) VALUES\n";
        }
        
        // Fix user data rows that have phone_number, emergency_contact, 'tenant'
        // Pattern: '09171234XXX', '09187654XXX', 'tenant', 1,
        // Replace with: '09171234XXX', '+63', 1,
        if (preg_match("/'(\d{11})', '(\d{11})', 'tenant', (\d+),/", $line)) {
            $line = preg_replace(
                "/'(\d{11})', '(\d{11})', 'tenant', (\d+),/",
                "'$1', '+63', $3,",
                $line
            );
        }
        
        fputs($output, $line);
    }
    
    fclose($handle);
    fclose($output);
    
    echo "✓ Fixed SQL file saved as: $outputFile\n";
    echo "✓ Now add role assignments after the file\n";
} else {
    echo "❌ Error: Could not open files\n";
}
