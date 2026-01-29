<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientInstallationController extends Controller
{
    /**
     * Display a listing of the client's installations.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get the client associated with the authenticated user
        $client = Client::where('email', $user->email)->firstOrFail();

        // Get all installations for this client with related data
        $installations = Installation::where('client_uuid', $client->uuid)
            ->with(['devices', 'contract'])
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($installation) {
                return [
                    'uuid' => $installation->uuid,
                    'type' => $installation->type,
                    'address' => $installation->address,
                    'city_fr' => $installation->city_fr,
                    'city_ar' => $installation->city_ar,
                    'city_en' => $installation->city_en,
                    'country' => $installation->country,
                    'scheduled_date' => $installation->scheduled_date?->format('Y-m-d'),
                    'scheduled_time' => $installation->scheduled_time?->format('H:i'),
                    'devices' => $installation->devices->map(function ($device) {
                        return [
                            'id' => $device->id,
                            'uuid' => $device->uuid ?? null,
                            'name' => $device->name ?? null,
                            'type' => $device->type ?? 'device',
                            'model' => $device->model ?? null,
                            'serial_number' => $device->serial_number ?? null,
                            'location' => $device->location ?? null,
                        ];
                    }),
                    'contract' => $installation->contract ? [
                        'uuid' => $installation->contract->uuid,
                        'reference' => $installation->contract->reference ?? null,
                        'status' => $installation->contract->status ?? null,
                    ] : null,
                ];
            });

        return Inertia::render('Client/Installations/Index', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
            ],
            'installations' => $installations,
        ]);
    }

    /**
     * Display the specified installation.
     */
    public function show(Request $request, string $uuid)
    {
        $user = $request->user();

        // Get the client associated with the authenticated user
        $client = Client::where('email', $user->email)->firstOrFail();

        // Get the installation and verify it belongs to this client
        $installation = Installation::where('uuid', $uuid)
            ->where('client_uuid', $client->uuid)
            ->with(['devices', 'contract'])
            ->firstOrFail();

        return Inertia::render('Client/Installations/[uuid]', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
            ],
            'installation' => [
                'uuid' => $installation->uuid,
                'type' => $installation->type,
                'address' => $installation->address,
                'city_fr' => $installation->city_fr,
                'city_ar' => $installation->city_ar,
                'city_en' => $installation->city_en,
                'country' => $installation->country,
                'scheduled_date' => $installation->scheduled_date?->format('Y-m-d'),
                'scheduled_time' => $installation->scheduled_time?->format('H:i'),
                'devices' => $installation->devices->map(function ($device) {
                    return [
                        'id' => $device->id,
                        'uuid' => $device->uuid ?? null,
                        'name' => $device->name ?? null,
                        'type' => $device->type ?? 'device',
                        'model' => $device->model ?? null,
                        'serial_number' => $device->serial_number ?? null,
                        'location' => $device->location ?? null,
                    ];
                }),
                'contract' => $installation->contract ? [
                    'uuid' => $installation->contract->uuid,
                    'reference' => $installation->contract->reference ?? null,
                    'status' => $installation->contract->status ?? null,
                    'description' => $installation->contract->description ?? null,
                ] : null,
            ],
        ]);
    }
}

