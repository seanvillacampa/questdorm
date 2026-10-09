<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\TenantPayment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('activeContract.tenants.user')
            ->orderByRaw('CAST(room_number AS UNSIGNED)')
            ->orderBy('room_number');

        if ($request->filled('search')) {
            $query->where('room_number', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('floor') && $request->floor !== 'all') {
            $query->where('floor', $request->floor);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $rooms  = $query->paginate(10)->withQueryString();
        $floors = Room::distinct()->orderBy('floor')->pluck('floor');

        return view('rooms.index', compact('rooms', 'floors'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        // Normalise meter_number
        if ($request->filled('meter_number')) {
            $raw = preg_replace('/^mtr-?/i', '', trim($request->meter_number));
            $raw = preg_replace('/\D/', '', $raw); // digits only
            $request->merge(['meter_number' => 'MTR-' . $raw]);
        }

        $data = $request->validate([
            'room_number'       => 'required|string|max:10|unique:rooms,room_number',
            'floor'             => 'required|integer|min:1|max:20',
            'capacity'          => 'required|integer|min:1|max:20',
            'monthly_rate'      => 'required|numeric|min:0',
            'deposit_required'  => 'required|numeric|min:0',
            'meter_number'      => [
                'nullable', 'string', 'max:30',
                \Illuminate\Validation\Rule::unique('rooms', 'meter_number'),
            ],
            'is_airconditioned' => 'nullable|boolean',
        ]);

        // New rooms are always vacant
        $data['status']            = 'vacant';
        $data['is_airconditioned'] = $request->boolean('is_airconditioned');

        $room = Room::create($data);

        AuditLog::record('Created', 'Room', $room->id, "Room {$room->room_number} added (vacant)");

        return redirect()->route('rooms.index')
            ->with('success', "Room {$room->room_number} created.");
    }

    public function edit(Room $room)
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        // Normalise meter_number
        if ($request->filled('meter_number')) {
            $raw = preg_replace('/^mtr-?/i', '', trim($request->meter_number));
            $raw = preg_replace('/\D/', '', $raw); // digits only
            $request->merge(['meter_number' => 'MTR-' . $raw]);
        }

        $data = $request->validate([
            'floor'             => 'required|integer|min:1|max:20',
            'capacity'          => 'required|integer|min:1|max:20',
            'monthly_rate'      => 'required|numeric|min:0',
            'deposit_required'  => 'required|numeric|min:0',
            'meter_number'      => [
                'nullable', 'string', 'max:30',
                \Illuminate\Validation\Rule::unique('rooms', 'meter_number')->ignore($room->id),
            ],
            'status'            => 'required|in:vacant,occupied,maintenance',
            'is_airconditioned' => 'nullable|boolean',
        ]);

        $data['is_airconditioned'] = $request->boolean('is_airconditioned');
        $oldStatus = $room->status;

        DB::transaction(function () use ($room, $data, $oldStatus) {
            $old = $room->only(array_keys($data));
            $room->update($data);

            // When setting to vacant, deactivate all active contracts for this room
            if ($data['status'] === 'vacant' && $oldStatus !== 'vacant') {
                $activeContracts = Contract::where('room_id', $room->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($activeContracts as $contract) {
                    // Void all unpaid invoices
                    $unpaidIds = Invoice::where('contract_id', $contract->id)
                        ->whereNotIn('status', ['paid', 'void'])
                        ->pluck('id');

                    if ($unpaidIds->isNotEmpty()) {
                        Invoice::whereIn('id', $unpaidIds)->update(['status' => 'void']);
                        TenantPayment::whereIn('invoice_id', $unpaidIds)
                            ->where('status', '!=', 'paid')
                            ->update(['status' => 'void']);
                    }

                    $contract->update([
                        'is_active' => false,
                        'status'    => 'ended',
                    ]);

                    AuditLog::record('Deactivated', 'Contract', $contract->id,
                        "Auto-deactivated: Room {$room->room_number} set to vacant. {$unpaidIds->count()} invoice(s) voided.");
                }
            }

            AuditLog::record('Updated', 'Room', $room->id,
                "Room {$room->room_number}: " . AuditLog::diff($old, $data));
        });

        return redirect()->route('rooms.index')
            ->with('success', "Room {$room->room_number} updated.");
    }
}
