<?php

namespace App\Http\Middleware;

use App\Enums\ClientStatus;
use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientIsActive
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user has an associated client with active status.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()
                ->route('login');
        }

        // Find client associated with this user
        $client = Client::query()->where('user_id', $user->id)->first();

        if (!$client) {
            return redirect()
                ->route('login')
                ->with('error', 'Aucun compte client associé.');
        }

        // Check if client has active status
        $activeStatuses = [
            ClientStatus::ACTIVE->value,
            ClientStatus::FORMAL_NOTICE->value,
            ClientStatus::DISABLED->value,
        ];

        if (!in_array($client->status->value, $activeStatuses)) {
            return redirect()
                ->route('login')
                ->with('error', 'Votre compte client n\'est pas actif.');
        }

        if ($client->status->value === ClientStatus::CLOSED->value) {
            return redirect()
                ->route('login')
                ->with('error', 'Votre compte client est clôturé. merci de contacter le support technique.');
        }

        // Share client data with the request for use in controllers
        $request->merge(['client' => $client]);

        return $next($request);
    }
}

