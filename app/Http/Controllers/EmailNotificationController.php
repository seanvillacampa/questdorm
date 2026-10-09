<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InvoiceCreated;
use App\Notifications\PaymentReceived;
use App\Models\Invoice;

class EmailNotificationController extends Controller
{
    // Settings keys used
    private const KEYS = [
        'notify_owner_payment_received',
        'notify_owner_payment_missed',
        'notify_tenant_receipt',
        'notify_tenant_missed',
    ];

    public function index()
    {
        // Load toggle states from settings (default ON for all)
        $prefs = collect(self::KEYS)->mapWithKeys(fn ($k) =>
            [$k => (bool) Setting::where('key', $k)->value('value') ?? true]
        );

        // Email log: any AuditLog entries tagged as Email
        $emailLogs = AuditLog::where('record_type', 'Email')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('email-notifications.index', compact('prefs', 'emailLogs'));
    }

    public function updatePrefs(Request $request)
    {
        foreach (self::KEYS as $key) {
            // Checkbox sends '1' when checked, absent when unchecked
            $value = $request->boolean($key) ? 1 : 0;
            Setting::updateOrCreateSimple($key, $value);
        }

        AuditLog::record('Updated', 'Email', null, 'Email notification preferences updated by ' . Auth::user()->name);

        return back()->with('success', 'Email preferences saved.');
    }

    public function sendTest(Request $request)
    {
        $type  = $request->input('type');
        $email = Auth::user()->email;

        try {
            switch ($type) {
                case 'invoice_created':
                    $invoice = Invoice::with(['contract.room','tenantPayments'])->latest()->first();
                    if ($invoice && $invoice->tenantPayments->first()) {
                        Notification::route('mail', $email)
                            ->notify(new InvoiceCreated($invoice, $invoice->tenantPayments->first()));
                    }
                    break;

                case 'payment_received':
                    $invoice = Invoice::with(['contract.room','tenantPayments'])->latest()->first();
                    if ($invoice && $invoice->tenantPayments->first()) {
                        Notification::route('mail', $email)
                            ->notify(new \App\Notifications\PaymentReceived(
                                $invoice,
                                $invoice->tenantPayments->first(),
                                $invoice->tenantPayments->first()->share_amount,
                                'gcash'
                            ));
                    }
                    break;

                case 'payment_late':
                    $invoice = Invoice::with(['contract.room','tenantPayments'])->latest()->first();
                    if ($invoice && $invoice->tenantPayments->first()) {
                        Notification::route('mail', $email)
                            ->notify(new \App\Notifications\PaymentMissed(
                                $invoice,
                                $invoice->tenantPayments->first()
                            ));
                    }
                    break;

                case 'payment_overdue':
                    $invoice = Invoice::with(['contract.room','tenantPayments'])->latest()->first();
                    if ($invoice && $invoice->tenantPayments->first()) {
                        Notification::route('mail', $email)
                            ->notify(new \App\Notifications\PaymentOverdue(
                                $invoice,
                                $invoice->tenantPayments->first()
                            ));
                    }
                    break;

                case 'owner_alert':
                    $invoice = Invoice::with(['contract.room','contract.tenants.user','tenantPayments'])->latest()->first();
                    if ($invoice) {
                        Notification::route('mail', $email)
                            ->notify(new \App\Notifications\OwnerPaymentAlert($invoice));
                    }
                    break;
            }

            AuditLog::record('Email', 'Email', null, "Test email sent: {$type} to {$email}");
            return back()->with('success', "Test email ({$type}) sent to {$email}.");
        } catch (\Exception $e) {
            Log::error("Test email failed: {$type}", ['error' => $e->getMessage()]);
            return back()->withErrors(['test' => 'Failed: ' . $e->getMessage()]);
        }
    }
}
