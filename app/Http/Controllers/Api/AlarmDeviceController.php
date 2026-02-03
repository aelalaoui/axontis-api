<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlarmDeviceRequest;
use App\Http\Requests\UpdateAlarmDeviceRequest;
use App\Jobs\CheckAlarmDeviceStatusJob;
use App\Models\AlarmDevice;
use App\Services\HikvisionApiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour la gestion des centrales d'alarme Hikvision.
 *
 * CRUD complet + opérations spécifiques (test connexion, armement, etc.)
 */
class AlarmDeviceController extends Controller
{
    protected HikvisionApiService $hikvisionApi;

    public function __construct(HikvisionApiService $hikvisionApi)
    {
        $this->hikvisionApi = $hikvisionApi;
    }

    /**
     * List all alarm devices.
     *
     * GET /api/alarm-devices
     *
     * Query params:
     * - status: filter by status (online, offline, error)
     * - installation_uuid: filter by installation
     * - per_page: pagination (default 20)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AlarmDevice::query()
                ->with(['installation', 'installation.client']);

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('installation_uuid')) {
                $query->where('installation_uuid', $request->installation_uuid);
            }

            if ($request->has('arm_status')) {
                $query->where('arm_status', $request->arm_status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('mac_address', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $devices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $devices,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Index error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching devices',
            ], 500);
        }
    }

    /**
     * Get a single alarm device.
     *
     * GET /api/alarm-devices/{uuid}
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::with(['installation', 'installation.client', 'installation.contract'])
                ->where('uuid', $uuid)
                ->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            // Add computed statistics
            $device->recent_events_count = $device->getRecentEventsCount(60);
            $device->recent_alerts_count = $device->getRecentAlertsCount(60);

            return response()->json([
                'success' => true,
                'data' => $device,
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Show error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching device',
            ], 500);
        }
    }

    /**
     * Create a new alarm device.
     *
     * POST /api/alarm-devices
     *
     * @param StoreAlarmDeviceRequest $request
     * @return JsonResponse
     */
    public function store(StoreAlarmDeviceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Handle password separately (it will be encrypted by mutator)
            if (isset($data['api_password'])) {
                $password = $data['api_password'];
                unset($data['api_password']);
            }

            $device = AlarmDevice::create($data);

            // Set encrypted password
            if (isset($password)) {
                $device->api_password = $password;
                $device->save();
            }

            Log::info('AlarmDeviceController: Device created', [
                'device_uuid' => $device->uuid,
                'name' => $device->name,
                'serial_number' => $device->serial_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device created successfully',
                'data' => $device,
            ], 201);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Store error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating device',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update an alarm device.
     *
     * PUT /api/alarm-devices/{uuid}
     *
     * @param UpdateAlarmDeviceRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(UpdateAlarmDeviceRequest $request, string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $data = $request->validated();

            // Handle password separately
            if (isset($data['api_password'])) {
                $device->api_password = $data['api_password'];
                unset($data['api_password']);
            }

            $device->update($data);

            Log::info('AlarmDeviceController: Device updated', [
                'device_uuid' => $device->uuid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device updated successfully',
                'data' => $device->fresh(),
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Update error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating device',
            ], 500);
        }
    }

    /**
     * Delete an alarm device.
     *
     * DELETE /api/alarm-devices/{uuid}
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $device->delete(); // Soft delete

            Log::info('AlarmDeviceController: Device deleted', [
                'device_uuid' => $uuid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device deleted successfully',
            ], 200);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Destroy error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting device',
            ], 500);
        }
    }

    /**
     * Test connection to a device.
     *
     * POST /api/alarm-devices/{uuid}/test-connection
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function testConnection(string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $result = $this->hikvisionApi->testConnection($device);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ], $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Test connection error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error testing connection',
            ], 500);
        }
    }

    /**
     * Get device info from the central.
     *
     * GET /api/alarm-devices/{uuid}/info
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function getDeviceInfo(string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $result = $this->hikvisionApi->getDeviceInfo($device);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting device info',
            ], 500);
        }
    }

    /**
     * Get device status (arm status, zones, etc.).
     *
     * GET /api/alarm-devices/{uuid}/status
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function getStatus(string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $result = $this->hikvisionApi->getStatus($device);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting device status',
            ], 500);
        }
    }

    /**
     * Arm the device.
     *
     * POST /api/alarm-devices/{uuid}/arm
     *
     * Body:
     * - mode: 'away' | 'stay' (default: 'away')
     * - partition: int (optional)
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function arm(Request $request, string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $mode = $request->get('mode', 'away');
            $partition = $request->get('partition');

            $result = match ($mode) {
                'stay' => $this->hikvisionApi->armStay($device, $partition),
                default => $this->hikvisionApi->armAway($device, $partition),
            };

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Arm error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error arming device',
            ], 500);
        }
    }

    /**
     * Disarm the device.
     *
     * POST /api/alarm-devices/{uuid}/disarm
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function disarm(Request $request, string $uuid): JsonResponse
    {
        try {
            $device = AlarmDevice::where('uuid', $uuid)->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $partition = $request->get('partition');
            $result = $this->hikvisionApi->disarm($device, $partition);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('AlarmDeviceController: Disarm error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error disarming device',
            ], 500);
        }
    }

    /**
     * Refresh status of all stale devices.
     *
     * POST /api/alarm-devices/refresh-status
     *
     * @return JsonResponse
     */
    public function refreshStatus(): JsonResponse
    {
        try {
            CheckAlarmDeviceStatusJob::dispatch()
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            return response()->json([
                'success' => true,
                'message' => 'Status refresh job dispatched',
            ], 202);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error dispatching refresh job',
            ], 500);
        }
    }

    /**
     * Get statistics for all devices.
     *
     * GET /api/alarm-devices/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => AlarmDevice::count(),
                'online' => AlarmDevice::online()->count(),
                'offline' => AlarmDevice::offline()->count(),
                'error' => AlarmDevice::withErrors()->count(),
                'armed' => AlarmDevice::armed()->count(),
                'disarmed' => AlarmDevice::disarmed()->count(),
                'by_model' => AlarmDevice::selectRaw('model, count(*) as count')
                    ->groupBy('model')
                    ->pluck('count', 'model'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics',
            ], 500);
        }
    }
}
