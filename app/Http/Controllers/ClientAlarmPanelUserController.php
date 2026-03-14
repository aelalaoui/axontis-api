<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Services\HikPartnerProService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CRUD utilisateurs du panel AX PRO (max 16 : 1 installateur + 1 admin + 14 normaux).
 *
 * Accès : rôle administrator uniquement.
 */
class ClientAlarmPanelUserController extends Controller
{
    public function __construct(
        private HikPartnerProService $hpp
    ) {}

    /**
     * GET /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/panel-users
     */
    public function index(Request $request, string $installationUuid, string $uuid): InertiaResponse
    {
        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        $users = $this->hpp->listPanelUsers($installationDevice);

        return Inertia::render('Client/Alarm/PanelUsers', [
            'device' => [
                'uuid' => $installationDevice->uuid,
                'brand' => $installationDevice->device?->brand,
                'model' => $installationDevice->device?->model,
                'serial_number' => $installationDevice->getPanelSerialNumber(),
            ],
            'panelUsers' => $users,
            'maxUsers' => 14,
        ]);
    }

    /**
     * POST /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/panel-users
     */
    public function store(Request $request, string $installationUuid, string $uuid): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $request->validate([
            'name' => 'required|string|max:32',
            'code' => 'required|string|min:4|max:6',
            'type' => 'sometimes|string|in:normal,admin',
        ]);

        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        $existingUsers = $this->hpp->listPanelUsers($installationDevice);
        $normalUsers = collect($existingUsers)->where('type', '!=', 'installer')->where('type', '!=', 'admin');

        if ($normalUsers->count() >= 14 && ($request->input('type', 'normal') === 'normal')) {
            return back()->withErrors([
                'limit' => 'Limite atteinte : 14 utilisateurs normaux maximum sur cette centrale.',
            ]);
        }

        try {
            $this->hpp->createPanelUser($installationDevice, $request->only(['name', 'code', 'type']));
            $installationDevice->setProperty('panel_user_count', count($existingUsers) + 1, 'integer');

            return back()->with('success', 'Utilisateur créé sur la centrale.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la création : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * PUT /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/panel-users/{userId}
     */
    public function update(Request $request, string $installationUuid, string $uuid, string $userId): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $request->validate([
            'name' => 'sometimes|string|max:32',
            'code' => 'sometimes|string|min:4|max:6',
        ]);

        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        try {
            $this->hpp->updatePanelUser($installationDevice, $userId, $request->only(['name', 'code']));
            return back()->with('success', 'Utilisateur modifié.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la modification : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * DELETE /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/panel-users/{userId}
     */
    public function destroy(Request $request, string $installationUuid, string $uuid, string $userId): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        try {
            $this->hpp->deletePanelUser($installationDevice, $userId);

            $existingUsers = $this->hpp->listPanelUsers($installationDevice);
            $installationDevice->setProperty('panel_user_count', count($existingUsers), 'integer');

            return back()->with('success', 'Utilisateur supprimé de la centrale.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ]);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function resolveClientInstallationDevice(Request $request, string $installationUuid, string $uuid): InstallationDevice
    {
        $user = $request->user();
        $client = $user->client;

        $installation = Installation::where('uuid', $installationUuid)
            ->where('client_uuid', $client->uuid)
            ->firstOrFail();

        return InstallationDevice::alarmPanels()
            ->where('uuid', $uuid)
            ->whereHas('task', function ($q) use ($installation) {
                $q->where('taskable_id', $installation->id)
                  ->where('taskable_type', Installation::class);
            })
            ->with(['device', 'task.taskable'])
            ->firstOrFail();
    }

    private function authorizeAdministrator(Request $request): void
    {
        if ($request->user()->role !== \App\Enums\UserRole::ADMINISTRATOR->value) {
            abort(403, 'Seul un administrateur peut gérer les utilisateurs du panel.');
        }
    }
}

