<?php
/**
 * Schema verification - outputs the exact columns needed for SQL
 */

echo "=== CONTRACTS TABLE (FINAL SCHEMA) ===\n";
echo "Columns: id, room_id, start_date, end_date, monthly_rent, due_day, status, contract_file_path, created_by, timestamps\n";
echo "NOTE: NO deposit_required, NO deposit_collected (removed by migration 2026_10_07_080444)\n\n";

echo "=== USERS TABLE (FINAL SCHEMA) ===\n";
echo "Columns: id, name, first_name, middle_name, last_name, email, email_verified_at, phone, phone_country_code, password, is_active, last_login_at, remember_token, timestamps\n";
echo "NOTE: NO role column (uses Spatie)\n\n";

echo "=== ROOMS TABLE (FINAL SCHEMA) ===\n";
echo "Columns: id, room_number, floor, capacity, monthly_rate, deposit_required, meter_number, status, timestamps\n";
echo "NOTE: deposit_required added by migration 2026_10_07_080424\n\n";

echo "=== INVOICES TABLE (FINAL SCHEMA) ===\n";
echo "Columns: id, invoice_number, contract_id, tenant_count, billing_month, due_date, rent_amount, electricity_amount, total_amount, amount_paid, status, paid_at, created_by, timestamps\n\n";

echo "=== TENANT_PAYMENTS TABLE ===\n";
echo "Columns: id, invoice_id, tenant_id, share_amount, amount_paid, status, carry_over_balance, timestamps\n\n";

echo "=== TENANT_DEPOSITS TABLE ===\n";
echo "Columns: id, contract_id, tenant_id, amount_required, amount_paid, amount_deducted, amount_refunded, deduction_reason, notes, timestamps\n\n";

echo "=== METER_READINGS TABLE ===\n";
echo "Columns: id, room_id, reading_date, reading_value, previous_reading, kwh_used, timestamps\n\n";

echo "=== CORRECT SQL INSERT STATEMENTS ===\n\n";

echo "-- CONTRACTS (NO deposit columns!)\n";
echo "INSERT INTO `contracts` (`id`, `room_id`, `start_date`, `end_date`, `monthly_rent`, `due_day`, `status`, `contract_file_path`, `created_by`, `created_at`, `updated_at`) VALUES\n";
echo "(1, 1, '2025-12-15', '2027-12-31', 4500.00, 15, 'active', NULL, NULL, '2025-12-15 00:00:00', '2025-12-15 00:00:00');\n\n";

echo "-- ROOMS (WITH deposit_required!)\n";
echo "INSERT INTO `rooms` (`id`, `room_number`, `floor`, `capacity`, `monthly_rate`, `deposit_required`, `meter_number`, `status`, `created_at`, `updated_at`) VALUES\n";
echo "(1, '101', 1, 3, 4500.00, 9000.00, 'MTR-101', 'occupied', '2024-12-01 00:00:00', '2025-01-15 00:00:00');\n\n";
