<?php

namespace App\Services;

use App\Helpers\MoneyHelper;
use App\Models\Client;
use App\Models\Contract;
use App\Models\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContractService
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Generate a contract for a client
     */
    public function generateContract(Client $client): Contract
    {
        // 1. Create a Contract record if it doesn't exist or create a new one
        // For now, we'll create a new pending contract based on client data
        // You might want to adjust this logic based on your specific requirements (e.g. from an offer)

        $contract = Contract::create([
            'client_id' => $client->id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            // These would normally come from the accepted offer/quote
            'monthly_amount_cents' => 0,
            'vat_rate_percentage' => 20,
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
