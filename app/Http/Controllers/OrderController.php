<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['requestedBy', 'approvedBy', 'supplier'])
            ->orderBy($request->get('sort', 'created_at'), $request->get('direction', 'desc'));

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('CRM/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'type', 'priority', 'sort', 'direction']),
            'statusOptions' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'ordered' => 'Ordered',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
            'typeOptions' => [
                'locally' => 'Locally',
                'externally' => 'Externally',
            ],
            'priorityOptions' => [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
            ],
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        $suppliers = Supplier::active()
            ->select('uuid', 'name', 'code')
            ->orderBy('name')
            ->get();

        $devices = Device::select('uuid', 'brand', 'model')
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->map(function ($device) {
                return [
                    'uuid' => $device->uuid,
                    'name' => $device->full_name, // Using the accessor for "brand - model"
                    'brand' => $device->brand,
                    'model' => $device->model,
                ];
            });

        return Inertia::render('CRM/Orders/Create', [
            'suppliers' => $suppliers,
            'devices' => $devices,
            'statusOptions' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
            ],
            'typeOptions' => [
                'locally' => 'Locally',
                'externally' => 'Externally',
            ],
            'priorityOptions' => [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'urgent' => 'Urgent',
            ],
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:locally,externally',
            'status' => 'required|string|in:draft,pending',
            'supplier_id' => 'required|exists:suppliers,uuid',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'total_ht' => 'nullable|numeric|min:0',
            'total_tva' => 'nullable|numeric|min:0',
            'total_ttc' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'devices' => 'nullable|array',
            'devices.*.device_id' => 'required|exists:devices,uuid',
            'devices.*.qty_ordered' => 'required|integer|min:1',
            'devices.*.ht_price' => 'required|numeric|min:0',
            'devices.*.tva_rate' => 'required|numeric|min:0|max:100',
            'devices.*.notes' => 'nullable|string|max:500',
        ]);

        $validated['requested_by'] = Auth::id();
        $validated['order_number'] = Order::generateOrderNumber();

        // Create the order (exclude devices from the attributes)
        $order = Order::create(collect($validated)->except('devices')->toArray());

        // Attach devices to the order if provided
        if (!empty($validated['devices'])) {
            foreach ($validated['devices'] as $deviceData) {
                $order->devices()->attach($deviceData['device_id'], [
                    'supplier_id' => $validated['supplier_id'],
                    'qty_ordered' => $deviceData['qty_ordered'],
                    'ht_price' => $deviceData['ht_price'],
                    'tva_rate' => $deviceData['tva_rate'],
                    'tva_price' => ($deviceData['ht_price'] * $deviceData['qty_ordered']) * ($deviceData['tva_rate'] / 100),
                    'ttc_price' => ($deviceData['ht_price'] * $deviceData['qty_ordered']) * (1 + ($deviceData['tva_rate'] / 100)),
                    'status' => 'pending',
                    'notes' => $deviceData['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('crm.orders.show', $order)
            ->with('success', 'Order created successfully.');
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $order->load([
            'requestedBy',
            'approvedBy',
            'supplier',
            'quotationFile',
            'devices',
            'arrivals.device',
        ]);

        return Inertia::render('CRM/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        $order->load([
            'supplier' => function ($query) {
                $query->select('uuid', 'name', 'code', 'email');
            },
            'devices' => function ($query) {
                $query->select('devices.uuid', 'devices.brand', 'devices.model', 'devices.category', 'devices.stock_qty');
            }
        ]);

        $suppliers = Supplier::active()
            ->select('uuid', 'name', 'code')
            ->orderBy('name')
            ->get();

        $devices = Device::select('uuid', 'brand', 'model')
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->uuid,
                    'uuid' => $device->uuid,
                    'name' => $device->full_name, // Using the accessor for "brand - model"
                    'brand' => $device->brand,
                    'model' => $device->model,
                ];
            });

        return Inertia::render('CRM/Orders/Edit', [
            'order' => $order,
            'suppliers' => $suppliers,
            'devices' => $devices,
            'statusOptions' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'ordered' => 'Ordered',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
            'typeOptions' => [
                'locally' => 'Locally',
                'externally' => 'Externally',
            ],
            'priorityOptions' => [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
            ],
        ]);
    }

/**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:locally,externally',
            'status' => 'required|string|in:draft,pending,approved,ordered,completed,cancelled',
            'supplier_id' => 'required|exists:suppliers,uuid',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'actual_delivery_date' => 'nullable|date',
            'total_ht' => 'nullable|numeric|min:0',
            'total_tva' => 'nullable|numeric|min:0',
            'total_ttc' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'devices' => 'nullable|array',
            'devices.*.device_id' => 'required|exists:devices,uuid',
            'devices.*.qty_ordered' => 'required|integer|min:1',
            'devices.*.ht_price' => 'required|numeric|min:0',
            'devices.*.tva_rate' => 'required|numeric|min:0|max:100',
            'devices.*.notes' => 'nullable|string|max:500',
        ]);

        // Update order basic fields (exclude devices from the update)
        $order->update(collect($validated)->except('devices')->toArray());

        // Handle devices update if provided
        if (isset($validated['devices'])) {
            // Detach all existing devices
            $order->devices()->detach();
            
            // Attach new devices
            foreach ($validated['devices'] as $deviceData) {
                $htPrice = $deviceData['ht_price'];
                $qtyOrdered = $deviceData['qty_ordered'];
                $tvaRate = $deviceData['tva_rate'];
                
                // Calculate TVA and TTC prices per unit (not total)
                $tvaPrice = ($htPrice * $tvaRate) / 100;
                $ttcPrice = $htPrice + $tvaPrice;

                $order->devices()->attach($deviceData['device_id'], [
                    'supplier_id' => $validated['supplier_id'],
                    'qty_ordered' => $qtyOrdered,
                    'ht_price' => $htPrice,
                    'tva_rate' => $tvaRate,
                    'tva_price' => $tvaPrice,
                    'ttc_price' => $ttcPrice,
                    'status' => 'pending',
                    'notes' => $deviceData['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('crm.orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        // Check if order has related arrivals or devices
        if ($order->arrivals()->count() > 0 || $order->devices()->count() > 0) {
            return back()->with('error', 'Cannot delete order with related arrivals or devices.');
        }

        $order->delete();

        return redirect()->route('crm.orders.index')
            ->with('success', 'Order deleted successfully.');
    }

    /**
     * Approve the specified order.
     */
    public function approve(Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $order->approve(Auth::user());

        return back()->with('success', 'Order approved successfully.');
    }

    /**
     * Mark the order as ordered.
     */
    public function markAsOrdered(Order $order)
    {
        if (!$order->is_approved) {
            return back()->with('error', 'Only approved orders can be marked as ordered.');
        }

        $order->markAsOrdered();

        return back()->with('success', 'Order marked as ordered successfully.');
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted(Order $order)
    {
        if ($order->status !== 'ordered') {
            return back()->with('error', 'Only ordered orders can be marked as completed.');
        }

        $order->markAsCompleted();

        return back()->with('success', 'Order marked as completed successfully.');
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(Order $order)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot cancel completed or already cancelled orders.');
        }

        $order->cancel();

        return back()->with('success', 'Order cancelled successfully.');
    }
}