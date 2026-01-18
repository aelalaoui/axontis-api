<?php

namespace App\Http\Middleware;

use App\Enums\ClientStatus;
use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientActiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user has an associated client with active status
     * and proper role permissions for client space access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', 'Veuillez vous connecter pour accéder à cet espace.');
        }

        // Vérifier que l'utilisateur a bien le rôle CLIENT
        if (!$user->isClient()) {
            // Si l'utilisateur a un autre rôle (technician, operator, manager, administrator)
            // le rediriger vers le dashboard CRM approprié
            return redirect()
                ->route('dashboard')
                ->with('error', 'Cet espace est réservé aux clients. Vous avez été redirigé vers votre tableau de bord.');
        }

        // Find client associated with this user
        $client = Client::query()->where('user_id', $user->id)->first();

        if (!$client) {
            return redirect()
                ->route('login')
                ->with('error', 'Aucun compte client associé à votre compte utilisateur.');
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
                ->with('error', 'Votre compte client n\'est pas actif. Veuillez contacter le support.');
        }

        // Si le client a un statut "fermé", bloquer l'accès complètement
        if ($client->status->value === ClientStatus::CLOSED->value) {
            return redirect()
                ->route('login')
                ->with('error', 'Votre compte client est clôturé. Merci de contacter le support technique pour plus d\'informations.');
        }

        // Si le client a un statut "désactivé", afficher un avertissement mais permettre l'accès
        if ($client->status->value === ClientStatus::DISABLED->value) {
            session()->flash('warning', 'Votre compte est temporairement désactivé. Certaines fonctionnalités peuvent être limitées.');
        }

        // Si le client a un avis formel, afficher un avertissement
        if ($client->status->value === ClientStatus::FORMAL_NOTICE->value) {
            session()->flash('warning', 'Votre compte fait l\'objet d\'une mise en demeure. Veuillez régulariser votre situation.');
        }

        // Share client data with the request for use in controllers
        $request->merge(['client' => $client]);

        return $next($request);
    }
}
