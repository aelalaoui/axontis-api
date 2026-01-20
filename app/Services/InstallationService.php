<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Models\Installation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallationService
{
    public function getAllInstallations()
    {
        return Installation::with(['client', 'contract', 'devices'])->get();
    }

    public function createInstallation(array $data)
    {
        return DB::transaction(function () use ($data) {
            $installation = Installation::create(array_merge($data, ['uuid' => Str::uuid()]));

            if (isset($data['city'])) {
                $installation->client()->update(['city' => $data['city']]);
            }

            return $installation;
        });
    }

    public function findInstallationByUuid(string $uuid)
    {
        return Installation::with(['client', 'contract', 'devices'])->where('uuid', $uuid)->first();
    }

    public function updateInstallation(Installation $installation, array $data)
    {
        return DB::transaction(function () use ($installation, $data) {
            $installation->update($data);
            return $installation->refresh();
        });
    }

    public function deleteInstallation(Installation $installation): ?bool
    {
        return $installation->delete();
    }

    /**
     * Schedule installation with date, time and business logic validation
     */
    public function scheduleInstallation(Installation $installation, string $date, string $time, $authenticatedClient): Installation
    {
        return DB::transaction(function () use ($installation, $date, $time, $authenticatedClient) {
            // Verify installation belongs to authenticated client
            if ($installation->client_uuid !== $authenticatedClient->uuid) {
                throw new \Exception('Cette installation ne vous appartient pas.');
            }

            // Validate client and contract status
            $client = $installation->client;
            $contract = $installation->contract;

            if (!$client || $client->status->value !== ClientStatus::ACTIVE->value) {
                throw new \Exception('Votre compte client doit être actif.');
            }

            if (
                is_null($contract)
                || !in_array(
                    $contract->status,
                    [ContractStatus::PENDING->value, ContractStatus::SCHEDULED->value]
                )
            ) {
                throw new \Exception('Le statut du contrat doit être en attente.');
            }

            // Validate date is within allowed range (J+3 to 1 month)
            $scheduledDateTime = new \DateTime($date . ' ' . $time);
            $minDate = (new \DateTime())->add(new \DateInterval('P3D'));
            $maxDate = (new \DateTime())->add(new \DateInterval('P30D'));

            if ($scheduledDateTime < $minDate || $scheduledDateTime > $maxDate) {
                throw new \Exception('La date d\'installation doit être entre J+3 et 1 mois.');
            }

            $installation->update([
                'scheduled_date' => $date,
                'scheduled_time' => $time,
            ]);

            $installation->contract()->update(['status' => 'scheduled']);

            return $installation->refresh();
        });
    }
}
