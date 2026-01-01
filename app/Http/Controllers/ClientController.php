<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\ClientStep;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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
            'step' => 'required|string|in:' . ClientStep::validationString(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step provided',
                'errors' => $validator->errors(),
                'valid_steps' => ClientStep::values()
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
            $statusData = $this->clientService->updateClientStep($client, $step);

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
     * Update client details
     */
    public function updateDetails(Request $request, string $uuid): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
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

            // Update client details using service
            $updatedClient = $this->clientService->updateClientDetails(
                $client,
                $request->only(['first_name', 'last_name', 'company', 'phone', 'city'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Client details updated successfully',
                'data' => $updatedClient
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client details',
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


    /**
     * Display payment form for a specific contract
     */
    public function payment(string $clientUuid, string $contractUuid): Response
    {
        /** @var Client $client */
        $client = Client::fromUuid($clientUuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        /** @var Contract $contract */
        $contract = Contract::fromUuid($contractUuid);

        if (is_null($contract)) {
            abort(404, 'Contract not found');
        }

        $client->update(['step' => ClientStep::PAYMENT_STEP->value]);

        return Inertia::render('Payment', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
            ],
            'contract' => [
                'uuid' => $contract->uuid,
                'monthly_ht' => $contract->monthly_ht,
                'monthly_tva' => $contract->monthly_tva,
                'monthly_ttc' => $contract->monthly_ttc,
                'subscription_ht' => $contract->subscription_ht,
                'subscription_tva' => $contract->subscription_tva,
                'subscription_ttc' => $contract->subscription_ttc,
                'description' => $contract->description,
                'currency' => $contract->currency,
            ]
        ]);
    }

    /**
     * Display the create account form for a client after payment
     */
    public function createAccount(string $clientUuid): Response|RedirectResponse
    {
        /** @var Client $client */
        $client = Client::fromUuid($clientUuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        // Check if user already exists for this client
        if ($client->user_id) {
            return redirect()->route('login')->with('message', 'Un compte existe déjà pour ce client.');
        }

        // Link user to client
        $client->update([
            'status' => ClientStep::PASSWORD_STEP->value,
        ]);

        return Inertia::render('Client/CreateAccount', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name ?? $client->first_name .' '. $client->last_name,
                'email' => $client->email,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
            ],
            'clientUuid' => $clientUuid,
        ]);
    }

    /**
     * Store the new user account and convert client to user
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|string|exists:clients,uuid',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            /** @var Client $client */
            $client = Client::fromUuid($request->client_uuid);

            if (is_null($client)) {
                return back()->withErrors(['client_uuid' => 'Client introuvable.']);
            }

            // Check if user already exists for this email
            $existingUser = User::query()->where('email', $client->email)->first();

            if ($existingUser) {
                // Link existing user to client
                $client->update(['user_id' => $existingUser->id]);
                Auth::login($existingUser);
                return redirect()->route('security.dashboard');
            }

            // Check if client already has a user
            if ($client->user_id) {
                Auth::loginUsingId($client->user_id);
                return redirect()->route('security.dashboard');
            }

            // Create new user
            $user = User::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $client->full_name,
                'email' => $client->email,
                'password' => Hash::make($request->password),
            ]);

            // Link user to client
            $client->update([
                'user_id' => $user->id,
                'status' => ClientStatus::ACTIVE->value,
                'step' => ClientStep::SCHEDULE_STEP->value,
            ]);

            // Login the user
            Auth::login($user);

            return redirect()->route('security.dashboard');

        } catch (\Exception $e) {
            return back()->withErrors(['client_uuid' => 'Une erreur est survenue lors de la création du compte.']);
        }
    }
}
