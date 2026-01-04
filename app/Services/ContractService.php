<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;

class ContractService
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Generate a contract for a client
     *
     * @param Client $client
     * @param int $monthlyAmountCents Monthly subscription amount in cents
     * @param int $subscriptionPriceCents Initial subscription/caution price in cents
     * @param string $currency Currency code (default: MAD)
     * @throws \Exception
     */
    public function generateContract(
        Client $client,
        int $monthlyAmountCents = 0,
        int $subscriptionPriceCents = 0,
        string $currency = 'MAD'
    ): Contract
    {
        // 1. Create a Contract record if it doesn't exist or create a new one
        // For now, we'll create a new pending contract based on client data
        // You might want to adjust this logic based on your specific requirements (e.g. from an offer)

        $contract = Contract::query()->create([
            'client_uuid' => $client->uuid,
            'status' => ContractStatus::CREATED->value,
            'start_date' => now(),
            'monthly_amount_cents' => $monthlyAmountCents,
            'subscription_price_cents' => $subscriptionPriceCents,
            'vat_rate_percentage' => 20,
            'currency' => $currency,
            'description' => 'Contrat de maintenance et télésurveillance'
        ]);

        // 2. Generate PDF content
        $pdf = Pdf::loadView('documents.contract', [
            'client' => $client,
            'contract' => $contract
        ]);

        // 3. Save PDF temporarily
        $fileName = 'contrat_' . $client->uuid . '_' . time() . '.pdf';
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        $pdf->save($tempPath);

        // 4. Create UploadedFile instance from temp file
        $uploadedFile = new UploadedFile(
            $tempPath,
            $fileName,
            'application/pdf',
            null,
            true
        );

        // 5. Upload file using FileService
        $this->fileService->uploadFile(
            $uploadedFile,
            $contract,
            'contract',
            'Contrat - ' . $client->company ?? $client->first_name . ' ' . $client->last_name
        );

        // 6. Clean up temp file (UploadedFile might handle this if moved, but good practice if copy made)
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return $contract->load('files');
    }
}
