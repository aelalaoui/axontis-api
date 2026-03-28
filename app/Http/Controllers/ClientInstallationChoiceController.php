<?php

namespace App\Http\Controllers;

use App\Enums\ClientStep;
use App\Models\Installation;
use App\Models\Product;
use App\Models\Task;
use App\Notifications\InstallationChoiceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientInstallationChoiceController extends Controller
{
    /**
     * GET /client/installation-setup
     * Show the installation mode choice page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        /** @var \App\Models\Client $client */
        $client = $request->get('client');

        // If the choice has already been made, redirect home
        if ($client->getProperty('installation_mode')) {
            return redirect()->route('client.home');
        }

        // Load the first pending installation for this client
        $installation = Installation::where('client_uuid', $client->uuid)
            ->with('contract')
            ->whereHas('contract', fn ($q) => $q->whereIn('status', ['pending', 'scheduled', 'paid', 'active']))
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$installation) {
            return redirect()->route('client.home');
        }

        // Read installation fee from sub-product
        $feeProduct = Product::where('property_name', 'installation_mode')
            ->where('default_value', 'technician')
            ->where('name', 'Installation Technicien')
            ->first();

        $installationFee         = ($feeProduct?->caution_price_cents ?? 50000) / 100;
        $installationFeeCurrency = $installation->contract?->currency ?? 'MAD';

        return Inertia::render('Client/Operations/InstallationChoice', [
            'client' => [
                'uuid'      => $client->uuid,
                'full_name' => $client->full_name,
                'email'     => $client->email,
                'address'   => $client->address,
            ],
            'installation' => [
                'uuid'    => $installation->uuid,
                'address' => $installation->address,
                'city'    => $installation->city_fr ?? $installation->city ?? '',
                'country' => $installation->country,
            ],
            'contract' => [
                'uuid'     => $installation->contract?->uuid,
                'currency' => $installationFeeCurrency,
            ],
            'installation_fee'          => $installationFee,
            'installation_fee_currency' => $installationFeeCurrency,
        ]);
    }

    /**
     * POST /client/installation-setup
     * Store the installation mode choice, create a Task, send email.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'installation_uuid'  => 'required|uuid|exists:installations,uuid',
            'installation_mode'  => 'required|in:technician,self',
            'delivery_address'   => 'required_if:installation_mode,self|nullable|string|max:500',
            'same_address'       => 'boolean',
        ]);

        /** @var \App\Models\Client $client */
        $client = $request->get('client');

        $installation = Installation::where('uuid', $validated['installation_uuid'])
            ->where('client_uuid', $client->uuid)
            ->firstOrFail();

        $mode            = $validated['installation_mode'];
        $deliveryAddress = null;

        if ($mode === 'self') {
            $deliveryAddress = $validated['same_address']
                ? $installation->address
                : $validated['delivery_address'];
        }

        // ── Persist client properties ──────────────────────────────────────
        $client->setProperty('installation_mode', $mode);
        if ($deliveryAddress) {
            $client->setProperty('delivery_address', $deliveryAddress);
        }

        // ── Create a Task ──────────────────────────────────────────────────
        $taskNotes = $mode === 'technician'
            ? 'Installation par technicien Axontis – frais payés en ligne.'
            : 'Auto-installation – envoi postal à : ' . $deliveryAddress;

        Task::create([
            'taskable_type'  => Installation::class,
            'taskable_uuid'  => $installation->uuid,
            'address'        => $installation->address ?? '',
            'type'           => 'installation',
            'status'         => 'scheduled',
            'user_id'        => null, // assigned later by an operator
            'scheduled_date' => null,
            'notes'          => $taskNotes,
        ]);

        // ── Update client step ─────────────────────────────────────────────
        $client->update(['step' => ClientStep::COMPLETED_STEPS->value]);

        // ── Send confirmation email ────────────────────────────────────────
        $feeProduct = Product::where('property_name', 'installation_mode')
            ->where('default_value', 'technician')
            ->where('name', 'Installation Technicien')
            ->first();

        $feeAmount = ($feeProduct?->caution_price_cents ?? 50000) / 100;
        $currency  = $installation->contract?->currency ?? 'MAD';

        $client->notify(new InstallationChoiceNotification(
            clientName:            $client->full_name,
            installationMode:      $mode,
            deliveryAddress:       $deliveryAddress,
            installationFeeAmount: $mode === 'technician' ? $feeAmount : null,
            currency:              $currency,
        ));

        // ── Flash message and redirect ─────────────────────────────────────
        $flashMessage = $mode === 'technician'
            ? 'Votre choix a été enregistré. Un technicien vous contactera sous 48h pour confirmer la date d\'intervention.'
            : 'Votre choix a été enregistré. Votre matériel sera livré à l\'adresse indiquée.';

        return redirect()->route('client.home')
            ->with('installation_choice_success', $flashMessage)
            ->with('installation_mode', $mode);
    }
}

