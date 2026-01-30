<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\File;
use App\Services\ClientService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    protected $contractService;
    protected $docuSignService;
    protected $clientService;

    public function __construct(
        ContractService $contractService,
        \App\Services\DocuSignService $docuSignService,
        ClientService $clientService
    )
    {
        $this->contractService = $contractService;
        $this->docuSignService = $docuSignService;
        $this->clientService = $clientService;
    }

    /**
     * Display a listing of contracts for the authenticated client
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            $client = $request->user()->client;

            if (is_null($client)) {
                return redirect()
                    ->route('client.create-account')
                    ->with('error', 'Aucun client associé à ce compte');
            }

            $contracts = $client->contracts()
                ->with(['files', 'signatures'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($contract) {
                    return [
                        'uuid' => $contract->uuid,
                        'status' => $contract->status,
                        'start_date' => $contract->start_date?->format('Y-m-d'),
                        'due_date' => $contract->due_date,
                        'termination_date' => $contract->termination_date?->format('Y-m-d'),
                        'description' => $contract->description,
                        'monthly_ttc' => $contract->monthly_ttc,
                        'subscription_ttc' => $contract->subscription_ttc,
                        'currency' => $contract->currency,
                        'is_active' => $contract->is_active,
                        'is_terminated' => $contract->is_terminated,
                        'files_count' => $contract->files->count(),
                        'has_signature' => $contract->signatures->isNotEmpty(),
                        'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return inertia('Client/Contract/Index', [
                'contracts' => $contracts,
                'client' => [
                    'uuid' => $client->uuid,
                    'name' => $client->company ?? $client->first_name . ' ' . $client->last_name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Contract listing failed: ' . $e->getMessage());
            return redirect()->route('client.home')
                ->with('error', 'Erreur lors de la récupération des contrats');
        }
    }

    /**
     * Display the specified contract
     *
     * @param Request $request
     * @param string $uuid
     * @return \Inertia\Response
     */
    public function show(Request $request, $uuid)
    {
        try {
            $client = $request->user()->client;

            if (!$client) {
                return redirect()->route('client.create-account')
                    ->with('error', 'Aucun client associé à ce compte');
            }

            $contract = $client->contracts()
                ->with(['files', 'signatures', 'installations', 'payments'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return inertia('Client/Contract/[uuid]', [
                'contract' => [
                    'uuid' => $contract->uuid,
                    'status' => $contract->status,
                    'start_date' => $contract->start_date?->format('Y-m-d'),
                    'due_date' => $contract->due_date,
                    'termination_date' => $contract->termination_date?->format('Y-m-d'),
                    'description' => $contract->description,
                    'monthly_ht' => $contract->monthly_ht,
                    'monthly_tva' => $contract->monthly_tva,
                    'monthly_ttc' => $contract->monthly_ttc,
                    'subscription_ht' => $contract->subscription_ht,
                    'subscription_tva' => $contract->subscription_tva,
                    'subscription_ttc' => $contract->subscription_ttc,
                    'vat_rate_percentage' => $contract->vat_rate_percentage,
                    'currency' => $contract->currency,
                    'is_active' => $contract->is_active,
                    'is_terminated' => $contract->is_terminated,
                    'total_paid' => $contract->total_paid,
                    'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
                    /** @var File $file */
                    'files' => $contract->files->map(function ($file) {
                        return [
                            'uuid' => $file->uuid,
                            'name' => $file->name,
                            'type' => $file->type,
                            'url' => $file->url,
                            'download_url' => $file->getDownloadUrlAttribute(),
                            'view_url' => $file->getViewUrlAttribute(),
                            'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'signatures' => $contract->signatures->map(function ($signature) {
                        return [
                            'uuid' => $signature->uuid,
                            'status' => $signature->status,
                            'signed_at' => $signature->signed_at?->format('Y-m-d H:i:s'),
                            'created_at' => $signature->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'installations' => $contract->installations->map(function ($installation) {
                        return [
                            'uuid' => $installation->uuid,
                            'type' => $installation->type,
                            'scheduled_at' => $installation->scheduled_at?->format('Y-m-d H:i:s'),
                            'status' => $installation->status,
                        ];
                    }),
                    'payments' => $contract->payments->map(function ($payment) {
                        return [
                            'uuid' => $payment->uuid,
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                            'status' => $payment->status,
                            'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                ],
                'client' => [
                    'uuid' => $client->uuid,
                    'name' => $client->company ?? $client->first_name . ' ' . $client->last_name,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('client.contracts.index')
                ->with('error', 'Contrat non trouvé');
        } catch (\Exception $e) {
            Log::error('Contract display failed: ' . $e->getMessage());
            return redirect()
                ->route('client.contracts.index')
                ->with('error', 'Erreur lors de la récupération du contrat');
        }
    }

    /**
     * Generate a contract for a client
     *
     * @param Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request, $uuid)
    {
        // ...existing code...
    }

    /**
     * Display a listing of ALL contracts for CRM (Admin/Manager/Operator view)
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function crmIndex(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $status = $request->query('status', '');
            $sort = $request->query('sort', 'created_at');
            $direction = $request->query('direction', 'desc');

            $query = Contract::with(['client', 'installations'])
                ->where('deleted_at', null);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('company', 'like', "%$search%")
                                ->orWhere('first_name', 'like', "%$search%")
                                ->orWhere('last_name', 'like', "%$search%");
                        });
                });
            }

            // Apply status filter
            if ($status === 'terminated') {
                $query->whereNotNull('termination_date');
            } elseif ($status) {
                $query->where('status', $status);
            }

            // Apply sorting
            $query->orderBy($sort, $direction);

            $contracts = $query->paginate(15)
                ->appends(request()->query());

            return inertia('CRM/Contracts/Index', [
                'contracts' => $contracts->map(function ($contract) {
                    return [
                        'uuid' => $contract->uuid,
                        'description' => $contract->description,
                        'client_name' => $contract->client->company ?? $contract->client->first_name . ' ' . $contract->client->last_name,
                        'client_uuid' => $contract->client->uuid,
                        'status' => $contract->status,
                        'start_date' => $contract->start_date?->format('Y-m-d'),
                        'due_date' => $contract->due_date,
                        'termination_date' => $contract->termination_date?->format('Y-m-d'),
                        'monthly_ttc' => $contract->monthly_ttc,
                        'currency' => $contract->currency,
                        'is_active' => $contract->is_active,
                        'is_terminated' => $contract->is_terminated,
                        'installations_count' => $contract->installations->count(),
                        'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'sort' => $sort,
                    'direction' => $direction,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Contract CRM listing failed: ' . $e->getMessage());
            return redirect()->route('crm.dashboard')
                ->with('error', 'Erreur lors de la récupération des contrats');
        }
    }

    /**
     * Display the specified contract for CRM view
     *
     * @param string $uuid
     * @return \Inertia\Response
     */
    public function crmShow($uuid)
    {
        try {
            $contract = Contract::where('uuid', $uuid)
                ->with(['client', 'installations', 'files', 'signatures', 'payments'])
                ->firstOrFail();

            return inertia('CRM/Contracts/Show', [
                'contract' => [
                    'uuid' => $contract->uuid,
                    'description' => $contract->description,
                    'status' => $contract->status,
                    'start_date' => $contract->start_date?->format('Y-m-d'),
                    'due_date' => $contract->due_date,
                    'termination_date' => $contract->termination_date?->format('Y-m-d'),
                    'monthly_ht' => $contract->monthly_ht,
                    'monthly_tva' => $contract->monthly_tva,
                    'monthly_ttc' => $contract->monthly_ttc,
                    'subscription_ht' => $contract->subscription_ht,
                    'subscription_tva' => $contract->subscription_tva,
                    'subscription_ttc' => $contract->subscription_ttc,
                    'vat_rate_percentage' => $contract->vat_rate_percentage,
                    'currency' => $contract->currency,
                    'is_active' => $contract->is_active,
                    'is_terminated' => $contract->is_terminated,
                    'total_paid' => $contract->total_paid,
                    'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $contract->updated_at->format('Y-m-d H:i:s'),
                    'client' => [
                        'uuid' => $contract->client->uuid,
                        'name' => $contract->client->company ?? $contract->client->first_name . ' ' . $contract->client->last_name,
                        'email' => $contract->client->email,
                        'phone' => $contract->client->phone,
                        'address' => $contract->client->address,
                        'city' => $contract->client->city,
                        'postal_code' => $contract->client->postal_code,
                    ],
                    'installations' => $contract->installations->map(function ($installation) {
                        return [
                            'uuid' => $installation->uuid,
                            'address' => $installation->address,
                            'country' => $installation->country,
                            'type' => $installation->type,
                            'scheduled_date' => $installation->scheduled_date?->format('Y-m-d'),
                            'scheduled_time' => $installation->scheduled_time,
                        ];
                    }),
                    'files' => $contract->files->map(function ($file) {
                        return [
                            'uuid' => $file->uuid,
                            'name' => $file->name,
                            'type' => $file->type,
                            'url' => $file->url,
                            'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'signatures' => $contract->signatures->map(function ($signature) {
                        return [
                            'uuid' => $signature->uuid,
                            'status' => $signature->status,
                            'signed_at' => $signature->signed_at?->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'payments' => $contract->payments->map(function ($payment) {
                        return [
                            'uuid' => $payment->uuid,
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                            'status' => $payment->status,
                            'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('crm.contracts.index')
                ->with('error', 'Contrat non trouvé');
        } catch (\Exception $e) {
            Log::error('Contract CRM display failed: ' . $e->getMessage());
            return redirect()
                ->route('crm.contracts.index')
                ->with('error', 'Erreur lors de la récupération du contrat');
        }
    }

    /**
     * Show the form for editing a contract
     *
     * @param string $uuid
     * @return \Inertia\Response
     */
    public function edit($uuid)
    {
        try {
            $contract = Contract::where('uuid', $uuid)->firstOrFail();

            return inertia('CRM/Contracts/Edit', [
                'contract' => [
                    'uuid' => $contract->uuid,
                    'description' => $contract->description,
                    'start_date' => $contract->start_date?->format('Y-m-d'),
                    'due_date' => $contract->due_date,
                    'monthly_amount_cents' => $contract->monthly_amount_cents,
                    'subscription_price_cents' => $contract->subscription_price_cents,
                    'vat_rate_percentage' => $contract->vat_rate_percentage,
                    'currency' => $contract->currency,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('crm.contracts.index')
                ->with('error', 'Contrat non trouvé');
        } catch (\Exception $e) {
            Log::error('Contract edit failed: ' . $e->getMessage());
            return redirect()
                ->route('crm.contracts.index')
                ->with('error', 'Erreur lors de l\'accès au formulaire d\'édition');
        }
    }

    /**
     * Update the specified contract
     *
     * @param Request $request
     * @param string $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $uuid)
    {
        try {
            $contract = Contract::where('uuid', $uuid)->firstOrFail();

            $validated = $request->validate([
                'description' => 'required|string|max:1000',
                'start_date' => 'required|date',
                'due_date' => 'nullable|date',
                'monthly_amount_cents' => 'required|integer|min:0',
                'subscription_price_cents' => 'required|integer|min:0',
                'vat_rate_percentage' => 'required|integer|min:0|max:100',
                'currency' => 'required|string|max:3',
            ]);

            $contract->update($validated);

            return redirect()
                ->route('crm.contracts.show', $contract->uuid)
                ->with('success', 'Contrat mis à jour avec succès');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('crm.contracts.index')
                ->with('error', 'Contrat non trouvé');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Contract update failed: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du contrat: ' . $e->getMessage());
        }
    }
}
