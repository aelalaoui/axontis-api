<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallationAssignmentController extends Controller
{
    /**
     * Return staff users (non-client) for the assignment panel.
     */
    public function staff()
    {
        $users = User::whereNot('role', UserRole::CLIENT->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'role']);

        return response()->json([
            'users' => $users->map(fn($u) => [
                'id'   => $u->id,
                'uuid' => $u->uuid,
                'name' => $u->name,
                'role' => $u->role instanceof UserRole ? $u->role->value : $u->role,
            ]),
        ]);
    }

    /**
     * Assign devices to an installation.
     *
     * Expected payload:
     * {
     *   "delivery_mode": "on_site" | "postal",
     *   "technician_id": 5,           // required if on_site
     *   "scheduled_date": "2026-04-01", // optional, if on_site
     *   "postal_address": "...",       // required if postal
     *   "devices": [
     *     {
     *       "device_id": 12,
     *       "serial_number": "SN-001",
     *       "status": "assigned",
     *       "notes": "...",
     *       "properties": { "key": "value", ... }
     *     },
     *     ...
     *   ]
     * }
     */
    public function assign(Request $request, string $installationUuid)
    {
        $request->validate([
            'delivery_mode'             => 'required|in:on_site,postal',
            'technician_id'             => 'required_if:delivery_mode,on_site|nullable|exists:users,id',
            'scheduled_date'            => 'nullable|date',
            'postal_address'            => 'required_if:delivery_mode,postal|nullable|string|max:500',
            'devices'                   => 'required|array|min:1',
            'devices.*.device_id'       => 'required|exists:devices,id',
            'devices.*.serial_number'   => 'nullable|string|max:191',
            'devices.*.status'          => 'nullable|in:assigned,installed,returned,maintenance,replaced',
            'devices.*.notes'           => 'nullable|string',
            'devices.*.properties'      => 'nullable|array',
        ]);

        $installation = Installation::where('uuid', $installationUuid)->firstOrFail();

        try {
            DB::transaction(function () use ($request, $installation) {
                $isOnSite = $request->delivery_mode === 'on_site';

                // Build task notes
                $notes = $isOnSite
                    ? null
                    : ('Envoi postal — adresse : ' . $request->postal_address);

                // Create the Task linked to this installation
                $task = Task::create([
                    'taskable_type'  => Installation::class,
                    'taskable_id'    => $installation->id,
                    'address'        => $installation->address ?? '',
                    'type'           => 'installation',
                    'status'         => 'scheduled',
                    'user_id'        => $isOnSite ? $request->technician_id : null,
                    'scheduled_date' => $request->scheduled_date,
                    'notes'          => $notes,
                ]);

                // Attach each device
                foreach ($request->devices as $deviceData) {
                    $task->devices()->attach($deviceData['device_id'], [
                        'serial_number' => $deviceData['serial_number'] ?? null,
                        'status'        => $deviceData['status'] ?? 'assigned',
                        'notes'         => $deviceData['notes'] ?? null,
                    ]);

                    // If there are custom properties, store them on the InstallationDevice record
                    if (!empty($deviceData['properties'])) {
                        $installationDevice = InstallationDevice::where('task_id', $task->id)
                            ->where('device_id', $deviceData['device_id'])
                            ->latest()
                            ->first();

                        if ($installationDevice) {
                            foreach ($deviceData['properties'] as $key => $value) {
                                $installationDevice->setProperty($key, $value);
                            }
                        }
                    }
                }
            });

            return back()->with('success', 'Installation assignée avec succès.');
        } catch (\Exception $e) {
            Log::error('InstallationAssignment failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'assignation : ' . $e->getMessage()]);
        }
    }
}

