<?php

namespace App\Http\Controllers;

use App\Services\ArrivalService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ArrivalController extends Controller
{
    protected ArrivalService $arrivalService;

    public function __construct(ArrivalService $arrivalService)
    {
        $this->arrivalService = $arrivalService;
    }

    /**
     * Process arrival for an order
     */
    public function processArrival(Request $request, string $orderId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'arrivals' => 'required|array|min:1',
                'arrivals.*.device_id' => 'required|string',
                'arrivals.*.qty' => 'required|integer|min:1',
                'arrivals.*.arrival_date' => 'nullable|date',
                'arrivals.*.invoice_number' => 'nullable|string|max:255',
                'arrivals.*.notes' => 'nullable|string|max:1000',
            ]);

            // Validate arrival data
            $validationErrors = $this->arrivalService->validateArrivalData($orderId, $validated['arrivals']);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationErrors
                ], 422);
            }

            $result = $this->arrivalService->processOrderArrival($orderId, $validated['arrivals']);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'order' => [
                        'id' => $result['order']->uuid,
                        'order_number' => $result['order']->order_number,
                        'status' => $result['order']->status,
                        'status_changed' => $result['order_status_changed']
                    ],
                    'arrivals' => $result['arrivals']->map(function ($arrival) {
                        return [
                            'id' => $arrival->uuid,
                            'device_id' => $arrival->device_id,
                            'device_name' => $arrival->device->full_name ?? 'Unknown',
                            'qty' => $arrival->qty,
                            'arrival_date' => $arrival->arrival_date,
                            'status' => $arrival->status
                        ];
                    }),
                    'arrivals_count' => count($result['arrivals'])
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process arrival',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process multiple arrival batches for the same order
     */
    public function processMultipleArrivals(Request $request, string $orderId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'arrival_batches' => 'required|array|min:1',
                'arrival_batches.*' => 'required|array|min:1',
                'arrival_batches.*.*.device_id' => 'required|string',
                'arrival_batches.*.*.qty' => 'required|integer|min:1',
                'arrival_batches.*.*.arrival_date' => 'nullable|date',
                'arrival_batches.*.*.invoice_number' => 'nullable|string|max:255',
                'arrival_batches.*.*.notes' => 'nullable|string|max:1000',
            ]);

            $results = $this->arrivalService->processMultipleArrivals($orderId, $validated['arrival_batches']);

            $successfulBatches = collect($results)->where('success', true)->count();
            $failedBatches = collect($results)->where('success', false)->count();

            return response()->json([
                'success' => $failedBatches === 0,
                'message' => "Processed {$successfulBatches} batch(es) successfully" .
                           ($failedBatches > 0 ? ", {$failedBatches} failed" : ""),
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total_batches' => count($results),
                        'successful_batches' => $successfulBatches,
                        'failed_batches' => $failedBatches
                    ]
                ]
            ], $failedBatches > 0 ? 207 : 200); // 207 Multi-Status if there are failures

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process multiple arrivals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get arrivals for an order
     */
    public function getOrderArrivals(string $orderId): JsonResponse
    {
        try {
            $arrivals = $this->arrivalService->getOrderArrivals($orderId);

            return response()->json([
                'success' => true,
                'data' => $arrivals->map(function ($arrival) {
                    return [
                        'id' => $arrival->uuid,
                        'device_id' => $arrival->device_id,
                        'device_name' => $arrival->device->full_name ?? 'Unknown',
                        'qty' => $arrival->qty,
                        'ht_price' => $arrival->ht_price,
                        'tva_price' => $arrival->tva_price,
                        'ttc_price' => $arrival->ttc_price,
                        'total_value' => $arrival->total_value,
                        'arrival_date' => $arrival->arrival_date,
                        'invoice_number' => $arrival->invoice_number,
                        'notes' => $arrival->notes,
                        'status' => $arrival->status,
                        'created_at' => $arrival->created_at
                    ];
                }),
                'count' => $arrivals->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get arrivals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get arrival summary for an order
     */
    public function getOrderArrivalSummary(string $orderId): JsonResponse
    {
        try {
            $summary = $this->arrivalService->getOrderArrivalSummary($orderId);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get arrival summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders ready for arrival processing (status = 'ordered')
     */
    public function getOrdersReadyForArrival(): JsonResponse
    {
        try {
            $orders = \App\Models\Order::where('status', 'ordered')
                ->with(['supplier', 'devices'])
                ->orderBy('order_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $orders->map(function ($order) {
                    return [
                        'id' => $order->uuid,
                        'order_number' => $order->order_number,
                        'supplier' => $order->supplier->name ?? 'Unknown',
                        'order_date' => $order->order_date,
                        'expected_delivery_date' => $order->expected_delivery_date,
                        'total_ttc' => $order->total_ttc,
                        'devices_count' => $order->devices->count(),
                        'total_items_ordered' => $order->devices->sum('pivot.qty_ordered'),
                        'created_at' => $order->created_at
                    ];
                }),
                'count' => $orders->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get orders ready for arrival',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
