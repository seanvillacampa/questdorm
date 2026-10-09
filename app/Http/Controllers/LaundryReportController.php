<?php

namespace App\Http\Controllers;

use App\Models\DetergentInventory;
use App\Models\DetergentLog;
use App\Models\LaundryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaundryReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   Carbon::now()->endOfMonth()->toDateString());

        $orders = LaundryOrder::between($from, $to);

        $summary = [
            'transactions' => (clone $orders)->count(),
            'total_income' => (clone $orders)->paid()->sum('total_amount'),
            'receivables'  => (clone $orders)->unpaid()->sum('total_amount'),
            'cash'         => (clone $orders)->paid()->where('payment_method', LaundryOrder::METHOD_CASH)->sum('total_amount'),
            'online'       => (clone $orders)->paid()->where('payment_method', LaundryOrder::METHOD_ONLINE)->sum('total_amount'),
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

        // ── Detergent data ────────────────────────────────────────────────
        $inventory = DetergentInventory::current();

        // Restock entries within the selected period
        $detergentRestocks = DetergentLog::with('user')
            ->restocks()
            ->between($from, $to)
            ->latest()
            ->get();

        $detergentSummary = [
            'current_stock_ml'   => $inventory->stock_ml,
            'restocked_ml'       => $detergentRestocks->sum('amount_ml'),
            'consumed_ml'        => (clone $orders)->sum('total_liquid_ml'),
            'restock_count'      => $detergentRestocks->count(),
        ];

        return view('laundry.reports', compact(
            'from', 'to',
            'summary', 'byCustomerType', 'daily', 'unpaid',
            'inventory', 'detergentRestocks', 'detergentSummary'
        ));
    }
}
