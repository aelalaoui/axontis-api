<?php

namespace App\Http\Controllers;

use App\Enums\ClientStep;
use App\Models\Client;
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
        try {
            $client = Client::fromUuid($uuid);

            if ($client->step !== ClientStep::SIGNATURE_STEP) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 401);
            }

            // Get pricing from stored offer data (saved during calculateOffer step)
            $storedOfferData = $this->clientService->getStoredOfferData($client);

            $monthlyAmountCents = 0;
            $subscriptionPriceCents = 0;
            $currency = 'MAD';
            $parentProduct = null;

            if ($storedOfferData) {
                // Use stored offer data directly - values are already in cents
                $monthlyAmountCents = (int) ($storedOfferData['monthly_amount_cents'] ?? 0);
                $subscriptionPriceCents = (int) ($storedOfferData['subscription_amount_cents'] ?? 0);
                $currency = $storedOfferData['currency'] ?? 'MAD';

                // Resolve the parent product from stored offer data
                $parentProduct = $this->clientService->findParentProduct(
                    $storedOfferData['parent_property'],
                    $storedOfferData['parent_value']
                );
            } else {
                // Fallback: try to recalculate from stored parent product info
                $parentProperty = $client->getProperty('offer_parent_property');
                $parentValue = $client->getProperty('offer_parent_value');

                if ($parentProperty && $parentValue) {
                    $parentProduct = $this->clientService->findParentProduct($parentProperty, $parentValue);

                    if ($parentProduct) {
                        $offerData = $this->clientService->calculateOfferPrices($client, $parentProduct);
                        $monthlyAmountCents = (int) ($offerData['pricing']['monthly_amount_cents'] ?? 0);
                        $subscriptionPriceCents = (int) ($offerData['pricing']['subscription_amount_cents'] ?? 0);
                        $currency = $offerData['pricing']['currency'] ?? 'MAD';
                    }
                }
            }

            // 1. Generate Contract and save PDF with calculated prices
            $contract = $this->contractService->generateContract(
                $client,
                $monthlyAmountCents,
                $subscriptionPriceCents,
                $currency
            );

            // 2. Link the parent product to the contract
            if ($parentProduct) {
                $contract->update(['product_uuid' => $parentProduct->id]);
            }

            // 2. Get PDF Content
            // Assuming the contract generation attaches exactly one file which is the contract
            $contractFile = $contract->files->first();

            if (!$contractFile) {
                throw new \Exception("Le fichier du contrat n'a pas été généré.");
            }

            // Retrieve content - if remote, might need to download using url or temporaryUrl
            // FileService logic implies we can use storage to get contents
            try {
                $pdfContent = $contractFile->getContents();
            } catch (\Exception $e) {
                // Fallback for some cloud storages if direct local access isn't setup same way
                // But getContents in File model delegates to Storage::disk()->get() which should work for R2 too
                $pdfContent = file_get_contents($contractFile->url);
            }

            // 3. Generate Signing URL via DocuSign
            $clientName = $client->company ?? $client->first_name . ' ' . $client->last_name;
            $returnUrl = 'https://axontis.com/contract/signed'; // Callback URL for frontend

            $signingUrl = $this->docuSignService->sendEnvelopeForEmbeddedSigning(
                $pdfContent,
                $clientName,
                $client->email,
                $client->uuid,
                $returnUrl,
                $contract
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat généré et prêt à être signé',
                'data' => [
                    'contract' => $contract,
                    'signing_url' => $signingUrl
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Contract generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du contrat: ' . $e->getMessage()
            ], 500);
        }
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

            $query = Contract::with(['client', 'installations']);

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
                'contracts' => $contracts->through(function ($contract) {
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
                ->with([
                    'client.properties',
                    'installations',
                    'files',
                    'signatures',
                    'payments',
                    'product.children.device',
                ])
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
                    'product' => $contract->product ? [
                        'id'   => $contract->product->id,
                        'name' => $contract->product->name,
                    ] : null,
                    'sub_products' => $contract->product
                        ? $contract->product->children
                            ->filter(function ($child) use ($contract) {
                                // Only include sub-products that match the client's stored properties
                                // (same logic as ClientService::calculateOfferPrices)
                                if (!$child->property_name) {
                                    return true; // No filter condition → always include
                                }
                                $clientValue = $contract->client->getProperty($child->property_name);
                                return $clientValue !== null && $clientValue == $child->default_value;
                            })
                            ->map(function ($child) {
                                return [
                                    'id'            => $child->id,
                                    'name'          => $child->name,
                                    'property_name' => $child->property_name,
                                    'default_value' => $child->default_value,
                                    'device'        => $child->device ? [
                                        'id'        => $child->device->id,
                                        'uuid'      => $child->device->uuid,
                                        'brand'     => $child->device->brand,
                                        'model'     => $child->device->model,
                                        'category'  => $child->device->category,
                                        'stock_qty' => $child->device->stock_qty,
                                        'full_name' => $child->device->full_name,
                                    ] : null,
                                ];
                            })->values()->all()
                        : [],
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
