<?php

namespace App\Services;

use App\Models\InstallationDevice;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service unique pour toutes les interactions avec Hik-Partner Pro OpenAPI.
 *
 * Ce service est le SEUL endroit du codebase qui connaît les credentials HPP.
 * Chaque requête est signée dynamiquement avec HMAC-SHA256.
 */
class HikPartnerProService
{
    private ?string $arcId;
    private ?string $arcKey;
    private ?string $baseUrl;
    private int $connectTimeout;
    private int $requestTimeout;

    public function __construct()
    {
        $this->arcId = config('hikvision.hpp.arc_id');
        $this->arcKey = config('hikvision.hpp.arc_key');
        $this->baseUrl = rtrim(config('hikvision.hpp.base_url'), '/');
        $this->connectTimeout = config('hikvision.hpp.connect_timeout', 5);
        $this->requestTimeout = config('hikvision.hpp.request_timeout', 30);
    }

    // ─── Public API — Arm / Disarm ───────────────────────────

    /**
     * Armer la centrale.
     *
     * @param InstallationDevice $installationDevice
     * @param string $mode 'away' | 'stay' | 'instant'
     * @throws \Exception
     */
    public function arm(InstallationDevice $installationDevice, string $mode): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $modeMap = [
            'away' => 'AWAY_ARM',
            'stay' => 'STAY_ARM',
            'instant' => 'INSTANT_ARM',
        ];

        $response = $this->post("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/arm", [
            'armType' => $modeMap[$mode] ?? 'AWAY_ARM',
        ]);

        return $response->json();
    }

    /**
     * Désarmer la centrale.
     */
    public function disarm(InstallationDevice $installationDevice): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $response = $this->post("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/disarm", []);

        return $response->json();
    }

    // ─── Public API — Status ─────────────────────────────────

    /**
     * Récupérer le statut du device (zones, arm_status, connectivity).
     */
    public function getDeviceStatus(InstallationDevice $installationDevice): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $response = $this->get("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/status");

        return $response->json();
    }

    // ─── Public API — Panel Users ────────────────────────────

    /**
     * Liste les utilisateurs du panel.
     */
    public function listPanelUsers(InstallationDevice $installationDevice): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $response = $this->get("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/users");

        return $response->json('data', []);
    }

    /**
     * Créer un utilisateur sur le panel.
     */
    public function createPanelUser(InstallationDevice $installationDevice, array $data): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $response = $this->post("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/users", $data);

        return $response->json();
    }

    /**
     * Modifier un utilisateur du panel.
     */
    public function updatePanelUser(InstallationDevice $installationDevice, string $userId, array $data): array
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $response = $this->put("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/users/{$userId}", $data);

        return $response->json();
    }

    /**
     * Supprimer un utilisateur du panel.
     */
    public function deletePanelUser(InstallationDevice $installationDevice, string $userId): void
    {
        $hppDeviceId = $installationDevice->getHppDeviceId();
        $siteId = $installationDevice->getProperty('hpp_site_id');

        $this->delete("/api/v1/estate/{$siteId}/devices/{$hppDeviceId}/users/{$userId}");
    }

    // ─── HMAC Signature ──────────────────────────────────────

    /**
     * Construit les headers signés pour une requête HPP.
     */
    private function buildSignedHeaders(string $method, string $path, string $body = ''): array
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $nonce = (string) Str::uuid();
        $contentType = 'application/json';
        $contentMd5 = $body !== '' ? base64_encode(md5($body, true)) : '';
        $date = gmdate('D, d M Y H:i:s \G\M\T');

        $stringToSign = strtoupper($method) . "\n"
            . $contentMd5 . "\n"
            . $contentType . "\n"
            . $date . "\n"
            . $path;

        $signature = base64_encode(
            hash_hmac('sha256', $stringToSign, $this->arcKey, true)
        );

        return [
            'X-Ca-Key' => $this->arcId,
            'X-Ca-Timestamp' => $timestamp,
            'X-Ca-Nonce' => $nonce,
            'X-Ca-Signature' => $signature,
            'Content-Type' => $contentType,
            'Content-MD5' => $contentMd5,
            'Date' => $date,
        ];
    }

    // ─── HTTP Methods ────────────────────────────────────────

    private function get(string $path): Response
    {
        $headers = $this->buildSignedHeaders('GET', $path);

        $response = Http::timeout($this->requestTimeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders($headers)
            ->get($this->baseUrl . $path);

        $this->logResponse('GET', $path, $response);

        $response->throw();

        return $response;
    }

    private function post(string $path, array $data): Response
    {
        $body = json_encode($data);
        $headers = $this->buildSignedHeaders('POST', $path, $body);

        $response = Http::timeout($this->requestTimeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($this->baseUrl . $path);

        $this->logResponse('POST', $path, $response);

        $response->throw();

        return $response;
    }

    private function put(string $path, array $data): Response
    {
        $body = json_encode($data);
        $headers = $this->buildSignedHeaders('PUT', $path, $body);

        $response = Http::timeout($this->requestTimeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->put($this->baseUrl . $path);

        $this->logResponse('PUT', $path, $response);

        $response->throw();

        return $response;
    }

    private function delete(string $path): Response
    {
        $headers = $this->buildSignedHeaders('DELETE', $path);

        $response = Http::timeout($this->requestTimeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders($headers)
            ->delete($this->baseUrl . $path);

        $this->logResponse('DELETE', $path, $response);

        $response->throw();

        return $response;
    }

    // ─── Logging ─────────────────────────────────────────────

    private function logResponse(string $method, string $path, Response $response): void
    {
        $level = $response->successful() ? 'info' : 'error';

        Log::channel('single')->{$level}('HikPartnerPro API', [
            'method' => $method,
            'path' => $path,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);
    }
}

