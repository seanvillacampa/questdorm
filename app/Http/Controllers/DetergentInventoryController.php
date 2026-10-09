<?php

namespace App\Http\Controllers;

use App\Models\DetergentInventory;
use App\Models\DetergentLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DetergentInventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('can:viewAny,App\Models\DetergentInventory',  only: ['index']),
            new Middleware('can:create,App\Models\DetergentInventory',   only: ['store']),
            new Middleware('can:delete,App\Models\DetergentInventory',   only: ['destroy']),
        ];
    }

    /**
     * Show the inventory page with current stock and full log history.
     */
    public function index(Request $request)
    {
        $inventory = DetergentInventory::current();

        $logs = DetergentLog::with(['user', 'order'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when(
                $request->filled('from') && $request->filled('to'),
                fn ($q) => $q->between($request->from, $request->to)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('laundry.detergent.index', compact('inventory', 'logs'));
    }

    /**
     * Add stock (restock). Accessible by both owner and staff.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'amount_ml' => ['required', 'integer', 'min:1', 'max:500000'],
            'notes'     => ['nullable', 'string', 'max:255'],
        ]);

        $inventory = DetergentInventory::current();
        $inventory->addStock($data['amount_ml'], $request->user(), $data['notes'] ?? null);

        return redirect()->route('detergent.index')
            ->with('success', number_format($data['amount_ml']) . ' ml added to detergent stock.');
    }

    /**
     * Delete a restock log entry. Owner only.
     * Deleting a restock log reverses the stock addition.
     */
    public function destroy(Request $request, DetergentLog $log)
    {
        // Only restock entries can be manually deleted
        if ($log->isDeduction()) {
            return redirect()->route('detergent.index')
                ->with('error', 'Deduction entries cannot be deleted.');
        }

        $inventory = DetergentInventory::current();

        // Prevent going negative
        if ($inventory->stock_ml < $log->amount_ml) {
            return redirect()->route('detergent.index')
                ->with('error', 'Cannot delete: removing this entry would make the stock negative.');
        }

        // Reverse the stock
        $inventory->stock_ml -= $log->amount_ml;
        $inventory->save();

        $log->delete();

        return redirect()->route('detergent.index')
            ->with('success', 'Restock entry deleted and stock adjusted.');
    }
}
