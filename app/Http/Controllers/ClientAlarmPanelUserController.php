<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Installation;
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
     * GET /client/alarm/devices/{uuid}/panel-users
     */
    public function index(Request $request, string $uuid): InertiaResponse
    {
        $device = $this->resolveClientDevice($request, $uuid);

        $users = $this->hpp->listPanelUsers($device);

        return Inertia::render('Client/Alarm/PanelUsers', [
            'device' => [
                'uuid' => $device->uuid,
                'brand' => $device->brand,
                'model' => $device->model,
                'serial_number' => $device->getPanelSerialNumber(),
            ],
            'panelUsers' => $users,
            'maxUsers' => 14, // Limite hardware DS-PWA64-L-WB (hors installateur et admin)
        ]);
    }

    /**
     * POST /client/alarm/devices/{uuid}/panel-users
     */
    public function store(Request $request, string $uuid): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $request->validate([
            'name' => 'required|string|max:32',
            'code' => 'required|string|min:4|max:6',
            'type' => 'sometimes|string|in:normal,admin',
        ]);

        $device = $this->resolveClientDevice($request, $uuid);

        // Vérifier la limite de 14 utilisateurs normaux
        $existingUsers = $this->hpp->listPanelUsers($device);
        $normalUsers = collect($existingUsers)->where('type', '!=', 'installer')->where('type', '!=', 'admin');

        if ($normalUsers->count() >= 14 && ($request->input('type', 'normal') === 'normal')) {
            return back()->withErrors([
                'limit' => 'Limite atteinte : 14 utilisateurs normaux maximum sur cette centrale.',
            ]);
        }

        try {
            $this->hpp->createPanelUser($device, $request->only(['name', 'code', 'type']));

            // Mettre à jour le compteur
            $device->setProperty('panel_user_count', count($existingUsers) + 1, 'integer');

            return back()->with('success', 'Utilisateur créé sur la centrale.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la création : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * PUT /client/alarm/devices/{uuid}/panel-users/{userId}
     */
    public function update(Request $request, string $uuid, string $userId): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $request->validate([
            'name' => 'sometimes|string|max:32',
            'code' => 'sometimes|string|min:4|max:6',
        ]);

        $device = $this->resolveClientDevice($request, $uuid);

        try {
            $this->hpp->updatePanelUser($device, $userId, $request->only(['name', 'code']));
            return back()->with('success', 'Utilisateur modifié.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la modification : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * DELETE /client/alarm/devices/{uuid}/panel-users/{userId}
     */
    public function destroy(Request $request, string $uuid, string $userId): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdministrator($request);

        $device = $this->resolveClientDevice($request, $uuid);

        try {
            $this->hpp->deletePanelUser($device, $userId);

            $existingUsers = $this->hpp->listPanelUsers($device);
            $device->setProperty('panel_user_count', count($existingUsers), 'integer');

            return back()->with('success', 'Utilisateur supprimé de la centrale.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'api' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ]);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function resolveClientDevice(Request $request, string $uuid): Device
    {
        $user = $request->user();
        $client = $user->client;

        $installationUuids = Installation::where('client_uuid', $client->uuid)
            ->pluck('uuid');

        return Device::alarmPanels()
            ->where('uuid', $uuid)
            ->whereIn('installation_uuid', $installationUuids)
            ->firstOrFail();
    }

    private function authorizeAdministrator(Request $request): void
    {
        $user = $request->user();

        if ($user->role !== \App\Enums\UserRole::ADMINISTRATOR->value) {
            abort(403, 'Seul un administrateur peut gérer les utilisateurs du panel.');
        }
    }
}

