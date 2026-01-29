<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        // Convert string roles to UserRole enum and check
        $allowedRoles = array_map(fn($role) => UserRole::from($role), $roles);

        if (!$user->hasAnyRole($allowedRoles)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
        }

        return $next($request);
    }
}

