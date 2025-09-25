<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    /**
     * Display a listing of devices.
     */
    public function index(Request $request)
    {
        $query = Device::query()
            ->orderBy($request->get('sort', 'created_at'), $request->get('direction', 'desc'));

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Stock status filter
        if ($request->filled('stock_status')) {
            $stockStatus = $request->get('stock_status');
            if ($stockStatus === 'low_stock') {
                $query->lowStock();
            } elseif ($stockStatus === 'out_of_stock') {
                $query->outOfStock();
            }
        }

        $devices = $query->paginate(15)->withQueryString();

        // Get unique categories for filter dropdown
        $categories = Device::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('CRM/Devices/Index', [
            'devices' => $devices,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'stock_status', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new device.
     */
    public function create()
    {
        // Get existing categories for dropdown
        $categories = Device::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('CRM/Devices/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created device in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stock_qty' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
        ]);

        Device::create($validated);

        return redirect()->route('crm.devices.index')
            ->with('success', 'Device created successfully.');
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device)
    {
        $device->load([
            'orderDevices.order.supplier',
            'orderDevices' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        return Inertia::render('CRM/Devices/Show', [
            'device' => $device,
        ]);
    }

    /**
     * Show the form for editing the specified device.
     */
    public function edit(Device $device)
    {
        // Get existing categories for dropdown
        $categories = Device::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('CRM/Devices/Edit', [
            'device' => $device,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified device in storage.
     */
    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stock_qty' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
        ]);

        $device->update($validated);

        return redirect()->route('crm.devices.index')
            ->with('success', 'Device updated successfully.');
    }

    /**
     * Remove the specified device from storage.
     */
    public function destroy(Device $device)
    {
        // Check if device has any active orders
        $hasActiveOrders = $device->orderDevices()
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['draft', 'pending', 'approved', 'ordered', 'partially_received']);
            })
            ->exists();

        if ($hasActiveOrders) {
            return redirect()->route('crm.devices.index')
                ->with('error', 'Cannot delete device with active orders.');
        }

        $device->delete();

        return redirect()->route('crm.devices.index')
            ->with('success', 'Device deleted successfully.');
    }

    /**
     * Search devices for autocomplete (API endpoint).
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        $devices = Device::where(function ($query) use ($search) {
            $query->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
        })
        ->limit(10)
        ->get(['id', 'brand', 'model', 'category', 'stock_qty', 'min_stock_level'])
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'label' => $device->full_name,
                'brand' => $device->brand,
                'model' => $device->model,
                'category' => $device->category,
                'stock_qty' => $device->stock_qty,
                'min_stock_level' => $device->min_stock_level,
                'is_low_stock' => $device->is_low_stock,
                'is_out_of_stock' => $device->is_out_of_stock,
            ];
        });

        return response()->json($devices);
    }

    /**
     * Update stock quantity for a device.
     */
    public function updateStock(Request $request, Device $device)
    {
        $validated = $request->validate([
            'action' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:0',
        ]);

        $action = $validated['action'];
        $quantity = $validated['quantity'];

        switch ($action) {
            case 'add':
                $device->addStock($quantity);
                $message = "Added {$quantity} units to stock.";
                break;
            case 'remove':
                if ($device->removeStock($quantity)) {
                    $message = "Removed {$quantity} units from stock.";
                } else {
                    return redirect()->back()
                        ->with('error', 'Insufficient stock quantity.');
                }
                break;
            case 'set':
                $device->update(['stock_qty' => $quantity]);
                $message = "Stock quantity set to {$quantity} units.";
                break;
        }

        return redirect()->back()
            ->with('success', $message);
    }
}