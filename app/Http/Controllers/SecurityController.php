<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Display the client security dashboard.
     */
    public function dashboard(Request $request): Response
    {
        /** @var Client $client */
        $client = $request->get('client');

        // Load client relationships
        $client->load(['contracts', 'installations']);

        return Inertia::render('Security/Dashboard', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'country' => $client->country,
                'status' => $client->status->value,
            ],
            'contracts' => $client->contracts->map(function ($contract) {
                return [
                    'uuid' => $contract->uuid,
                    'description' => $contract->description,
                    'status' => $contract->status,
                    'monthly_ttc' => $contract->monthly_ttc,
                    'created_at' => $contract->created_at->format('d/m/Y'),
                ];
            }),
        ]);
    }
}

