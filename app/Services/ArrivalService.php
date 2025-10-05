<?php

namespace App\Services;

use App\Models\Arrival;
use App\Models\Order;
use App\Models\Device;
use App\Models\OrderDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class ArrivalService
{
    /**
     * Process arrival for an order with 'ordered' status
     *
     * @param string $orderId
     * @param array $arrivals - Array of arrivals with device_id, qty, and optional data
     * @return array
     * @throws Exception
     */
    public function processOrderArrival(string $orderId, array $arrivals): array
    {
        return DB::transaction(function () use ($orderId, $arrivals) {
            $order = Order::where('uuid', $orderId)
                          ->whereIn('status', ['ordered', 'partially_received'])
                          ->firstOrFail();

            $processedArrivals = [];
            $orderUpdated = false;

            foreach ($arrivals as $arrivalData) {
                $arrival = $this->createArrival($order, $arrivalData);
                $this->processArrivalItem($arrival);
                $processedArrivals[] = $arrival;
            }

            // Check if order should be marked as completed
            if ($this->shouldCompleteOrder($order)) {
                $order->status = 'completed';
                $order->save();
                $orderUpdated = true;
            } elseif ($this->hasPartialArrivals($order)) {
                $order->status = 'partially_received';
                $order->save();
                $orderUpdated = true;
            }

            return [
                'order' => $order,
                'arrivals' => $processedArrivals,
                'order_status_changed' => $orderUpdated,
                'message' => $this->getProcessingMessage($order, count($processedArrivals))
            ];
        });
    }

    /**
     * Create arrival record
     */
    private function createArrival(Order $order, array $arrivalData): Arrival
    {
        $device = Device::where('uuid', $arrivalData['device_id'])->firstOrFail();

        $orderDevice = OrderDevice::where('order_uuid', $order->uuid)
                                 ->where('device_uuid', $device->uuid)
                                 ->firstOrFail();

        return Arrival::create([
            'device_id' => $device->uuid,
            'order_id' => $order->uuid,
            'ht_price' => $orderDevice->ht_price,
            'tva_price' => $orderDevice->tva_price,
            'ttc_price' => $orderDevice->ttc_price,
            'qty' => $arrivalData['qty'],
            'order_number' => $order->order_number,
            'supplier' => $order->supplier->name ?? 'Unknown',
            'arrival_date' => $arrivalData['arrival_date'] ?? now(),
            'invoice_number' => $arrivalData['invoice_number'] ?? null,
            'notes' => $arrivalData['notes'] ?? null,
            'status' => 'received',
        ]);
    }

    /**
     * Process individual arrival item (update quantities and stock)
     */
    private function processArrivalItem(Arrival $arrival): void
    {
        // CORRECTION CRITIQUE: Utiliser des requêtes SQL directes pour éviter les problèmes Eloquent

        // 1. Mise à jour DIRECTE de l'OrderDevice avec WHERE strict
        $updatedRows = \DB::table('order_device')
            ->where('order_uuid', $arrival->order_id)
            ->where('device_uuid', $arrival->device_id)
            ->increment('qty_received', $arrival->qty);

        if ($updatedRows === 0) {
            \Log::error('CRITICAL: No OrderDevice found to update', [
                'order_uuid' => $arrival->order_id,
                'device_uuid' => $arrival->device_id,
                'arrival_qty' => $arrival->qty
            ]);
            return;
        }

        \Log::info('OrderDevice updated with direct SQL', [
            'order_uuid' => $arrival->order_id,
            'device_uuid' => $arrival->device_id,
            'qty_added' => $arrival->qty,
            'rows_affected' => $updatedRows
        ]);

        // 2. Mettre à jour le statut de l'OrderDevice selon la logique
        $orderDevice = \DB::table('order_device')
            ->where('order_uuid', $arrival->order_id)
            ->where('device_uuid', $arrival->device_id)
            ->first();

        if ($orderDevice) {
            $newStatus = ($orderDevice->qty_received >= $orderDevice->qty_ordered) ? 'received' : 'partially_received';

            \DB::table('order_device')
                ->where('order_uuid', $arrival->order_id)
                ->where('device_uuid', $arrival->device_id)
                ->update(['status' => $newStatus]);

            \Log::info('OrderDevice status updated', [
                'order_uuid' => $arrival->order_id,
                'device_uuid' => $arrival->device_id,
                'new_status' => $newStatus,
                'qty_received' => $orderDevice->qty_received,
                'qty_ordered' => $orderDevice->qty_ordered
            ]);
        }

        // 3. Mise à jour DIRECTE du stock du device
        $deviceUpdatedRows = \DB::table('devices')
            ->where('uuid', $arrival->device_id)
            ->increment('stock_qty', $arrival->qty);

        \Log::info('Device stock updated with direct SQL', [
            'device_uuid' => $arrival->device_id,
            'qty_added' => $arrival->qty,
            'rows_affected' => $deviceUpdatedRows
        ]);

        // 4. Marquer l'arrivée comme stockée
        $arrival->status = 'stocked';
        $arrival->save();

        \Log::info('Arrival marked as stocked', [
            'arrival_uuid' => $arrival->uuid,
            'order_uuid' => $arrival->order_id,
            'device_uuid' => $arrival->device_id
        ]);
    }

    /**
     * Check if order should be marked as completed
     */
    private function shouldCompleteOrder(Order $order): bool
    {
        $orderDevices = OrderDevice::where('order_uuid', $order->uuid)->get();

        return $orderDevices->every(function ($orderDevice) {
            return $orderDevice->is_fully_received;
        });
    }

    /**
     * Check if order has partial arrivals
     */
    private function hasPartialArrivals(Order $order): bool
    {
        $orderDevices = OrderDevice::where('order_uuid', $order->uuid)->get();

        return $orderDevices->some(function ($orderDevice) {
            return $orderDevice->qty_received > 0 && !$orderDevice->is_fully_received;
        });
    }

    /**
     * Get all arrivals for an order
     */
    public function getOrderArrivals(string $orderId): Collection
    {
        return Arrival::where('order_id', $orderId)
                     ->with(['device', 'order'])
                     ->orderBy('arrival_date', 'desc')
                     ->get();
    }

    /**
     * Get arrival summary for an order
     */
    public function getOrderArrivalSummary(string $orderId): array
    {
        $order = Order::where('uuid', $orderId)->with(['devices', 'arrivals'])->firstOrFail();

        $summary = [
            'order_id' => $orderId,
            'order_number' => $order->order_number,
            'order_status' => $order->status,
            'total_arrivals' => $order->arrivals->count(),
            'devices' => []
        ];

        foreach ($order->devices as $device) {
            $orderDevice = $device->pivot;
            $arrivals = $order->arrivals->where('device_id', $device->uuid);

            $summary['devices'][] = [
                'device_id' => $device->uuid,
                'device_name' => $device->full_name,
                'qty_ordered' => $orderDevice->qty_ordered,
                'qty_received' => $orderDevice->qty_received,
                'qty_pending' => $orderDevice->qty_pending,
                'is_fully_received' => $orderDevice->is_fully_received,
                'arrivals_count' => $arrivals->count(),
                'arrival_dates' => $arrivals->pluck('arrival_date')->unique()->values()->toArray(),
            ];
        }

        return $summary;
    }

    /**
     * Handle multiple arrivals for same order (common scenario)
     */
    public function processMultipleArrivals(string $orderId, array $arrivalBatches): array
    {
        $results = [];

        foreach ($arrivalBatches as $batchIndex => $arrivals) {
            try {
                $result = $this->processOrderArrival($orderId, $arrivals);
                $results[] = [
                    'batch' => $batchIndex + 1,
                    'success' => true,
                    'data' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'batch' => $batchIndex + 1,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Validate arrival data before processing
     */
    public function validateArrivalData(string $orderId, array $arrivals): array
    {
        $errors = [];
        $order = Order::where('uuid', $orderId)->first();

        if (!$order) {
            return ['Order not found'];
        }

        if (!in_array($order->status, ['ordered', 'partially_received'])) {
            $errors[] = "Order status must be 'ordered' or 'partially_received' to process arrivals. Current status: {$order->status}";
        }

        foreach ($arrivals as $index => $arrival) {
            $arrivalErrors = [];

            if (!isset($arrival['device_id']) || !isset($arrival['qty'])) {
                $arrivalErrors[] = 'device_id and qty are required';
            }

            if (isset($arrival['qty']) && (!is_numeric($arrival['qty']) || $arrival['qty'] <= 0)) {
                $arrivalErrors[] = 'qty must be a positive number';
            }

            if (isset($arrival['device_id'])) {
                $orderDevice = OrderDevice::where('order_uuid', $orderId)
                                         ->where('device_uuid', $arrival['device_id'])
                                         ->first();

                if (!$orderDevice) {
                    $arrivalErrors[] = 'Device not found in this order';
                } elseif (isset($arrival['qty'])) {
                    $remainingQty = $orderDevice->qty_pending;
                    if ($arrival['qty'] > $remainingQty) {
                        $arrivalErrors[] = "Quantity ({$arrival['qty']}) exceeds remaining quantity to receive ({$remainingQty})";
                    }
                }
            }

            if (!empty($arrivalErrors)) {
                $errors["arrival_{$index}"] = $arrivalErrors;
            }
        }

        return $errors;
    }

    /**
     * Get processing message based on order status
     */
    private function getProcessingMessage(Order $order, int $arrivalsCount): string
    {
        switch ($order->status) {
            case 'completed':
                return "Order completed successfully. {$arrivalsCount} arrival(s) processed.";
            case 'partially_received':
                return "Order partially received. {$arrivalsCount} arrival(s) processed. Waiting for remaining items.";
            default:
                return "{$arrivalsCount} arrival(s) processed for order {$order->order_number}.";
        }
    }
}
