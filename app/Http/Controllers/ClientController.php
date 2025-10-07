<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Create a new client
     */
    public function create(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:clients,email',
            'country' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Créer le nouveau client
            $client = Client::create([
                'email' => $request->email,
                'country' => $request->country,
                'type' => 'unknown',
                'status' => ClientStatus::EMAIL_STEP->value
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update client status/step
     */
    public function updateStep(Request $request, string $uuid, string $step): JsonResponse
    {
        // Validation du statut
        $validator = Validator::make(['step' => $step], [
            'step' => 'required|string|in:' . ClientStatus::validationString(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step provided',
                'errors' => $validator->errors(),
                'valid_steps' => ClientStatus::values()
            ], 422);
        }

        try {
            // Trouver le client par UUID
            $client = Client::where('uuid', $uuid)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // Mettre à jour uniquement le statut
            $client->update(['status' => $step]);

            return response()->json([
                'success' => true,
                'message' => 'Client status updated successfully',
                'data' => [
                    'uuid' => $client->uuid,
                    'previous_status' => $client->getOriginal('status'),
                    'current_status' => $client->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store client criterias as properties
     */
    public function storeCriterias(Request $request, string $uuid): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'criterias' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Trouver le client par UUID
            /** @var Client $client */
            $client = Client::where('uuid', $uuid)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $criterias = $request->input('criterias');

            // Stocker chaque critère comme une propriété
            $storedProperties = [];
            foreach ($criterias as $key => $value) {
                if ($key === 'email') {
                    continue; // Ignorer email
                }
                $propertyName = "{$key}";
                $storedProperty = $client->setProperty($propertyName, $value);
                $storedProperties[] = [
                    'property' => $propertyName,
                    'value' => $value,
                    'type' => $storedProperty->type
                ];
            }

            $client->type = $client->getProperty('customerType', 'unknown');
            $client->save();

            return response()->json([
                'success' => true,
                'message' => 'Criterias stored successfully',
                'data' => [
                    'client_uuid' => $client->uuid,
                    'stored_criterias' => $storedProperties,
                    'criterias_count' => count($storedProperties),
                    'timestamp' => now()->toDateTimeString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store criterias',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
