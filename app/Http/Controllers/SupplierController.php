<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort functionality
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $suppliers = $query->paginate(15)->withQueryString();

        return Inertia::render('CRM/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        return Inertia::render('CRM/Suppliers/Create');
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:suppliers,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('crm.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['orders', 'orderDevices']);

        return Inertia::render('CRM/Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        return Inertia::render('CRM/Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers')->ignore($supplier->id)],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('crm.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Check if supplier has related orders
        if ($supplier->orders()->count() > 0 || $supplier->orderDevices()->count() > 0) {
            return redirect()->route('crm.suppliers.index')
                ->with('error', 'Cannot delete supplier with existing orders or devices.');
        }

        $supplier->delete();

        return redirect()->route('crm.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Search suppliers for autocomplete.
     */
    public function searchSuppliers(Request $request)
    {
        $query = $request->get('query', $request->get('q', ''));

        if (empty($query) || strlen(trim($query)) < 2) {
            return response()->json([]);
        }

        $suppliers = Supplier::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->select('uuid', 'name', 'code', 'email')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    /**
     * Toggle supplier active status.
     */
    public function toggleStatus(Supplier $supplier)
    {
        if ($supplier->is_active) {
            $supplier->deactivate();
            $message = 'Supplier deactivated successfully.';
        } else {
            $supplier->activate();
            $message = 'Supplier activated successfully.';
        }

        return redirect()->back()->with('success', $message);
    }
}
