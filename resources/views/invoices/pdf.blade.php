<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Billing Statement {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e40af;
        }
        
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .invoice-info-left, .invoice-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .charges-table td {
            padding: 6px 8px;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f9fafb;
            border-top: 2px solid #ddd !important;
        }
        
        .balance-due {
            background-color: #fee2e2;
            color: #991b1b;
            font-size: 14px;
        }
        
        .balance-paid {
            background-color: #dcfce7;
            color: #166534;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-pending {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        
        .highlight {
            background-color: #fef3c7;
            padding: 10px;
            border-left: 3px solid #f59e0b;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Quest Building</h1>
        <p>Billing Statement</p>
    </div>
    
    <div class="invoice-info">
        <div class="invoice-info-left">
            <div class="info-label">Statement Number</div>
            <div class="info-value">{{ $invoice->invoice_number }}</div>
            
            <div class="info-label">Room</div>
            <div class="info-value">Room {{ $invoice->contract->room->room_number }} - Floor {{ $invoice->contract->room->floor }}</div>
            
            <div class="info-label">Number of Tenants</div>
            <div class="info-value">{{ $invoice->tenant_count }} tenant(s)</div>
        </div>
        
        <div class="invoice-info-right">
            <div class="info-label">Billing Month</div>
            <div class="info-value">{{ now()->parse($invoice->billing_month)->format('F Y') }}</div>
            
            <div class="info-label">Due Date</div>
            <div class="info-value">{{ $invoice->due_date->format('F d, Y') }}</div>
            
            <div class="info-label">Status</div>
            <div class="info-value">
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>
    </div>
    
    <div class="section-title">Room Charges</div>
    <table class="charges-table">
        <tr>
            <td><strong>Monthly Rent</strong></td>
            <td class="text-right">₱{{ number_format($invoice->rent_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Electricity</strong></td>
            <td class="text-right">₱{{ number_format($invoice->electricity_amount, 2) }}</td>
        </tr>
        
        @if(($invoice->carry_over_balance ?? 0) > 0)
        <tr style="color: #991b1b;">
            <td><strong>Unpaid balance from previous month</strong></td>
            <td class="text-right">+ ₱{{ number_format($invoice->carry_over_balance, 2) }}</td>
        </tr>
        @endif
        
        @if(($invoice->credit_balance ?? 0) > 0)
        <tr style="color: #166534;">
            <td><strong>Overpayment credit from previous month</strong></td>
            <td class="text-right">− ₱{{ number_format($invoice->credit_balance, 2) }}</td>
        </tr>
        @endif
        
        <tr class="total-row">
            <td><strong>Total Room Bill</strong></td>
            <td class="text-right">₱{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Per Tenant Share</td>
            <td class="text-right">₱{{ number_format($invoice->effectiveSharePerTenant(), 2) }}</td>
        </tr>
        <tr style="color: #166534;">
            <td><strong>Amount Paid</strong></td>
            <td class="text-right">₱{{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr class="{{ $invoice->balanceDue() > 0 ? 'balance-due' : 'balance-paid' }}">
            <td><strong>Balance {{ $invoice->balanceDue() > 0 ? 'Due' : '' }}</strong></td>
            <td class="text-right"><strong>₱{{ number_format($invoice->balanceDue(), 2) }}</strong></td>
        </tr>
    </table>
    
    <div class="section-title">Tenant Payment Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th class="text-right">Rent Share</th>
                <th class="text-right">Electricity Share</th>
                <th class="text-right">Total Share</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->tenantPayments as $tp)
            <tr>
                <td>{{ $tp->tenant->user->name }}</td>
                <td class="text-right">₱{{ number_format($invoice->rentPerTenant(), 2) }}</td>
                <td class="text-right">₱{{ number_format($invoice->electricityPerTenant(), 2) }}</td>
                <td class="text-right">₱{{ number_format($tp->share_amount, 2) }}</td>
                <td class="text-right">{{ $tp->amount_paid > 0 ? '₱'.number_format($tp->amount_paid, 2) : '—' }}</td>
                <td class="text-center">
                    <span class="status-badge status-{{ $tp->status }}">{{ ucfirst($tp->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    @if($invoice->payments->isNotEmpty())
    <div class="section-title">Payment History</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Tenant</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $p)
            <tr>
                <td>{{ $p->received_at->format('M d, Y') }}</td>
                <td>{{ $p->tenantPayment?->tenant->user->name ?? '—' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $p->method)) }}</td>
                <td>{{ $p->reference ?? '—' }}</td>
                <td class="text-right">₱{{ number_format($p->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    @if($invoice->balanceDue() > 0)
    <div class="highlight">
        <strong>Payment Instructions:</strong><br>
        Please make payment by {{ $invoice->due_date->format('F d, Y') }} to avoid late fees. 
        Contact the management office for payment methods and details.
    </div>
    @endif
    
    <div class="footer">
        <p>Quest Building Dormitory Management System</p>
        <p>Generated on {{ now('Asia/Manila')->format('F d, Y g:i A') }}</p>
    </div>
</body>
</html>
