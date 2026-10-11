<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $rateHistory  = Setting::where('key', 'like', 'electricity_rate%')
            ->orderByDesc('effective_from')
            ->get();
        $currentRate  = Setting::currentElectricityRate();
        $billingRules = Setting::billingRules();

        return view('settings.index', compact('rateHistory', 'currentRate', 'billingRules'));
    }

    public function storeRate(Request $request)
    {
        $data = $request->validate([
            'rate'           => 'required|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        $old = Setting::currentElectricityRate();

        Setting::create([
            'key'            => 'electricity_rate',
            'value'          => $data['rate'],
            'effective_from' => $data['effective_from'],
            'set_by'         => Auth::id(),
        ]);

        AuditLog::record('Rate', 'Setting', null,
            "Electricity rate: ₱{$old} → ₱{$data['rate']} effective {$data['effective_from']}");

        return back()->with('success', 'Electricity rate updated.');
    }

    public function storeBilling(Request $request)
    {
        $data = $request->validate([
            'invoice_advance_days' => 'required|integer|min:1|max:14',
            'overdue_grace_days'   => 'required|integer|min:1',
            'deposit_months'       => 'required|integer|min:1|max:6',
            'advance_months'       => 'required|integer|min:0|max:3',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreateSimple($key, $value);
        }

        AuditLog::record('Updated', 'Setting', null, 'Billing rules updated: ' . $data['advance_months'] . ' month(s) advance, ' . $data['deposit_months'] . ' month(s) deposit');

        return back()->with('success', 'Billing rules saved.');
    }

    public function resyncStatuses()
    {
        $invoices = \App\Models\Invoice::with('tenantPayments')
            ->whereNotIn('status', ['void'])
            ->get();

        $count = $invoices->count();

        foreach ($invoices as $invoice) {
            $invoice->recomputeStatus();
        }

        AuditLog::record('Resynced', 'Invoice', null, "Manually resynced statuses for {$count} billing statement(s).");

        return back()->with('success', "Resynced statuses for {$count} billing statement(s).");
    }
}
