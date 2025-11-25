<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Create a new client
     */
    public function create(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
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
            // Check if client already existed
            $isExisting = $this->clientService->clientExists($request->email);

            // Use service to find or create client
            $client = $this->clientService->findOrCreateClient(
                $request->email,
                $request->country
            );

            return response()->json([
                'success' => true,
                'message' => $isExisting ? 'Client already exists' : 'Client created successfully',
                'data' => $client
            ], $isExisting ? 200 : 201);

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
            // Find client by UUID using service
            $client = $this->clientService->findClientByUuid($uuid);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // Update status using service
            $statusData = $this->clientService->updateClientStatus($client, $step);

            return response()->json([
                'success' => true,
                'message' => 'Client status updated successfully',
                'data' => $statusData
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
            // Find client by UUID using service
            $client = $this->clientService->findClientByUuid($uuid);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // Store criterias using service
            $criteriasData = $this->clientService->storeCriterias(
                $client,
                $request->input('criterias')
            );

            return response()->json([
                'success' => true,
                'message' => 'Criterias stored successfully',
                'data' => $criteriasData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store criterias',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Calculate offer prices based on client properties and products
     */
    public function calculateOffer(Request $request, string $uuid, string $property, string $value): JsonResponse
    {
        try {
            // Find client by UUID using service
            $client = $this->clientService->findClientByUuid($uuid);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // Find parent product using service
            $parentProduct = $this->clientService->findParentProduct($property, $value);

            if (!$parentProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'No parent product found for the specified property and value',
                    'searched_criteria' => [
                        'property' => $property,
                        'value' => $value
                    ]
                ], 404);
            }

            // Calculate offer using service
            $offerData = $this->clientService->calculateOfferPrices($client, $parentProduct);

            return response()->json([
                'success' => true,
                'message' => 'Offer calculated successfully',
                'data' => $offerData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

