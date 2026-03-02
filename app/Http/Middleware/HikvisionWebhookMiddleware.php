<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sécurité pour les webhooks Hikvision AX PRO.
 *
 * 1. Lookup Device par panel_serial_number (property)
 * 2. Vérification IP source contre webhook_ip_whitelist
 * 3. Vérification signature HMAC (X-Hikvision-Signature)
 * 4. Rate limiting Redis : 500 req/min par IP
 * 5. Déduplication Redis : fenêtre 60s par (device_uuid + cid_code)
 */
class HikvisionWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $serialNumber = $request->route('serial_number');

        // 1. Lookup Device par panel_serial_number
        $device = $this->resolveDevice($serialNumber);

        if (!$device) {
            // 404 silencieux — pas 401, pour éviter l'énumération
            Log::warning('Hikvision webhook: unknown serial', ['serial' => $serialNumber]);
            abort(404);
        }

        // Stocker le device résolu pour le controller
        $request->attributes->set('alarm_device', $device);

        // 2. Vérification IP source
        $sourceIp = $request->ip();
        $whitelist = $device->getWebhookIpWhitelist();

        if (!empty($whitelist) && !in_array($sourceIp, $whitelist)) {
            Log::warning('Hikvision webhook: IP not whitelisted', [
                'ip' => $sourceIp,
                'serial' => $serialNumber,
                'whitelist' => $whitelist,
            ]);
            abort(403);
        }

        // 3. Vérification signature HMAC
        $webhookSecret = $device->getWebhookSecret();
        $signature = $request->header('X-Hikvision-Signature');

        if ($webhookSecret && $signature) {
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Hikvision webhook: invalid HMAC signature', [
                    'serial' => $serialNumber,
                    'ip' => $sourceIp,
                ]);
                abort(403);
            }
        }

        // 4. Rate limiting : 500 req/min par IP
        $rateLimitKey = "hikvision_webhook_rate:{$sourceIp}";
        $rateLimit = config('hikvision.webhook.rate_limit_per_minute', 500);
        $currentCount = (int) Cache::get($rateLimitKey, 0);

        if ($currentCount >= $rateLimit) {
            Log::warning('Hikvision webhook: rate limit exceeded', [
                'ip' => $sourceIp,
                'count' => $currentCount,
            ]);
            return response()->json(['message' => 'Rate limit exceeded'], 429);
        }

        Cache::put($rateLimitKey, $currentCount + 1, 60);

        // 5. Déduplication : fenêtre configurable par (device_uuid + cid_code)
        $payload = $request->all();
        $cidCode = $payload['CIDEvent']['code'] ?? $payload['cid_code'] ?? null;

        if ($cidCode !== null) {
            $deduplicateWindow = config('hikvision.webhook.deduplicate_window', 60);
            $dedupKey = "hikvision_dedup:{$device->uuid}:{$cidCode}";

            if (Cache::has($dedupKey)) {
                Log::debug('Hikvision webhook: deduplicated', [
                    'device' => $device->uuid,
                    'cid_code' => $cidCode,
                ]);
                // Toujours répondre 202 pour éviter les retransmissions
                return response()->json(['status' => 'accepted'], 202);
            }

            Cache::put($dedupKey, true, $deduplicateWindow);
        }

        return $next($request);
    }

    /**
     * Résout un Device depuis la property panel_serial_number.
     */
    private function resolveDevice(string $serialNumber): ?Device
    {
        return Device::alarmPanels()
            ->whereHas('properties', function ($query) use ($serialNumber) {
                $query->where('property', 'panel_serial_number')
                    ->where('value', $serialNumber);
            })
            ->first();
    }
}

