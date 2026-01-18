<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                if ($user->isClient()) {
                    return redirect()->route('client.home');
                }
                if ($user->isTechnician()) {
                    return redirect()->route('crm.dashboard');
                }
                if ($user->isOperator()) {
                    return redirect()->route('crm.dashboard');
                }
                if ($user->isManager()) {
                    return redirect()->route('crm.dashboard');
                }
                if ($user->isAdministrator()) {
                    return redirect()->route('home');
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
