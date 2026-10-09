<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Quest Building Report — {{ now()->parse($month)->format('F Y') }}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 32px; }
  h1   { font-size: 20px; margin-bottom: 4px; }
  h2   { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
  .meta { color: #666; font-size: 11px; margin-bottom: 24px; }
  .cards { display: flex; gap: 24px; margin-bottom: 24px; }
  .card  { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; flex: 1; }
  .card p.label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 4px; }
  .card p.value { font-size: 22px; font-weight: bold; margin: 0; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th    { text-align: left; font-size: 10px; text-transform: uppercase; color: #9ca3af; letter-spacing: .05em; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
  td    { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
  tr:last-child td { border-bottom: none; }
  .right { text-align: right; }
  .status-paid     { color: #16a34a; font-weight: 600; }
  .status-overdue  { color: #dc2626; font-weight: 600; }
  .status-partial  { color: #ea580c; font-weight: 600; }
  .status-pending  { color: #6b7280; }
  @media print { body { margin: 16px; } }
</style>
</head>
<body>

<h1>Quest Building — Income Report</h1>
<p class="meta">
  Period: {{ now()->parse($month)->format('F Y') }} &nbsp;|&nbsp;
  Generated: {{ now()->format('M d, Y g:i A') }}
</p>

<div class="cards">
  <div class="card">
    <p class="label">Total billed</p>
    <p class="value">₱{{ number_format($totalBilled,2) }}</p>
  </div>
  <div class="card">
    <p class="label">Collected</p>
    <p class="value" style="color:#16a34a">₱{{ number_format($totalCollected,2) }}</p>
  </div>
  <div class="card">
    <p class="label">Uncollected</p>
    <p class="value" style="color:#dc2626">₱{{ number_format(max(0,$totalBilled-$totalCollected),2) }}</p>
  </div>
</div>

<h2>Payment Status Summary</h2>
<table>
  <tr>
    <th>Status</th><th class="right">Rooms</th><th class="right">Amount</th>
  </tr>
  @foreach(['paid','partial','pending','overdue'] as $s)
    @php $cnt = $statusCounts[$s] ?? 0; @endphp
    <tr>
      <td class="status-{{ $s }}">{{ ucfirst($s) }}</td>
      <td class="right">{{ $cnt }}</td>
      <td class="right">—</td>
    </tr>
  @endforeach
</table>

<h2>Invoice Details</h2>
<table>
  <tr>
    <th>Invoice #</th><th>Room</th><th>Tenants</th><th>Due Date</th>
    <th class="right">Rent</th><th class="right">Electricity</th>
    <th class="right">Total</th><th class="right">Paid</th><th class="right">Balance</th><th>Status</th>
  </tr>
  @foreach($invoices as $inv)
    @php
      $tenants = $inv->contract->tenants->map(fn($t)=>$t->user->name)->implode(', ');
      $sCls = match(true) {
        $inv->status === 'paid'    => 'status-paid',
        $inv->status === 'overdue' => 'status-overdue',
        str_starts_with($inv->status,'partial') => 'status-partial',
        default => 'status-pending',
      };
    @endphp
    <tr>
      <td>{{ $inv->invoice_number }}</td>
      <td>{{ $inv->contract->room->room_number }}</td>
      <td style="max-width:120px">{{ $tenants }}</td>
      <td>{{ $inv->due_date->format('M d, Y') }}</td>
      <td class="right">₱{{ number_format($inv->rent_amount,2) }}</td>
      <td class="right">₱{{ number_format($inv->electricity_amount,2) }}</td>
      <td class="right">₱{{ number_format($inv->total_amount,2) }}</td>
      <td class="right">₱{{ number_format($inv->amount_paid,2) }}</td>
      <td class="right">₱{{ number_format($inv->balanceDue(),2) }}</td>
      <td class="{{ $sCls }}">{{ ucfirst(str_replace('_',' ',$inv->status)) }}</td>
    </tr>
  @endforeach
</table>

<h2>Top Arrears</h2>
<table>
  <tr><th>Room</th><th>Lead Tenant</th><th class="right">Balance Due</th></tr>
  @foreach($topArrears as $a)
    <tr>
      <td>{{ $a['room'] }}</td>
      <td>{{ $a['name'] }}</td>
      <td class="right" style="color:#dc2626;font-weight:600">₱{{ number_format($a['balance'],2) }}</td>
    </tr>
  @endforeach
</table>

</body>
</html>
