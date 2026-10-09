<?php

namespace App\Http\Controllers;

use App\Models\MeterReading;
use App\Models\Room;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeterReadingController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $rooms = Room::where('status', 'occupied')
            ->with(['activeContract'])
            ->orderByRaw('CAST(room_number AS UNSIGNED)')
            ->orderBy('room_number')
            ->get();

        // Load this month's readings keyed by room_id
        $readings = MeterReading::where('reading_month', $month)
            ->get()->keyBy('room_id');

        // Load previous month readings for "previous kWh" column
        $prevMonth    = \Carbon\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
        $prevReadings = MeterReading::where('reading_month', $prevMonth)
            ->get()->keyBy('room_id');

        // Count only rooms in our list that have a reading for this month
        // Use a direct DB query to avoid keyBy type-mismatch issues
        $activeRoomIds = $rooms->pluck('id');
        $recorded      = MeterReading::where('reading_month', $month)
            ->whereIn('room_id', $activeRoomIds)
            ->count();
        $total         = $rooms->count();

        return view('meter-readings.index', compact(
            'rooms', 'readings', 'prevReadings', 'month', 'recorded', 'total'
        ));
    }

    /**
     * Save a single room's reading via AJAX (called by the per-row Save button).
     */
    public function storeSingle(Request $request)
    {
        $request->validate([
            'month'       => 'required|date_format:Y-m',
            'room_id'     => 'required|exists:rooms,id',
            'current_kwh' => 'required|numeric|min:0',
        ]);

        $month   = $request->month;
        $roomId  = $request->room_id;
        $current = (float) $request->current_kwh;

        // Validate: current must not be less than previous
        $prev = MeterReading::where('room_id', $roomId)
            ->where('reading_month', '<', $month)
            ->orderByDesc('reading_month')
            ->value('current_kwh') ?? 0;

        if ($current < $prev) {
            return response()->json([
                'message' => "Current kWh ({$current}) cannot be less than previous reading ({$prev}).",
            ], 422);
        }

        $existedBefore = MeterReading::where('room_id', $roomId)
            ->where('reading_month', $month)
            ->exists();

        $reading = MeterReading::updateOrCreate(
            ['room_id' => $roomId, 'reading_month' => $month],
            [
                'previous_kwh' => $prev,
                'current_kwh'  => $current,
                'kwh_used'     => max(0, $current - $prev),
                'recorded_by'  => Auth::id(),
            ]
        );

        AuditLog::record('Reading', 'MeterReading', $roomId,
            "reading: {$current} kWh for {$month}");

        return response()->json([
            'kwh_used'       => $reading->kwh_used,
            'newly_recorded' => !$existedBefore,
        ]);
    }

    /**
     * Bulk save — kept for backwards compatibility.
     */
    public function store(Request $request)
    {
        $request->validate([
            'month'                       => 'required|date_format:Y-m',
            'readings'                    => 'required|array',
            'readings.*.room_id'          => 'required|exists:rooms,id',
            'readings.*.current_kwh'      => 'nullable|numeric|min:0',
        ]);

        $month  = $request->month;
        $saved  = 0;
        $errors = [];

        foreach ($request->readings as $row) {
            if (empty($row['current_kwh'])) continue;

            $prev = MeterReading::where('room_id', $row['room_id'])
                ->where('reading_month', '<', $month)
                ->orderByDesc('reading_month')
                ->value('current_kwh') ?? 0;

            if ((float) $row['current_kwh'] < $prev) {
                $errors[] = "Room #{$row['room_id']}: current kWh ({$row['current_kwh']}) is less than previous ({$prev}).";
                continue;
            }

            MeterReading::updateOrCreate(
                ['room_id' => $row['room_id'], 'reading_month' => $month],
                [
                    'previous_kwh' => $prev,
                    'current_kwh'  => $row['current_kwh'],
                    'kwh_used'     => max(0, $row['current_kwh'] - $prev),
                    'recorded_by'  => Auth::id(),
                ]
            );

            AuditLog::record('Reading', 'MeterReading', $row['room_id'],
                "reading: {$row['current_kwh']} kWh for {$month}");
            $saved++;
        }

        if (!empty($errors)) {
            return redirect()->route('meter-readings.index', ['month' => $month])
                ->withErrors($errors)
                ->with('success', $saved > 0 ? "{$saved} reading(s) saved." : null);
        }

        return redirect()->route('meter-readings.index', ['month' => $month])
            ->with('success', "{$saved} reading(s) saved.");
    }
}
