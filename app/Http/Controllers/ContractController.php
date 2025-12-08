<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
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
            $client = Client::where('uuid', $uuid)->firstOrFail();

            if ($client->status !== \App\Enums\ClientStatus::INSTALLATION_STEP) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 401);
            }

            $contract = $this->contractService->generateContract($client);

            return response()->json([
                'success' => true,
                'message' => 'Contrat généré avec succès',
                'data' => [
                    'contract' => $contract
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
}
