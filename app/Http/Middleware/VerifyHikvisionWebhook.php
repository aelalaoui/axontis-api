<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier l'authenticité des webhooks Hikvision.
 *
 * Sécurité :
 * - Whitelist d'IPs autorisées
 * - Vérification de signature (si configurée)
 * - Rate limiting (via middleware séparé)
 *
 * Usage dans routes:
 *   Route::post('/webhooks/hikvision/alarm', ...)->middleware('hikvision.webhook');
 */
class VerifyHikvisionWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check IP whitelist
        if (!$this->isIpAllowed($request)) {
            Log::warning('HikvisionWebhook: Unauthorized IP', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: IP not whitelisted',
            ], 403);
        }

        // 2. Verify signature if configured
        if (!$this->verifySignature($request)) {
            Log::warning('HikvisionWebhook: Invalid signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid signature',
            ], 403);
        }

        // 3. Validate content type
        if (!$this->isValidContentType($request)) {
            Log::warning('HikvisionWebhook: Invalid content type', [
                'content_type' => $request->header('Content-Type'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bad Request: Invalid content type',
            ], 400);
        }

        // Log successful webhook receipt
        if (config('hikvision.logging.enabled', true)) {
            Log::info('HikvisionWebhook: Received', [
                'ip' => $request->ip(),
                'content_length' => $request->header('Content-Length'),
                'event_type' => $request->input('eventType'),
            ]);
        }

        return $next($request);
    }

    /**
     * Check if the request IP is in the whitelist.
     *
     * @param Request $request
     * @return bool
     */
    protected function isIpAllowed(Request $request): bool
    {
        $whitelist = config('hikvision.webhook.ip_whitelist', []);

        // If no whitelist configured, allow all (not recommended for production)
        if (empty($whitelist)) {
            return true;
        }

        $clientIp = $request->ip();

        foreach ($whitelist as $allowedIp) {
            $allowedIp = trim($allowedIp);

            if (empty($allowedIp)) {
                continue;
            }

            // Check for CIDR notation
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInCidr($clientIp, $allowedIp)) {
                    return true;
                }
            } elseif ($clientIp === $allowedIp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range.
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);

        $mask = (int) $mask;
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);

        if ($ip === false || $subnet === false) {
            return false;
        }

        $mask = -1 << (32 - $mask);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Verify the webhook signature if configured.
     *
     * @param Request $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $secret = config('hikvision.webhook.signature_secret');

        // If no secret configured, skip signature verification
        if (empty($secret)) {
            return true;
        }

        $signatureHeader = config('hikvision.webhook.signature_header', 'X-Hikvision-Signature');
        $signature = $request->header($signatureHeader);

        if (empty($signature)) {
            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate the content type is acceptable.
     *
     * @param Request $request
     * @return bool
     */
    protected function isValidContentType(Request $request): bool
    {
        $contentType = $request->header('Content-Type', '');

        // Accept JSON or XML
        $validTypes = [
            'application/json',
            'application/xml',
            'text/xml',
            'text/plain', // Some devices send plain text
        ];

        foreach ($validTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        // Also accept if no content-type (some devices don't set it)
        if (empty($contentType)) {
            return true;
        }

        return false;
    }
}
