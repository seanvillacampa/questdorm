<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    private function getData(string $month): array
    {
        $monthInvoices = Invoice::where('billing_month', $month)
            ->where('status', '!=', 'void')->get();

        $totalBilled    = $monthInvoices->sum('total_amount');
        $totalCollected = $monthInvoices->sum('amount_paid');

        // Status counts — include pending, partial as their own group
        $rawGroups = $monthInvoices->groupBy(function ($inv) {
            return match(true) {
                in_array($inv->status, ['partial']) => 'partial',
                default => $inv->status,
            };
        })->map->count();

        $statusCounts = collect([
            'paid'    => $rawGroups['paid']    ?? 0,
            'partial' => $rawGroups['partial'] ?? 0,
            'pending' => $rawGroups['pending'] ?? 0,
            'overdue' => $rawGroups['overdue'] ?? 0,
        ]);

        // Monthly income trend (last 9 months)
        $trend = Invoice::where('status', '!=', 'void')
            ->where('billing_month', '>=', now()->subMonths(8)->format('Y-m'))
            ->selectRaw("billing_month, SUM(amount_paid) as collected")
            ->groupBy('billing_month')
            ->orderBy('billing_month')
            ->get()->keyBy('billing_month');

        $trendLabels = [];
        $trendValues = [];
        for ($i = 8; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $trendLabels[] = now()->subMonths($i)->format('M');
            $trendValues[] = (float) ($trend->get($m)?->collected ?? 0);
        }

        // Top arrears
        $topArrears = Invoice::where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->whereNotIn('status', ['paid'])
            ->with(['contract.room', 'contract.tenants.user'])
            ->get()
            ->map(fn ($inv) => [
                'name'    => $inv->contract->tenants->first()?->user->name ?? '—',
                'room'    => $inv->contract->room->room_number ?? '—',
                'balance' => $inv->balanceDue(),
            ])
            ->sortByDesc('balance')
            ->take(5)
            ->values();

        return compact(
            'totalBilled', 'totalCollected', 'statusCounts',
            'trendLabels', 'trendValues', 'topArrears'
        );
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'dorm'); // 'dorm' or 'laundry'
        
        if ($type === 'laundry') {
            return $this->laundryReports($request);
        }
        
        $month = $request->get('month', now()->format('Y-m'));
        $data  = $this->getData($month);
        return view('reports.index', array_merge(['month' => $month, 'type' => $type], $data));
    }
    
    private function laundryReports(Request $request)
    {
        // Accept either a month picker (YYYY-MM) or explicit from/to
        if ($request->filled('month')) {
            $month = $request->input('month');
            $from  = \Illuminate\Support\Carbon::parse($month . '-01')->startOfMonth()->toDateString();
            $to    = \Illuminate\Support\Carbon::parse($month . '-01')->endOfMonth()->toDateString();
        } else {
            $from  = $request->input('from', \Illuminate\Support\Carbon::now()->startOfMonth()->toDateString());
            $to    = $request->input('to',   \Illuminate\Support\Carbon::now()->endOfMonth()->toDateString());
            $month = \Illuminate\Support\Carbon::parse($from)->format('Y-m');
        }

        $orders = \App\Models\LaundryOrder::between($from, $to);

        $summary = [
            'transactions' => (clone $orders)->count(),
            'total_income' => (clone $orders)->paid()->sum('total_amount'),
            'receivables'  => (clone $orders)->unpaid()->sum('total_amount'),
            'cash'         => (clone $orders)->paid()->where('payment_method', \App\Models\LaundryOrder::METHOD_CASH)->sum('total_amount'),
            'online'       => (clone $orders)->paid()->where('payment_method', \App\Models\LaundryOrder::METHOD_ONLINE)->sum('total_amount'),
            'loads'        => (clone $orders)->sum('total_loads'),
            'liquid_ml'    => (clone $orders)->sum('total_liquid_ml'),
        ];

        $byCustomerType = (clone $orders)
            ->select('customer_type', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(total_amount) as amount'))
            ->groupBy('customer_type')
            ->get();

        $daily = (clone $orders)
            ->select('date_received', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('date_received')
            ->orderBy('date_received')
            ->get();

        $unpaid = (clone $orders)->unpaid()->with('customer')->get();

        return view('reports.index', compact('from', 'to', 'month', 'summary', 'byCustomerType', 'daily', 'unpaid'))
            ->with('type', 'laundry');
    }

    public function exportLaundryCsv(Request $request)
    {
        if ($request->filled('month')) {
            $from = \Illuminate\Support\Carbon::parse($request->input('month') . '-01')->startOfMonth()->toDateString();
            $to   = \Illuminate\Support\Carbon::parse($request->input('month') . '-01')->endOfMonth()->toDateString();
        } else {
            $from = $request->input('from', \Illuminate\Support\Carbon::now()->startOfMonth()->toDateString());
            $to   = $request->input('to',   \Illuminate\Support\Carbon::now()->endOfMonth()->toDateString());
        }
        $label = str_replace('-', '', $from) . '_' . str_replace('-', '', $to);

        $orders = \App\Models\LaundryOrder::with(['customer', 'items.service'])
            ->between($from, $to)
            ->latest('date_received')
            ->latest('id')
            ->get();

        $rows   = [];
        $rows[] = ['Order No.', 'Date', 'Customer', 'Type', 'Room', 'Services', 'Loads', 'Liquid (ml)', 'Total', 'Payment Method', 'Status'];

        foreach ($orders as $order) {
            $services = $order->items->map(fn ($i) => $i->service->name . ' (' . $i->weight_kg . 'kg)')->implode('; ');
            $rows[] = [
                $order->order_no,
                $order->date_received->format('Y-m-d'),
                $order->customer->name,
                $order->customer_type,
                $order->room_no ?? '',
                $services,
                $order->total_loads,
                $order->total_liquid_ml,
                $order->total_amount,
                $order->payment_method,
                $order->payment_status,
            ];
        }

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"laundry_{$label}.csv\"",
        ]);
    }

    public function exportLaundryPdf(Request $request)
    {
        if ($request->filled('month')) {
            $from = \Illuminate\Support\Carbon::parse($request->input('month') . '-01')->startOfMonth()->toDateString();
            $to   = \Illuminate\Support\Carbon::parse($request->input('month') . '-01')->endOfMonth()->toDateString();
        } else {
            $from = $request->input('from', \Illuminate\Support\Carbon::now()->startOfMonth()->toDateString());
            $to   = $request->input('to',   \Illuminate\Support\Carbon::now()->endOfMonth()->toDateString());
        }
        $label  = str_replace('-', '', $from) . '_' . str_replace('-', '', $to);

        $orders = \App\Models\LaundryOrder::with(['customer', 'items.service'])
            ->between($from, $to)
            ->latest('date_received')
            ->latest('id')
            ->get();

        $summary = [
            'transactions' => $orders->count(),
            'total_income' => $orders->where('payment_status', 'paid')->sum('total_amount'),
            'receivables'  => $orders->where('payment_status', 'unpaid')->sum('total_amount'),
            'loads'        => $orders->sum('total_loads'),
            'liquid_ml'    => $orders->sum('total_liquid_ml'),
        ];

        $html = view('reports.laundry-pdf', compact('from', 'to', 'orders', 'summary'))->render();

        return response($html, 200, [
            'Content-Type'        => 'text/html',
            'Content-Disposition' => "attachment; filename=\"laundry_report_{$label}.html\"",
        ]);
    }

    public function exportCsv(Request $request)
    {
        $month  = $request->get('month', now()->format('Y-m'));
        $label  = now()->parse($month)->format('F_Y');

        $invoices = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->orderBy('invoice_number')
            ->get();

        $rows   = [];
        // Updated header to include per-tenant breakdown
        $rows[] = ['Invoice #','Room','Tenant','Rent Share','Electricity Share','Carry-Over','Total Owed','Amount Paid','Balance','Status'];

        foreach ($invoices as $inv) {
            // Export one row per tenant
            foreach ($inv->tenantPayments as $tp) {
                $tenantName = $tp->tenant?->user?->name ?? 'Unknown';
                $rentShare = round($inv->rent_amount / max(1, $inv->tenant_count), 2);
                $elecShare = round($inv->electricity_amount / max(1, $inv->tenant_count), 2);
                $carryOver = $tp->carry_over_balance ?? 0;
                $totalOwed = $tp->share_amount + $carryOver;
                $balance = max(0, $totalOwed - $tp->amount_paid);
                
                $rows[] = [
                    $inv->invoice_number,
                    $inv->contract->room->room_number,
                    $tenantName,
                    $rentShare,
                    $elecShare,
                    $carryOver,
                    $totalOwed,
                    $tp->amount_paid,
                    $balance,
                    $tp->status,
                ];
            }
        }

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"invoices_{$label}.csv\"",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $month  = $request->get('month', now()->format('Y-m'));
        $data   = $this->getData($month);

        $invoices = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->orderBy('invoice_number')
            ->get();

        $html = view('reports.pdf', array_merge(
            ['month' => $month, 'invoices' => $invoices],
            $data
        ))->render();

        // Return as HTML download — works without any PDF library
        $label = now()->parse($month)->format('F_Y');
        return response($html, 200, [
            'Content-Type'        => 'text/html',
            'Content-Disposition' => "attachment; filename=\"report_{$label}.html\"",
        ]);
    }
}
