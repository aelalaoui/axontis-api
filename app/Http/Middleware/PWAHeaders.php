<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PWAHeaders
{
    /**
     * Configuration des headers PWA
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Headers pour le Service Worker et Manifest
        if ($request->path() === 'sw.js' || $request->path() === 'manifest.webmanifest') {
            // Court cache pour le Service Worker et Manifest
            $response->header('Cache-Control', 'public, max-age=3600, no-cache');
            $response->header('Service-Worker-Allowed', '/');

            if ($request->path() === 'manifest.webmanifest') {
                $response->header('Content-Type', 'application/manifest+json');
            }
        }

        // Headers de sécurité PWA
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-XSS-Protection', '1; mode=block');

        // Headers CORS si nécessaire
        if (config('app.env') !== 'production') {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        // Headers pour les assets PWA
        if (preg_match('/\.(png|ico|webmanifest|svg|woff2?)$/i', $request->path())) {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}

