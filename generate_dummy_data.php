<?php
/**
 * Quest Building Dummy Data Generator
 * Generates complete SQL for Jan 2025 - Oct 11, 2026
 * Run: php generate_dummy_data.php > rental_management_dummy_data_complete.sql
 */

// Contract configurations [contract_id => [room_id, start_date, due_day, monthly_rate, tenant_count, tenant_ids]]
$contracts = [
    1 => [1, '2025-01-15', 15, 4500, 3, [1,2,3]],
    2 => [2, '2025-01-10', 10, 4000, 2, [4,5]],
    3 => [3, '2025-01-12', 12, 4500, 3, [6,7,8]],
    4 => [4, '2025-02-01', 1, 4000, 2, [9,10]],
    5 => [5, '2025-01-20', 20, 4500, 2, [11,12]],
    6 => [6, '2025-02-15', 15, 4000, 1, [13]],
    7 => [7, '2025-03-01', 1, 4500, 2, [14,15]],
    8 => [8, '2025-01-25', 25, 4000, 2, [16,17]],
    9 => [9, '2025-02-10', 10, 4500, 1, [18]],
    10 => [10, '2025-03-15', 15, 4000, 2, [19,20]],
    11 => [11, '2025-01-18', 18, 4000, 1, [21]],
    12 => [12, '2025-01-08', 8, 4800, 2, [22,23]],
    13 => [13, '2025-02-20', 20, 4200, 2, [24,25]],
    14 => [14, '2025-01-05', 5, 4800, 3, [26,27,28]],
    15 => [15, '2025-03-10', 10, 4200, 1, [29]],
    16 => [16, '2025-02-05', 5, 4800, 2, [30,31]],
    17 => [17, '2025-01-22', 22, 4200, 2, [32,33]],
    18 => [18, '2025-01-14', 14, 5000, 2, [34,35]],
    19 => [19, '2025-02-25', 25, 4500, 1, [36]],
    20 => [20, '2025-01-30', 30, 5000, 3, [37,38,39]],
    21 => [21, '2025-03-05', 5, 4500, 2, [40,41]],
    22 => [22, '2025-02-12', 12, 5000, 2, [42,43]],
    23 => [23, '2025-01-28', 28, 4500, 2, [44,45]],
    24 => [24, '2025-01-16', 16, 5200, 2, [1,2]],
    25 => [25, '2025-03-20', 20, 4800, 1, [3]],
    26 => [26, '2025-02-08', 8, 5200, 3, [4,5,6]],
    27 => [27, '2025-01-11', 11, 4800, 2, [7,8]],
    28 => [28, '2025-02-18', 18, 5200, 2, [9,10]],
];

$invoiceId = 1;
$paymentId = 1;
$tenantPaymentId = 1;
$meterReadingId = 1;

$electricityRate = 12.50;
$currentDate = new DateTime('2026-10-11'); // Today

echo "-- ============================================================================\n";
echo "-- METER READINGS, INVOICES, TENANT_PAYMENTS, PAYMENTS\n";
echo "-- Period: January 2025 - October 11, 2026\n";
echo "-- All Jan 2025 - Jul 2026 PAID, Aug-Oct 2026 MIXED\n";
echo "-- ============================================================================\n\n";

foreach ($contracts as $contractId => $contractInfo) {
    list($roomId, $startDate, $dueDay, $monthlyRate, $tenantCount, $tenantIds) = $contractInfo;
    
    $contractStart = new DateTime($startDate);
    $billingStart = new DateTime($startDate);
    
    // Calculate first billing month (month after contract starts)
    $billingStart->modify('first day of next month');
    
    $roomNumber = 100 + $roomId;
    if ($roomId > 11) $roomNumber = 200 + ($roomId - 11);
    if ($roomId > 17) $roomNumber = 300 + ($roomId - 17);
    if ($roomId > 23) $roomNumber = 400 + ($roomId - 23);
    
    echo "-- ============================================================================\n";
    echo "-- Contract $contractId (Room $roomNumber) - $tenantCount tenant(s)\n";
    echo "-- ============================================================================\n\n";
    
    $billingMonth = clone $billingStart;
    
    while ($billingMonth <= $currentDate) {
        $year = $billingMonth->format('Y');
        $month = $billingMonth->format('m');
        $monthName = $billingMonth->format('F Y');
        
        // Invoice number format: BILL-YYYYMM-XXXX
        $invoiceNumber = sprintf("BILL-%s%s-%04d", $year, $month, $invoiceId);
        
        // Calculate due date
        $dueDate = new DateTime("{$year}-{$month}-{$dueDay}");
        if ($dueDay > $billingMonth->format('t')) {
            $dueDate = new DateTime($billingMonth->format('Y-m-t'));
        }
        
        // Previous and current meter reading dates
        $prevReadingDate = (clone $billingMonth)->modify('-1 month')->modify('last day of this month');
        $currentReadingDate = (clone $billingMonth)->modify('last day of this month');
        
        // Generate realistic meter readings (150-350 kWh per month)
        $prevReading = 1000 + ($invoiceId * 250) + rand(0, 100);
        $currentReading = $prevReading + rand(150, 350);
        $kwh = $currentReading - $prevReading;
        $electricityAmount = $kwh * $electricityRate;
        
        // Total invoice amount
        $totalAmount = $monthlyRate + $electricityAmount;
        $shareAmount = $totalAmount / $tenantCount;
        
        // Determine invoice status based on period
        $isPaidPeriod = ($billingMonth < new DateTime('2026-08-01'));
        
        if ($isPaidPeriod) {
            // Jan 2025 - Jul 2026: ALL PAID
            $status = 'paid';
            $amountPaid = $totalAmount;
            $paidDate = (clone $dueDate)->modify('+' . rand(1, 3) . ' days');
        } else {
            // Aug 2026 - Oct 2026: MIXED statuses
            $rand = rand(1, 100);
            if ($rand <= 40) {
                // 40% paid
                $status = 'paid';
                $amountPaid = $totalAmount;
                $paidDate = (clone $dueDate)->modify('+' . rand(1, 5) . ' days');
            } elseif ($rand <= 65) {
                // 25% partial
                $status = 'partial';
                $amountPaid = round($totalAmount * (rand(30, 70) / 100), 2);
                $paidDate = (clone $dueDate)->modify('+' . rand(1, 3) . ' days');
            } elseif ($rand <= 85) {
                // 20% pending
                $status = 'pending';
                $amountPaid = 0;
                $paidDate = null;
            } else {
                // 15% overdue
                $status = 'overdue';
                $amountPaid = 0;
                $paidDate = null;
            }
            
            // Ensure no tenant exceeds 20k outstanding balance
            // For simplicity, limit partial payments to keep balance under 20k
            if ($status === 'partial' || $status === 'pending' || $status === 'overdue') {
                $maxOutstanding = 20000 / $tenantCount;
                if (($totalAmount - $amountPaid) > $maxOutstanding) {
                    // Pay more to stay under limit
                    $amountPaid = $totalAmount - $maxOutstanding + rand(1000, 3000);
                    if ($amountPaid >= $totalAmount) {
                        $amountPaid = $totalAmount;
                        $status = 'paid';
                    } else {
                        $status = 'partial';
                    }
                }
            }
        }
        
        // Meter readings
        echo "-- Meter readings for $monthName\n";
        echo "INSERT INTO `meter_readings` (`id`, `room_id`, `reading_date`, `reading_value`, `previous_reading`, `kwh_used`, `created_at`, `updated_at`) VALUES\n";
        echo "($meterReadingId, $roomId, '{$prevReadingDate->format('Y-m-d')}', $prevReading, " . ($prevReading - 250) . ", 250, '{$prevReadingDate->format('Y-m-d H:i:s')}', '{$prevReadingDate->format('Y-m-d H:i:s')}'),\n";
        $meterReadingId++;
        echo "($meterReadingId, $roomId, '{$currentReadingDate->format('Y-m-d')}', $currentReading, $prevReading, $kwh, '{$currentReadingDate->format('Y-m-d H:i:s')}', '{$currentReadingDate->format('Y-m-d H:i:s')}');\n\n";
        $meterReadingId++;
        
        // Invoice
        echo "-- Invoice for $monthName\n";
        echo "INSERT INTO `invoices` (`id`, `invoice_number`, `contract_id`, `tenant_count`, `billing_month`, `due_date`, `rent_amount`, `electricity_amount`, `total_amount`, `amount_paid`, `status`, `created_at`, `updated_at`) VALUES\n";
        echo "($invoiceId, '$invoiceNumber', $contractId, $tenantCount, '$year-$month-01', '{$dueDate->format('Y-m-d')}', $monthlyRate, " . round($electricityAmount, 2) . ", " . round($totalAmount, 2) . ", $amountPaid, '$status', '{$billingMonth->format('Y-m-d H:i:s')}', '{$currentDate->format('Y-m-d H:i:s')}');\n\n";
        
        // Tenant payments
        echo "-- Tenant payment shares for Invoice $invoiceNumber\n";
        foreach ($tenantIds as $index => $tenantId) {
            $tenantShareAmount = round($shareAmount, 2);
            
            // Adjust last tenant's share for rounding
            if ($index === count($tenantIds) - 1) {
                $totalShares = round($shareAmount * (count($tenantIds) - 1), 2);
                $tenantShareAmount = round($totalAmount - $totalShares, 2);
            }
            
            // Calculate tenant's paid amount based on invoice status
            if ($status === 'paid') {
                $tenantAmountPaid = $tenantShareAmount;
                $tenantStatus = 'paid';
                $carryOver = 0;
            } elseif ($status === 'partial') {
                // Distribute partial payment evenly
                $tenantAmountPaid = round($amountPaid / $tenantCount, 2);
                if ($index === count($tenantIds) - 1) {
                    $totalPaid = round(($amountPaid / $tenantCount) * (count($tenantIds) - 1), 2);
                    $tenantAmountPaid = round($amountPaid - $totalPaid, 2);
                }
                $tenantStatus = 'partial';
                $carryOver = round($tenantShareAmount - $tenantAmountPaid, 2);
            } else {
                $tenantAmountPaid = 0;
                $tenantStatus = $status;
                $carryOver = 0;
            }
            
            echo "INSERT INTO `tenant_payments` (`id`, `invoice_id`, `tenant_id`, `share_amount`, `amount_paid`, `status`, `carry_over_balance`, `created_at`, `updated_at`) VALUES\n";
            echo "($tenantPaymentId, $invoiceId, $tenantId, $tenantShareAmount, $tenantAmountPaid, '$tenantStatus', $carryOver, '{$billingMonth->format('Y-m-d H:i:s')}', '{$currentDate->format('Y-m-d H:i:s')}');\n";
            $tenantPaymentId++;
        }
        echo "\n";
        
        // Payment records (only if amount was paid)
        if ($amountPaid > 0 && $paidDate) {
            echo "-- Payment record for Invoice $invoiceNumber\n";
            echo "INSERT INTO `payments` (`id`, `invoice_id`, `amount`, `payment_method`, `payment_date`, `reference_number`, `created_at`, `updated_at`) VALUES\n";
            $paymentMethod = rand(1, 100) <= 70 ? 'cash' : (rand(1, 2) == 1 ? 'bank_transfer' : 'gcash');
            $referenceNumber = $paymentMethod === 'cash' ? 'CASH-' . strtoupper(substr(md5($invoiceId), 0, 8)) : 'REF-' . strtoupper(substr(md5($invoiceId . 'pay'), 0, 12));
            echo "($paymentId, $invoiceId, $amountPaid, '$paymentMethod', '{$paidDate->format('Y-m-d H:i:s')}', '$referenceNumber', '{$paidDate->format('Y-m-d H:i:s')}', '{$paidDate->format('Y-m-d H:i:s')}');\n\n";
            $paymentId++;
        }
        
        echo "\n";
        
        $invoiceId++;
        $billingMonth->modify('+1 month');
    }
}

echo "-- ============================================================================\n";
echo "-- END OF DATA GENERATION\n";
echo "-- Total Invoices: " . ($invoiceId - 1) . "\n";
echo "-- Total Payments: " . ($paymentId - 1) . "\n";
echo "-- Total Tenant Payments: " . ($tenantPaymentId - 1) . "\n";
echo "-- Total Meter Readings: " . ($meterReadingId - 1) . "\n";
echo "-- ============================================================================\n";
