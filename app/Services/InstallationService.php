<?php

namespace App\Services;

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

    public function deleteInstallation(Installation $installation)
    {
        return $installation->delete();
    }
}
