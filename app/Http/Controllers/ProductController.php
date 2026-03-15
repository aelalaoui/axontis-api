<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Product;
use App\Services\FileService;
use App\Traits\ManagesFiles;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    use ManagesFiles;

    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['device', 'parent', 'children', 'documents'])
            ->withCount('children')
            ->whereNull('id_parent'); // Only show parent products by default

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('property_name', 'like', "%{$search}%")
                  ->orWhere('default_value', 'like', "%{$search}%")
                  ->orWhereHas('device', function ($deviceQuery) use ($search) {
                      $deviceQuery->where('brand', 'like', "%{$search}%")
                                  ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        // Type filter (parent or child)
        if ($request->filled('type')) {
            if ($request->type === 'parent') {
                $query->whereNull('id_parent');
            } elseif ($request->type === 'child') {
                $query->whereNotNull('id_parent');
            } elseif ($request->type === 'all') {
                // Remove the default parent-only filter to show all products
                $query = Product::with(['device', 'parent', 'children'])
                    ->withCount('children');

                // Reapply search filter if exists
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('property_name', 'like', "%{$search}%")
                          ->orWhere('default_value', 'like', "%{$search}%")
                          ->orWhereHas('device', function ($deviceQuery) use ($search) {
                              $deviceQuery->where('brand', 'like', "%{$search}%")
                                          ->orWhere('model', 'like', "%{$search}%");
                          });
                    });
                }
            }
        }

        // Device status filter
        if ($request->filled('device_status')) {
            if ($request->device_status === 'with_device') {
                $query->whereNotNull('device_uuid');
            } elseif ($request->device_status === 'without_device') {
                $query->whereNull('device_uuid');
            }
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('CRM/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'type', 'device_status'])
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $devices = Device::select('uuid', 'brand', 'model', 'category', 'stock_qty', 'min_stock_level')
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->map(function ($device) {
                $device->full_name = "{$device->brand} - {$device->model}";
                return $device;
            });

        return Inertia::render('CRM/Products/Create', [
            'devices' => $devices
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'property_name' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'caution_price' => 'nullable|numeric|min:0',
            'subscription_price' => 'nullable|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => FileService::getFileValidationRules(),
            'sub_products' => 'array',
            'sub_products.*.name' => 'required|string|max:255',
            'sub_products.*.property_name' => 'nullable|string|max:255',
            'sub_products.*.default_value' => 'nullable|string|max:255',
            'sub_products.*.caution_price' => 'nullable|numeric|min:0',
            'sub_products.*.subscription_price' => 'nullable|numeric|min:0',
            'sub_products.*.device_uuid' => 'nullable|exists:devices,uuid',
        ]);

        // Convert prices to cents (integer) to avoid floating-point errors
        $cautionPriceCents = $validated['caution_price'] !== null
            ? (int) round($validated['caution_price'] * 100)
            : null;
        $subscriptionPriceCents = $validated['subscription_price'] !== null
            ? (int) round($validated['subscription_price'] * 100)
            : null;

        // Create parent product
        $product = Product::create([
            'name' => $validated['name'],
            'property_name' => $validated['property_name'],
            'default_value' => $validated['default_value'],
            'caution_price_cents' => $cautionPriceCents,
            'subscription_price_cents' => $subscriptionPriceCents,
            'device_uuid' => null, // Parent products don't have devices directly
        ]);

        // Handle document uploads using FileService
        if (!empty($validated['documents'])) {
            $this->fileService->uploadMultipleFiles($validated['documents'], $product, 'document');
        }

        // Create sub-products
        if (!empty($validated['sub_products'])) {
            foreach ($validated['sub_products'] as $subProductData) {
                $subCautionCents = $subProductData['caution_price'] !== null && $subProductData['caution_price'] !== ''
                    ? (int) round($subProductData['caution_price'] * 100)
                    : $cautionPriceCents;

                $subSubscriptionCents = $subProductData['subscription_price'] !== null && $subProductData['subscription_price'] !== ''
                    ? (int) round($subProductData['subscription_price'] * 100)
                    : $subscriptionPriceCents;

                Product::create([
                    'id_parent' => $product->id,
                    'name' => $subProductData['name'],
                    'property_name' => $subProductData['property_name'] ?? null,
                    'default_value' => $subProductData['default_value'] ?? null,
                    'caution_price_cents' => $subCautionCents,
                    'subscription_price_cents' => $subSubscriptionCents,
                    'device_uuid' => $subProductData['device_uuid'] ?? null,
                ]);
            }
        }

        return redirect()->route('crm.products.index')
            ->with('success', 'Product created successfully with ' . count($validated['sub_products'] ?? []) . ' sub-products.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load([
            'device',
            'parent',
            'children.device',
            'documents'
        ]);

        return Inertia::render('CRM/Products/Show', [
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $product->load(['children.device', 'device', 'documents']);

        $devices = Device::select('uuid', 'brand', 'model', 'category', 'stock_qty', 'min_stock_level')
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->map(function ($device) {
                $device->full_name = "{$device->brand} - {$device->model}";
                return $device;
            });

        // Force clear any session errors when loading the edit page
        session()->forget('errors');

        return Inertia::render('CRM/Products/Edit', [
            'product' => $product,
            'devices' => $devices
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'property_name' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'caution_price' => 'nullable|numeric|min:0',
            'subscription_price' => 'nullable|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => FileService::getFileValidationRules(),
            'documents_to_delete' => 'nullable|array',
            'documents_to_delete.*' => 'exists:files,uuid',
            'sub_products' => 'array',
            'sub_products.*.id' => 'nullable|exists:products,id',
            'sub_products.*.name' => 'required|string|max:255',
            'sub_products.*.property_name' => 'nullable|string|max:255',
            'sub_products.*.default_value' => 'nullable|string|max:255',
            'sub_products.*.caution_price' => 'nullable|numeric|min:0',
            'sub_products.*.subscription_price' => 'nullable|numeric|min:0',
            'sub_products.*.device_uuid' => 'nullable|exists:devices,uuid',
        ]);

        // Convert prices to cents (integer) to avoid floating-point errors
        $cautionPriceCents = $validated['caution_price'] !== null
            ? (int) round($validated['caution_price'] * 100)
            : null;
        $subscriptionPriceCents = $validated['subscription_price'] !== null
            ? (int) round($validated['subscription_price'] * 100)
            : null;

        // Update parent product
        $product->update([
            'name' => $validated['name'],
            'property_name' => $validated['property_name'],
            'default_value' => $validated['default_value'],
            'caution_price_cents' => $cautionPriceCents,
            'subscription_price_cents' => $subscriptionPriceCents,
        ]);

        // Handle document deletion using FileService
        if (!empty($validated['documents_to_delete'])) {
            $this->fileService->deleteMultipleFiles($validated['documents_to_delete'], $product);
        }

        // Handle new document uploads using FileService
        if (!empty($validated['documents'])) {
            try {
                $this->fileService->uploadMultipleFiles($validated['documents'], $product, 'document');
            } catch (\Exception $e) {
                \Log::error('ProductController@update - Document upload failed:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }

        // Handle sub-products
        $existingSubProductIds = $product->children()->pluck('id')->toArray();
        $submittedSubProductIds = collect($validated['sub_products'] ?? [])
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete removed sub-products
        $toDelete = array_diff($existingSubProductIds, $submittedSubProductIds);
        if (!empty($toDelete)) {
            Product::whereIn('id', $toDelete)->delete();
        }

        // Update or create sub-products
        foreach ($validated['sub_products'] ?? [] as $subProductData) {
            $subCautionCents = $subProductData['caution_price'] !== null && $subProductData['caution_price'] !== ''
                ? (int) round($subProductData['caution_price'] * 100)
                : $cautionPriceCents;

            $subSubscriptionCents = $subProductData['subscription_price'] !== null && $subProductData['subscription_price'] !== ''
                ? (int) round($subProductData['subscription_price'] * 100)
                : $subscriptionPriceCents;

            if (!empty($subProductData['id'])) {
                // Update existing sub-product
                Product::where('id', $subProductData['id'])->update([
                    'name' => $subProductData['name'],
                    'property_name' => $subProductData['property_name'] ?? null,
                    'default_value' => $subProductData['default_value'] ?? null,
                    'caution_price_cents' => $subCautionCents,
                    'subscription_price_cents' => $subSubscriptionCents,
                    'device_uuid' => $subProductData['device_uuid'] ?? null,
                ]);
            } else {
                // Create new sub-product
                Product::create([
                    'id_parent' => $product->id,
                    'name' => $subProductData['name'],
                    'property_name' => $subProductData['property_name'] ?? null,
                    'default_value' => $subProductData['default_value'] ?? null,
                    'caution_price_cents' => $subCautionCents,
                    'subscription_price_cents' => $subSubscriptionCents,
                    'device_uuid' => $subProductData['device_uuid'] ?? null,
                ]);
            }
        }

        return redirect()->route('crm.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $childrenCount = $product->children()->count();

        // Delete all children products first
        if ($childrenCount > 0) {
            $product->children()->delete();
        }

        // Then delete the parent product
        $product->delete();

        $message = $childrenCount > 0
            ? "Product deleted successfully with {$childrenCount} sub-products."
            : "Product deleted successfully.";

        return redirect()->route('crm.products.index')
            ->with('success', $message);
    }
}
