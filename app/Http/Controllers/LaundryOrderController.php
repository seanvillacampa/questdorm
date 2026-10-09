<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaundryOrderRequest;
use App\Http\Requests\UpdatePaymentStatusRequest;
use App\Models\Customer;
use App\Models\DetergentInventory;
use App\Models\LaundryOrder;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class LaundryOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('can:viewAny,' . LaundryOrder::class, only: ['index']),
            new Middleware('can:create,' . LaundryOrder::class, only: ['create', 'store']),
            new Middleware('can:view,order',   only: ['show']),
            new Middleware('can:update,order', only: ['edit', 'update']),
            new Middleware('can:delete,order', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $orders = LaundryOrder::with(['customer', 'items.service', 'recorder'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->method))
            ->when(
                $request->filled('from') && $request->filled('to'),
                fn ($q) => $q->between($request->from, $request->to)
            )
            ->latest('date_received')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('laundry.index', [
            'orders'        => $orders,
            'customerTypes' => Customer::TYPES,
            'inventory'     => DetergentInventory::current(),
        ]);
    }

    public function create()
    {
        $services  = Service::active()->with('prices')->get();
        $inventory = DetergentInventory::current();

        // Pre-format for JS — avoids Blade compiler choking on fn() inside @json
        $servicesJs = $services->map(function ($s) {
            return [
                'id'     => $s->id,
                'name'   => $s->name,
                'limit'  => (float) $s->weight_limit_kg,
                'liquid' => $s->liquidMlFor((float) $s->weight_limit_kg), // ml per load
                'prices' => $s->prices->pluck('price', 'customer_type'),
            ];
        })->values();

        return view('laundry.create', [
            'services'      => $services,
            'servicesJs'    => $servicesJs,
            'customerTypes' => Customer::TYPES,
            'inventory'     => $inventory,
        ]);
    }

    public function store(StoreLaundryOrderRequest $request)
    {
        $data = $request->validated();

        // ── Pre-flight: check detergent stock ────────────────────────────
        $inventory   = DetergentInventory::current();
        $services    = Service::with('prices')
            ->whereIn('id', array_column($data['items'], 'service_id'))
            ->get()
            ->keyBy('id');

        $totalRequired = 0;
        foreach ($data['items'] as $item) {
            $svc = $services[$item['service_id']] ?? null;
            if ($svc) {
                $totalRequired += $svc->liquidMlFor((float) $item['weight_kg']);
            }
        }

        if (! $inventory->hasEnough($totalRequired)) {
            return back()
                ->withInput()
                ->withErrors([
                    'detergent' => "Not enough liquid detergent. Available: {$inventory->stock_ml} ml, required: {$totalRequired} ml. Please restock before recording this order.",
                ]);
        }

        // ── Record order and deduct stock (single transaction) ───────────
        $order = DB::transaction(function () use ($data, $request, $inventory, $services, $totalRequired) {
            $customer = $this->resolveCustomer($data);

            $order = LaundryOrder::create([
                'order_no'       => LaundryOrder::generateOrderNo($data['date_received']),
                'customer_id'    => $customer->id,
                'customer_type'  => $customer->type,
                'room_no'        => $customer->isTenant() ? $customer->room_no : null,
                'date_received'  => $data['date_received'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
                'recorded_by'    => $request->user()->id,
            ]);

            $this->syncItems($order, $data['items'], $customer->type, $services);
            $order->recalculateTotals();

            // Deduct the exact ml consumed by this order (re-use the calculated total)
            $inventory->deductStock($order->total_liquid_ml, $order, $request->user());

            return $order;
        });

        return redirect()->route('laundry.show', $order)
            ->with('success', "Order {$order->order_no} recorded.");
    }

    public function show(LaundryOrder $order)
    {
        $order->load(['customer', 'items.service', 'recorder']);

        return view('laundry.show', compact('order'));
    }

    public function edit(LaundryOrder $order)
    {
        $order->load(['customer', 'items.service']);

        return view('laundry.edit', compact('order'));
    }

    public function update(UpdatePaymentStatusRequest $request, LaundryOrder $order)
    {
        $order->update($request->validated());

        return redirect()->route('laundry.show', $order)
            ->with('success', 'Payment updated.');
    }

    public function destroy(LaundryOrder $order)
    {
        $order->delete();

        return redirect()->route('laundry.index')
            ->with('success', "Order {$order->order_no} deleted.");
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::findOrFail($data['customer_id']);
            $customer->fill([
                'type'    => $data['customer_type'],
                'room_no' => $data['room_no'] ?? null,
            ])->save();

            return $customer;
        }

        return Customer::create([
            'name'       => $data['customer_name'],
            'type'       => $data['customer_type'],
            'room_no'    => $data['customer_type'] === Customer::TYPE_TENANT ? ($data['room_no'] ?? null) : null,
            'contact_no' => $data['contact_no'] ?? null,
        ]);
    }

    private function syncItems(LaundryOrder $order, array $items, string $customerType, ?\Illuminate\Support\Collection $services = null): void
    {
        if (is_null($services)) {
            $services = Service::with('prices')
                ->whereIn('id', array_column($items, 'service_id'))
                ->get()
                ->keyBy('id');
        }

        foreach ($items as $item) {
            $service   = $services[$item['service_id']];
            $weight    = (float) $item['weight_kg'];
            $unitPrice = $service->priceFor($customerType);
            $loads     = $service->loadsFor($weight);

            $order->items()->create([
                'service_id'      => $service->id,
                'weight_kg'       => $weight,
                'weight_limit_kg' => $service->weight_limit_kg,
                'unit_price'      => $unitPrice,
                'loads'           => $loads,
                'liquid_ml'       => $service->liquidMlFor($weight),
                'subtotal'        => $unitPrice * $loads,
            ]);
        }
    }
}
