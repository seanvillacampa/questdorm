<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point after login (/dashboard).
 * Redirects by role, then serves the owner/staff dashboard view
 * with all data the room grid and stat cards need.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return match (Auth::user()->primaryRole()) {
            'owner'    => redirect()->route('owner.dashboard'),
            'employee' => redirect()->route('staff.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403, 'This account has no role assigned. Contact the owner.'),
        };
    }

    /** Renders the actual dashboard view with all data (shared by owner + employee). */
    public function show()
    {
        return $this->staffDashboard();
    }

    // ── Owner / Employee dashboard ───────────────────────────────────────

    private function staffDashboard()
    {
        $billingMonth = now()->format('Y-m'); // "2026-09"

        // ── Rooms: eager-load active contract ─────────────────────────
        $rooms = Room::with('activeContract')
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        // Attach currentInvoice + currentTenantName to each room (bulk, no N+1)
        Room::withDashboardData($billingMonth, $rooms);

        // Attach unresolved message count to each room
        $unresolvedMessageCounts = \App\Models\TenantMessage::whereNull('resolved_at')
            ->select('room_id', DB::raw('COUNT(*) as count'))
            ->groupBy('room_id')
            ->pluck('count', 'room_id');

        foreach ($rooms as $room) {
            $room->unresolvedMessageCount = $unresolvedMessageCounts->get($room->id, 0);
        }

        // ── Stat cards ────────────────────────────────────────────────
        $totalRooms       = $rooms->count();
        $occupiedCount    = $rooms->where('status', 'occupied')->count();
        $vacantCount      = $rooms->where('status', 'vacant')->count();
        $maintenanceCount = $rooms->where('status', 'maintenance')->count();

        // Current-month invoices (all)
        $monthInvoices = Invoice::whereHas('contract', fn ($q) =>
                $q->whereHas('room') // only real contracts
            )
            ->where('billing_month', $billingMonth)
            ->get();

        $billedThisMonth    = $monthInvoices->sum('total_amount');
        $collectedThisMonth = $monthInvoices->sum('amount_paid');
        $overdueCount       = $monthInvoices->where('status', 'overdue')->count();
        
        // Active tenants (those with active contracts)
        $activeTenantsCount = Contract::where('status', 'active')
            ->distinct('id')
            ->count();

        // ── Recent tenant messages (unresolved) ────────────────────────────
        $recentMessages = \App\Models\TenantMessage::with(['tenant.user', 'room'])
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── Live activity feed (last 10 meaningful events) ────────────
        $recentActivity = $this->buildActivityFeed();

        // ── Month coverage for generate-invoices dropdown ─────────────
        
        // Define room count milestones (when new rooms were added)
        $roomMilestones = [
            '2026-10' => 29, // October 2026: 29 rooms
            '2026-09' => 28, // Before October: 28 rooms
        ];
        
        $invCounts = Invoice::select('billing_month', DB::raw('COUNT(*) as cnt'))
            ->where('status', '!=', 'void')
            ->groupBy('billing_month')
            ->pluck('cnt', 'billing_month');

        $monthCoverage = collect();
        for ($i = 0; $i <= 12; $i++) {
            $m = now()->subMonths($i)->format('Y-m');
            $cnt = (int) ($invCounts[$m] ?? 0);
            
            // Determine the expected room count for this month
            $expectedRooms = 28; // Default for oldest months
            foreach ($roomMilestones as $milestoneMonth => $roomCount) {
                if ($m >= $milestoneMonth) {
                    $expectedRooms = $roomCount;
                    break;
                }
            }
            
            $status = match(true) {
                $expectedRooms > 0 && $cnt >= $expectedRooms => 'all',
                $cnt > 0                                     => 'partial',
                default                                      => 'none',
            };
            $monthCoverage->put($m, [
                'label'  => now()->subMonths($i)->format('F Y'),
                'status' => $status,
                'count'  => $cnt,
                'total'  => $expectedRooms,
            ]);
        }

        return view('staff.dashboard', compact(
            'rooms',
            'billingMonth',
            'totalRooms',
            'occupiedCount',
            'vacantCount',
            'maintenanceCount',
            'billedThisMonth',
            'collectedThisMonth',
            'overdueCount',
            'activeTenantsCount',
            'recentActivity',
            'recentMessages',
            'monthCoverage',
        ));
    }

    /**
     * Build a unified activity feed from:
     *   - Recent payments (green dot)
     *   - Invoices that just turned late (yellow dot)
     *   - Invoices that just turned overdue (red dot)
     *
     * Returns a plain array of up to 10 items, newest first.
     */
    private function buildActivityFeed(): \Illuminate\Support\Collection
    {
        $items = collect();

        // Recent payments (last 7 days)
        $payments = Payment::with(['invoice.contract.room', 'recordedByUser'])
            ->where('received_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($payments as $payment) {
            $invoice  = $payment->invoice;
            $contract = $invoice?->contract;
            $room     = $contract?->room;
            $method   = ucfirst(str_replace('_', ' ', $payment->method));
            $by       = $payment->recorded_by_type === 'paymongo'
                ? 'via ' . $method
                : 'cash, recorded by staff';

            $items->push([
                'type'   => 'payment',
                'title'  => ($invoice?->status === 'paid' ? '' : 'Partial payment — ') .
                            'Room ' . ($room?->room_number ?? '?') . ' paid',
                'detail' => '₱' . number_format($payment->amount, 2) . ' ' . $by,
                'time'   => $payment->created_at->diffForHumans(),
                'sort'   => $payment->created_at,
            ]);
        }

        // Invoices that turned late (updated_at within last 48 h, status = late)
        $lateInvoices = Invoice::with(['contract.room'])
            ->where('status', 'late')
            ->where('updated_at', '>=', now()->subHours(48))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        foreach ($lateInvoices as $inv) {
            $room = $inv->contract?->room;
            $items->push([
                'type'   => 'late',
                'title'  => 'Room ' . ($room?->room_number ?? '?') . ' is now late',
                'detail' => 'Due ' . $inv->due_date->format('M j') . ' · reminder emailed',
                'time'   => $inv->updated_at->diffForHumans(),
                'sort'   => $inv->updated_at,
            ]);
        }

        // Invoices that turned overdue (updated_at within last 48 h, status = overdue)
        $overdueInvoices = Invoice::with(['contract.room'])
            ->where('status', 'overdue')
            ->where('updated_at', '>=', now()->subHours(48))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        foreach ($overdueInvoices as $inv) {
            $room = $inv->contract?->room;
            $items->push([
                'type'   => 'overdue',
                'title'  => 'Room ' . ($room?->room_number ?? '?') . ' is overdue',
                'detail' => 'Due ' . $inv->due_date->format('M j') . ' · owner emailed',
                'time'   => $inv->updated_at->diffForHumans(),
                'sort'   => $inv->updated_at,
            ]);
        }

        return $items->sortByDesc('sort')->take(10)->values();
    }
}
