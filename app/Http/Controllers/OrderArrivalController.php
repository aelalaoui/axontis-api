<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ArrivalService;
use Illuminate\Http\Request;

class OrderArrivalController extends Controller
{
    protected ArrivalService $arrivalService;
    public function __construct(ArrivalService $arrivalService)
    {
        $this->arrivalService = $arrivalService;
    }

    /**
     * Process arrival for an order
     */
    public function processArrival(Request $request, string $orderId)
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
                return back()->withErrors(['arrivals' => $validationErrors]);
            }

            $result = $this->arrivalService->processOrderArrival($orderId, $validated['arrivals']);

            return back()->with('success', $result['message']);

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Failed to process arrival: ' . $e->getMessage()]);
        }
    }

    /**
     * Get arrival data for the order (used by Vue component)
     */
    public function getArrivalData(string $orderId)
    {
        try {
            $order = Order::where('uuid', $orderId)
                         ->with(['devices', 'arrivals.device', 'supplier'])
                         ->firstOrFail();

            $summary = $this->arrivalService->getOrderArrivalSummary($orderId);
            $arrivals = $this->arrivalService->getOrderArrivals($orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'summary' => $summary,
                    'arrivals' => $arrivals,
                    'can_process_arrivals' => $order->status === 'ordered'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get arrival data: ' . $e->getMessage()
            ], 500);
        }
    }
}
