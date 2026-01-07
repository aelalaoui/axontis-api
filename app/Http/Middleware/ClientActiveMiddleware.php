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
     * Ensures that the authenticated user has an associated client with active status.
     * Works for both API and Web requests.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request, 'Non authentifié');
        }

        // Find client associated with this user
        $client = Client::query()
            ->where('user_id', $user->id)
            ->first();

        if (!$client) {
            return $this->forbidden($request, 'Aucun compte client associé à cet utilisateur.');
        }

        // Check if client has active status
        $activeStatuses = [
            ClientStatus::ACTIVE->value,
            ClientStatus::FORMAL_NOTICE->value,
            ClientStatus::DISABLED->value,
        ];

        if (!in_array($client->status->value, $activeStatuses)) {
            return $this->forbidden($request, 'Votre compte client n\'est pas actif.');
        }

        if ($client->status->value === ClientStatus::CLOSED->value) {
            return $this->forbidden($request, 'Votre compte client est clôturé. Merci de contacter le support technique.');
        }

        // Attach client to request for use in controller
        $request->merge(['client' => $client]);

        return $next($request);
    }

    /**
     * Return unauthorized response based on request type
     */
    private function unauthorized(Request $request, string $message = 'Non authentifié'): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        }

        return redirect()->route('login')->with('error', $message);
    }

    /**
     * Return forbidden response based on request type
     */
    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
