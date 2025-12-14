<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    protected $contractService;
    protected $docuSignService;

    public function __construct(ContractService $contractService, \App\Services\DocuSignService $docuSignService)
    {
        $this->contractService = $contractService;
        $this->docuSignService = $docuSignService;
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

            // 1. Generate Contract and save PDF
            $contract = $this->contractService->generateContract($client);

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
                $returnUrl
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
}
