<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Product;
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

    /**
     * Calculate offer prices based on client properties and products
     */
    public function calculateOffer(Request $request, string $uuid, string $property, string $value): JsonResponse
    {
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

            // Trouver le premier produit parent basé sur la propriété et la valeur
            $parentProduct = Product::whereNull('id_parent')
                ->where('property_name', $property)
                ->where('default_value', $value)
                ->first();

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

            // Récupérer tous les sous-produits du produit parent
            $subProducts = Product::where('id_parent', $parentProduct->id)->get();

            // Initialiser les totaux
            $totalCautionPrice = 0;
            $totalSubscriptionPrice = 0;
            $matchedProducts = [];

            // Pour chaque sous-produit, vérifier si le client a la propriété correspondante
            foreach ($subProducts as $subProduct) {
                if ($subProduct->property_name) {
                    // Récupérer la valeur de la propriété du client
                    $clientPropertyValue = $client->getProperty($subProduct->property_name);

                    // Si le client a cette propriété et qu'elle correspond à la valeur par défaut du produit
                    if ($clientPropertyValue !== null && $clientPropertyValue == $subProduct->default_value) {
                        $totalCautionPrice += $subProduct->caution_price ?? 0;
                        $totalSubscriptionPrice += $subProduct->subscription_price ?? 0;

                        $matchedProducts[] = [
                            'product_id' => $subProduct->id,
                            'product_name' => $subProduct->name,
                            'property_name' => $subProduct->property_name,
                            'client_property_value' => $clientPropertyValue,
                            'product_default_value' => $subProduct->default_value,
                            'caution_price' => $subProduct->caution_price ?? 0,
                            'subscription_price' => $subProduct->subscription_price ?? 0
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Offer calculated successfully',
                'data' => [
                    'client_uuid' => $client->uuid,
                    'parent_product' => [
                        'id' => $parentProduct->id,
                        'name' => $parentProduct->name,
                        'property' => $parentProduct->property_name,
                        'value' => $parentProduct->default_value
                    ],
                    'pricing' => [
                        'total_caution_price' => $totalCautionPrice,
                        'total_subscription_price' => $totalSubscriptionPrice,
                        'currency' => 'EUR' // Assuming EUR, adjust as needed
                    ],
                    'matched_products' => $matchedProducts,
                    'matched_products_count' => count($matchedProducts),
                    'total_subproducts_analyzed' => $subProducts->count(),
                    'calculation_timestamp' => now()->toDateTimeString()
                ]
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
