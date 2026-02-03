<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlarmDevice;
use App\Models\AlarmEvent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour consulter l'historique des événements d'alarme.
 *
 * Lecture seule - les événements sont créés via les webhooks.
 */
class AlarmEventController extends Controller
{
    /**
     * List alarm events with filtering and pagination.
     *
     * GET /api/alarm-events
     *
     * Query params:
     * - device_uuid: filter by device
     * - type: filter by alarm type (intrusion, fire, flood, other, system)
     * - severity: filter by severity (low, medium, critical)
     * - processed: filter by processed status (true/false)
     * - with_alerts: only events that created alerts
     * - start_date: filter events after this date
     * - end_date: filter events before this date
     * - per_page: pagination (default 20)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AlarmEvent::query()
                ->with(['alarmDevice', 'alert']);

            // Device filter
            if ($request->has('device_uuid')) {
                $query->forDevice($request->device_uuid);
            }

            // Type filter
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            // Severity filter
            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }

            // Processed filter
            if ($request->has('processed')) {
                $processed = filter_var($request->processed, FILTER_VALIDATE_BOOLEAN);
                $query->where('processed', $processed);
            }

            // Only with alerts
            if ($request->boolean('with_alerts')) {
                $query->withAlerts();
            }

            // Date range
            if ($request->has('start_date')) {
                $query->triggeredAfter($request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('triggered_at', '<=', $request->end_date);
            }

            // CID code filter
            if ($request->has('cid_code')) {
                $query->byCidCode((int) $request->cid_code);
            }

            // Zone filter
            if ($request->has('zone')) {
                $query->fromZone((int) $request->zone);
            }

            // Search in description
            if ($request->has('search')) {
                $query->where('event_description', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'triggered_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $events = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $events,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmEventController: Index error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching events',
            ], 500);
        }
    }

    /**
     * Get a single alarm event.
     *
     * GET /api/alarm-events/{uuid}
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $event = AlarmEvent::with(['alarmDevice', 'alarmDevice.installation', 'alert'])
                ->where('uuid', $uuid)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $event,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmEventController: Show error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching event',
            ], 500);
        }
    }

    /**
     * Get events for a specific device.
     *
     * GET /api/alarm-devices/{uuid}/events
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function forDevice(Request $request, string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $query = $device->events()
                ->with(['alert']);

            // Type filter
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            // Severity filter
            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }

            // Date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->triggeredBetween($request->start_date, $request->end_date);
            } elseif ($request->has('start_date')) {
                $query->triggeredAfter($request->start_date);
            }

            // Default: last 24 hours if no date filter
            if (!$request->has('start_date') && !$request->has('end_date')) {
                $query->recent(1440); // 24 hours
            }

            // Sorting
            $query->orderBy('triggered_at', 'desc');

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $events = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $events,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmEventController: ForDevice error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching events',
            ], 500);
        }
    }

    /**
     * Get event statistics.
     *
     * GET /api/alarm-events/stats
     *
     * Query params:
     * - device_uuid: filter by device
     * - period: 'day', 'week', 'month', 'year' (default: 'day')
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'day');
            $startDate = match ($period) {
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => now()->subDay(),
            };

            $query = AlarmEvent::where('triggered_at', '>=', $startDate);

            if ($request->has('device_uuid')) {
                $query->forDevice($request->device_uuid);
            }

            // Basic counts
            $stats = [
                'total_events' => $query->count(),
                'alerts_created' => (clone $query)->withAlerts()->count(),
                'processed' => (clone $query)->processed()->count(),
                'unprocessed' => (clone $query)->unprocessed()->count(),
                'with_errors' => (clone $query)->withErrors()->count(),
            ];

            // By type
            $stats['by_type'] = AlarmEvent::where('triggered_at', '>=', $startDate)
                ->when($request->has('device_uuid'), fn($q) => $q->forDevice($request->device_uuid))
                ->whereNotNull('alarm_type')
                ->selectRaw('alarm_type, count(*) as count')
                ->groupBy('alarm_type')
                ->pluck('count', 'alarm_type');

            // By severity
            $stats['by_severity'] = AlarmEvent::where('triggered_at', '>=', $startDate)
                ->when($request->has('device_uuid'), fn($q) => $q->forDevice($request->device_uuid))
                ->whereNotNull('severity')
                ->selectRaw('severity, count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity');

            // Top CID codes
            $stats['top_cid_codes'] = AlarmEvent::where('triggered_at', '>=', $startDate)
                ->when($request->has('device_uuid'), fn($q) => $q->forDevice($request->device_uuid))
                ->whereNotNull('cid_code')
                ->selectRaw('cid_code, count(*) as count')
                ->groupBy('cid_code')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'cid_code');

            // Events per hour (last 24h)
            if ($period === 'day') {
                $stats['hourly_distribution'] = AlarmEvent::where('triggered_at', '>=', now()->subDay())
                    ->when($request->has('device_uuid'), fn($q) => $q->forDevice($request->device_uuid))
                    ->selectRaw('HOUR(triggered_at) as hour, count(*) as count')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->pluck('count', 'hour');
            }

            return response()->json([
                'success' => true,
                'period' => $period,
                'start_date' => $startDate->toIso8601String(),
                'data' => $stats,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmEventController: Stats error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics',
            ], 500);
        }
    }

    /**
     * Get recent critical events.
     *
     * GET /api/alarm-events/critical
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function critical(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 10), 50);

            $events = AlarmEvent::critical()
                ->with(['alarmDevice', 'alert'])
                ->orderBy('triggered_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $events,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching critical events',
            ], 500);
        }
    }

    /**
     * Get unprocessed events.
     *
     * GET /api/alarm-events/unprocessed
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unprocessed(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 50), 100);

            $events = AlarmEvent::unprocessed()
                ->with(['alarmDevice'])
                ->orderBy('triggered_at', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'count' => $events->count(),
                'data' => $events,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching unprocessed events',
            ], 500);
        }
    }
}
