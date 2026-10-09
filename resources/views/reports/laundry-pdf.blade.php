<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Quest Building — Laundry Report {{ $from }} to {{ $to }}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 32px; }
  h1   { font-size: 20px; margin-bottom: 4px; }
  h2   { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
  .meta { color: #666; font-size: 11px; margin-bottom: 24px; }
  .cards { display: flex; gap: 16px; margin-bottom: 24px; }
  .card  { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; flex: 1; }
  .card p.label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 4px; }
  .card p.value { font-size: 20px; font-weight: bold; margin: 0; }
  .card p.sub   { font-size: 10px; color: #9ca3af; margin: 3px 0 0; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th    { text-align: left; font-size: 10px; text-transform: uppercase; color: #9ca3af; letter-spacing: .05em; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
  td    { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
  tr:last-child td { border-bottom: none; }
  .right { text-align: right; }
  .paid   { color: #16a34a; font-weight: 600; }
  .unpaid { color: #d97706; font-weight: 600; }
  @media print { body { margin: 16px; } }
</style>
</head>
<body>

<h1>Quest Building — Laundry Report</h1>
<p class="meta">Period: {{ \Carbon\Carbon::parse($from)->format('F d, Y') }} – {{ \Carbon\Carbon::parse($to)->format('F d, Y') }} &nbsp;·&nbsp; Generated: {{ now()->format('F d, Y h:i A') }}</p>

<div class="cards">
    <div class="card">
        <p class="label">Transactions</p>
        <p class="value">{{ $summary['transactions'] }}</p>
    </div>
    <div class="card">
        <p class="label">Income Collected</p>
        <p class="value">₱{{ number_format($summary['total_income'], 2) }}</p>
    </div>
    <div class="card">
        <p class="label">Receivables (NP)</p>
        <p class="value">₱{{ number_format($summary['receivables'], 2) }}</p>
    </div>
    <div class="card">
        <p class="label">Total Loads</p>
        <p class="value">{{ $summary['loads'] }}</p>
        <p class="sub">{{ number_format($summary['liquid_ml']) }} ml used</p>
    </div>
</div>

<h2>Order Details</h2>
<table>
    <thead>
        <tr>
            <th>Order No.</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Room</th>
            <th>Services</th>
            <th class="right">Total</th>
            <th>Method</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td style="font-family:monospace;font-size:10px">{{ $order->order_no }}</td>
                <td>{{ $order->date_received->format('M d, Y') }}</td>
                <td>{{ $order->customer->name }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->customer_type)) }}</td>
                <td>{{ $order->room_no ?? '—' }}</td>
                <td>
                    @foreach ($order->items as $item)
                        {{ $item->service->name }} ({{ $item->weight_kg }}kg)@if(!$loop->last), @endif
                    @endforeach
                </td>
                <td class="right">₱{{ number_format($order->total_amount, 2) }}</td>
                <td>{{ ucfirst($order->payment_method) }}</td>
                <td class="{{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
                    {{ $order->payment_status === 'paid' ? 'Paid' : 'NP' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:20px">No orders in this period.</td></tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
