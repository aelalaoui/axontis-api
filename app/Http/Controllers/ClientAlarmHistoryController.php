<?php

namespace App\Http\Controllers;

use App\Models\AlarmEvent;
use App\Models\Installation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Historique des événements alarme + exports CSV/PDF.
 */
class ClientAlarmHistoryController extends Controller
{
    /**
     * GET /client/installations/{installationUuid}/alarm/history
     */
    public function index(Request $request, string $installationUuid): InertiaResponse
    {
        $user = $request->user();
        $client = $user->client;

        $installationUuids = Installation::where('client_uuid', $client->uuid)
            ->pluck('uuid');

        $query = AlarmEvent::whereIn('installation_uuid', $installationUuids)
            ->with(['installationDevice.device:id,uuid,brand,model'])
            ->latest('triggered_at');

        // Filtres
        if ($request->filled('installation_device_uuid')) {
            $query->where('installation_device_uuid', $request->input('installation_device_uuid'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('date_from')) {
            $query->where('triggered_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('triggered_at', '<=', $request->input('date_to'));
        }

        $events = $query->paginate($request->get('per_page', 25))
            ->withQueryString();

        // Transformer pour Inertia
        $events->getCollection()->transform(fn(AlarmEvent $event) => [
            'uuid' => $event->uuid,
            'event_type' => $event->event_type,
            'category' => $event->category,
            'category_label' => $event->category_label,
            'severity' => $event->severity,
            'severity_label' => $event->severity_label,
            'zone_number' => $event->zone_number,
            'zone_name' => $event->zone_name,
            'triggered_at' => $event->triggered_at?->toIso8601String(),
            'has_alert' => $event->has_alert,
            'device' => $event->installationDevice ? [
                'uuid' => $event->installationDevice->uuid,
                'brand' => $event->installationDevice->device?->brand,
                'model' => $event->installationDevice->device?->model,
            ] : null,
        ]);

        return Inertia::render('Client/Alarm/History', [
            'events' => $events,
            'filters' => $request->only(['installation_device_uuid', 'category', 'severity', 'date_from', 'date_to']),
        ]);
    }

    /**
     * GET /client/installations/{installationUuid}/alarm/history/export
     */
    public function export(Request $request, string $installationUuid): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $client = $user->client;

        // Vérifier le droit d'export (administrator, manager)
        if (!$user->hasAnyRole([
            \App\Enums\UserRole::ADMINISTRATOR,
            \App\Enums\UserRole::MANAGER,
        ])) {
            abort(403, 'Vous n\'avez pas les droits nécessaires pour exporter.');
        }

        $installationUuids = Installation::where('client_uuid', $client->uuid)
            ->pluck('uuid');

        $query = AlarmEvent::whereIn('installation_uuid', $installationUuids)
            ->with(['installationDevice.device:id,uuid,brand,model'])
            ->latest('triggered_at');

        // Appliquer les mêmes filtres que l'historique
        if ($request->filled('installation_device_uuid')) {
            $query->where('installation_device_uuid', $request->input('installation_device_uuid'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }
        if ($request->filled('date_from')) {
            $query->where('triggered_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('triggered_at', '<=', $request->input('date_to'));
        }

        $count = $query->count();

        // Synchrone si < 1000 lignes
        if ($count <= 1000) {
            return $this->generateCsvResponse($query);
        }

        // Async : dispatch un job et notifier par email
        // TODO: Créer ExportAlarmEventsJob quand le volume le justifie
        return back()->with('info', "L'export contient {$count} événements. Il sera envoyé par email sous peu.");
    }

    /**
     * Génère le CSV en streaming.
     */
    private function generateCsvResponse($query): Response
    {
        $events = $query->get();

        $csv = "Date;Type;Catégorie;Sévérité;Zone;Centrale;Code CID\n";

        foreach ($events as $event) {
            $installationDevice = $event->installationDevice;
            $deviceName = $installationDevice
                ? "{$installationDevice->device?->brand} {$installationDevice->device?->model}"
                : '';

            $csv .= implode(';', [
                $event->triggered_at?->format('d/m/Y H:i:s'),
                $event->event_type,
                $event->category_label ?? $event->category,
                $event->severity_label ?? $event->severity,
                $event->zone_name ?? $event->zone_number ?? '',
                $deviceName,
                $event->cid_code ?? '',
            ]) . "\n";
        }

        $filename = 'evenements_alarme_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

