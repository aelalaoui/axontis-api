<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\ClientStep;
use App\Enums\ContractStatus;
use App\Enums\InstallationType;
use App\Enums\UserRole;
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
    public function createAccount(string $clientUuid, string $contractUuid): Response|RedirectResponse
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

        // Check if user already exists for this client
        if ($client->user_id) {
            return redirect()->route('login')->with('message', 'Un compte existe déjà pour ce client.');
        }

        // Link user to client
        $client->update([
            'step' => ClientStep::PASSWORD_STEP->value,
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
            'contractUuid' => $contractUuid,
        ]);
    }

    /**
     * Store the new user account and convert client to user
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|string|exists:clients,uuid',
            'contract_uuid' => 'required|string|exists:contracts,uuid',
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

            /** @var Contract $contract */
            $contract = Contract::fromUuid($request->contract_uuid);

            if (is_null($contract)) {
                return back()->withErrors(['contract_uuid' => 'Contrat introuvable.']);
            }

            $contract->update(['status' => ContractStatus::PENDING->value]);

            // Check if user already exists for this email
            $existingUser = User::query()->where('email', $client->email)->first();

            if ($existingUser) {
                // Link existing user to client
                $client->update(['user_id' => $existingUser->id]);

                // S'assurer que l'utilisateur existant a le rôle CLIENT
                if (!$existingUser->isClient()) {
                    $existingUser->update(['role' => UserRole::CLIENT]);
                }

                Auth::login($existingUser);
                return redirect()->route('client.home');
            }

            // Check if client already has a user
            if ($client->user_id) {
                Auth::loginUsingId($client->user_id);
                return redirect()->route('client.home');
            }

            // Create new user with CLIENT role
            $user = User::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $client->full_name,
                'email' => $client->email,
                'password' => Hash::make($request->password),
                'role' => UserRole::CLIENT, // Assigner le rôle CLIENT par défaut
            ]);

            // Link user to client
            $client->update([
                'user_id' => $user->id,
                'status' => ClientStatus::ACTIVE->value,
                'step' => ClientStep::SCHEDULE_STEP->value,
            ]);

            // Login the user
            Auth::login($user);

            // Return redirect response with contract UUID for installation scheduling
            return redirect()->route('client.home')->with([
                'contract_uuid' => $contract->uuid,
                'message' => 'Compte créé avec succès. Configurez votre installation.'
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Une erreur est survenue lors de la création du compte.']);
        }
    }

    /**
     * Display the client security dashboard.
     * Protected by client.active middleware ensuring only clients with active status can access.
     */
    public function home(Request $request): Response
    {
        /** @var Client $client */
        $client = $request->get('client');

        // Load client relationships
        $client->load(['contracts']);

        return Inertia::render('Client/Home', [
            'client' => [
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'country' => $client->country,
                'status' => $client->status->value,
                'step' => $client->step->value,
            ],
            'contracts' => $client->contracts->map(function ($contract) {
                $installation = $contract->installations()
                    ->where('type', InstallationType::FIRST_INSTALLATION->value)
                    ->first();

                return [
                    'uuid' => $contract->uuid,
                    'description' => $contract->description,
                    'status' => $contract->status,
                    'monthly_ttc' => $contract->monthly_ttc,
                    'created_at' => $contract->created_at->format('d/m/Y'),
                    'installation' => $installation->uuid ?? null,
                    'scheduled_date' => $installation?->scheduled_date ? $installation->scheduled_date->format('Y-m-d') : null,
                    'scheduled_time' => $installation?->scheduled_time ? $installation->scheduled_time->format('H:i') : null,
                ];
            }),
        ]);
    }

    /**
     * Display a listing of all clients.
     */
    public function index(Request $request): Response
    {
        $query = Client::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Sort functionality
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $clients = $query->paginate(15)->withQueryString();

        return Inertia::render('CRM/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Display the specified client with all related contracts and installations.
     */
    public function show(string $uuid): Response
    {
        /** @var Client $client */
        $client = Client::fromUuid($uuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        // Load relationships
        $client->load(['contracts', 'installations']);

        return Inertia::render('CRM/Clients/Show', [
            'client' => [
                'uuid' => $client->uuid,
                'type' => $client->type,
                'company_name' => $client->company_name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'country' => $client->country,
                'status' => $client->status->value,
                'step' => $client->step->value,
                'created_at' => $client->created_at->toIso8601String(),
                'updated_at' => $client->updated_at->toIso8601String(),
            ],
            'contracts' => $client->contracts->map(function ($contract) {
                return [
                    'uuid' => $contract->uuid,
                    'description' => $contract->description,
                    'status' => $contract->status,
                    'monthly_ht' => $contract->monthly_ht,
                    'monthly_tva' => $contract->monthly_tva,
                    'monthly_ttc' => $contract->monthly_ttc,
                    'subscription_ht' => $contract->subscription_ht,
                    'subscription_tva' => $contract->subscription_tva,
                    'subscription_ttc' => $contract->subscription_ttc,
                    'currency' => $contract->currency,
                    'created_at' => $contract->created_at->toIso8601String(),
                ];
            })->toArray(),
            'installations' => $client->installations->map(function ($installation) {
                return [
                    'uuid' => $installation->uuid,
                    'type' => $installation->type,
                    'address' => $installation->address,
                    'city' => $installation->city,
                    'postal_code' => $installation->postal_code,
                    'country' => $installation->country,
                    'scheduled_date' => $installation->scheduled_date?->toDateString(),
                    'scheduled_time' => $installation->scheduled_time?->format('H:i'),
                    'status' => $installation->status,
                    'created_at' => $installation->created_at->toIso8601String(),
                ];
            })->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(string $uuid): Response
    {
        /** @var Client $client */
        $client = Client::fromUuid($uuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        return Inertia::render('CRM/Clients/Edit', [
            'client' => [
                'uuid' => $client->uuid,
                'type' => $client->type,
                'company_name' => $client->company_name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'postal_code' => $client->postal_code,
                'country' => $client->country,
                'status' => $client->status->value,
            ],
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function updateCRM(Request $request, string $uuid): RedirectResponse
    {
        /** @var Client $client */
        $client = Client::fromUuid($uuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:individual,business',
            'company_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $client->update($validator->validated());

            return redirect()->route('crm.clients.show', $client->uuid)
                ->with('success', 'Client updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to update client.']);
        }
    }

    /**
     * Toggle client active/inactive status.
     */
    public function toggleStatus(string $uuid): RedirectResponse
    {
        /** @var Client $client */
        $client = Client::fromUuid($uuid);

        if (is_null($client)) {
            abort(404, 'Client not found');
        }

        try {
            // Toggle between active and disabled statuses
            $newStatus = $client->status->value === ClientStatus::ACTIVE->value
                ? ClientStatus::DISABLED->value
                : ClientStatus::ACTIVE->value;

            $client->update(['status' => $newStatus]);

            $message = $newStatus === ClientStatus::ACTIVE->value
                ? 'Client reactivated successfully.'
                : 'Client status changed to disabled.';

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to update client status.']);
        }
    }
}
