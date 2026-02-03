<?php

namespace App\Services;

use App\Models\AlarmDevice;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Service pour communiquer avec l'API ISAPI des centrales Hikvision AX PRO.
 *
 * Utilise l'authentification HTTP Digest pour les appels vers les centrales.
 * Supporte les opérations de lecture (info, statut) et d'écriture (armement).
 *
 * @see https://www.hikvision.com/content/dam/hikvision/products/documentation/ISAPI-Core-Protocol.pdf
 */
class HikvisionApiService
{
    /**
     * HTTP client
     */
    protected Client $client;

    /**
     * Default timeout settings
     */
    protected int $connectTimeout;
    protected int $requestTimeout;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connectTimeout = config('hikvision.timeouts.connect', 5);
        $this->requestTimeout = config('hikvision.timeouts.request', 30);

        $this->client = new Client([
            'connect_timeout' => $this->connectTimeout,
            'timeout' => $this->requestTimeout,
            'verify' => false, // Self-signed certs common on devices
            'http_errors' => false,
        ]);
    }

    // =========================================================================
    // DEVICE INFORMATION
    // =========================================================================

    /**
     * Test la connexion à une centrale.
     *
     * @param AlarmDevice $device
     * @return array{success: bool, message: string, data?: array}
     */
    public function testConnection(AlarmDevice $device): array
    {
        if (!$device->isConfiguredForApi()) {
            return [
                'success' => false,
                'message' => 'Device is not configured for API access (missing IP or credentials)',
            ];
        }

        try {
            $response = $this->makeRequest($device, 'GET', '/ISAPI/System/deviceInfo');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $response['data'],
                ];
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'testConnection');
        }
    }

    /**
     * Récupère les informations de la centrale.
     *
     * @param AlarmDevice $device
     * @return array{success: bool, message: string, data?: array}
     */
    public function getDeviceInfo(AlarmDevice $device): array
    {
        try {
            $response = $this->makeRequest($device, 'GET', '/ISAPI/System/deviceInfo');

            if ($response['success']) {
                // Update device info in database
                $this->updateDeviceInfo($device, $response['data']);

                return $response;
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'getDeviceInfo');
        }
    }

    /**
     * Récupère le statut actuel de la centrale (armé/désarmé, zones, etc.).
     *
     * @param AlarmDevice $device
     * @return array{success: bool, message: string, data?: array}
     */
    public function getStatus(AlarmDevice $device): array
    {
        try {
            $response = $this->makeRequest($device, 'GET', '/ISAPI/SecurityCP/status');

            if ($response['success']) {
                $this->updateDeviceStatus($device, $response['data']);
                return $response;
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'getStatus');
        }
    }

    /**
     * Récupère le statut des zones.
     *
     * @param AlarmDevice $device
     * @return array{success: bool, message: string, data?: array}
     */
    public function getZoneStatus(AlarmDevice $device): array
    {
        try {
            return $this->makeRequest($device, 'GET', '/ISAPI/SecurityCP/status/zones');
        } catch (Exception $e) {
            return $this->handleException($e, $device, 'getZoneStatus');
        }
    }

    // =========================================================================
    // ARM/DISARM OPERATIONS
    // =========================================================================

    /**
     * Arme la centrale en mode "Away" (total).
     *
     * @param AlarmDevice $device
     * @param int|null $partition Partition à armer (null = toutes)
     * @return array{success: bool, message: string}
     */
    public function armAway(AlarmDevice $device, ?int $partition = null): array
    {
        return $this->changeArmStatus($device, 'away', $partition);
    }

    /**
     * Arme la centrale en mode "Stay" (partiel).
     *
     * @param AlarmDevice $device
     * @param int|null $partition
     * @return array{success: bool, message: string}
     */
    public function armStay(AlarmDevice $device, ?int $partition = null): array
    {
        return $this->changeArmStatus($device, 'stay', $partition);
    }

    /**
     * Désarme la centrale.
     *
     * @param AlarmDevice $device
     * @param int|null $partition
     * @return array{success: bool, message: string}
     */
    public function disarm(AlarmDevice $device, ?int $partition = null): array
    {
        return $this->changeArmStatus($device, 'disarm', $partition);
    }

    /**
     * Change le statut d'armement.
     *
     * @param AlarmDevice $device
     * @param string $mode 'away', 'stay', 'disarm'
     * @param int|null $partition
     * @return array{success: bool, message: string}
     */
    protected function changeArmStatus(AlarmDevice $device, string $mode, ?int $partition = null): array
    {
        try {
            $endpoint = '/ISAPI/SecurityCP/control/arm';

            $body = [
                'ArmCtrl' => [
                    'mode' => $mode,
                ],
            ];

            if ($partition !== null) {
                $body['ArmCtrl']['subSysNo'] = $partition;
            }

            $response = $this->makeRequest($device, 'PUT', $endpoint, [
                'json' => $body,
            ]);

            if ($response['success']) {
                // Update local arm status
                $armStatus = match ($mode) {
                    'away' => AlarmDevice::ARM_AWAY,
                    'stay' => AlarmDevice::ARM_STAY,
                    'disarm' => AlarmDevice::DISARMED,
                    default => AlarmDevice::ARM_UNKNOWN,
                };
                $device->updateArmStatus($armStatus);

                Log::info('HikvisionApi: Arm status changed', [
                    'device_uuid' => $device->uuid,
                    'mode' => $mode,
                    'partition' => $partition,
                ]);

                return [
                    'success' => true,
                    'message' => "Successfully changed arm status to: {$mode}",
                ];
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'changeArmStatus');
        }
    }

    // =========================================================================
    // EVENT POLLING (FALLBACK)
    // =========================================================================

    /**
     * Poll les événements récents (fallback si webhook non disponible).
     *
     * @param AlarmDevice $device
     * @param int $limit Nombre max d'événements
     * @return array{success: bool, message: string, data?: array}
     */
    public function pollEvents(AlarmDevice $device, int $limit = 50): array
    {
        try {
            $response = $this->makeRequest($device, 'GET', '/ISAPI/Event/notification/alertStream', [
                'query' => [
                    'format' => 'json',
                    'limit' => $limit,
                ],
            ]);

            if ($response['success']) {
                Log::info('HikvisionApi: Polled events', [
                    'device_uuid' => $device->uuid,
                    'event_count' => count($response['data']['events'] ?? []),
                ]);
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'pollEvents');
        }
    }

    /**
     * Récupère l'historique des événements.
     *
     * @param AlarmDevice $device
     * @param \DateTime|null $startTime
     * @param \DateTime|null $endTime
     * @param int $limit
     * @return array{success: bool, message: string, data?: array}
     */
    public function getEventHistory(
        AlarmDevice $device,
        ?\DateTime $startTime = null,
        ?\DateTime $endTime = null,
        int $limit = 100
    ): array {
        try {
            $params = [
                'searchMatchStyle' => 'or',
            ];

            if ($startTime) {
                $params['startTime'] = $startTime->format('Y-m-d\TH:i:s');
            }
            if ($endTime) {
                $params['endTime'] = $endTime->format('Y-m-d\TH:i:s');
            }

            $response = $this->makeRequest($device, 'POST', '/ISAPI/Event/notification/search', [
                'json' => [
                    'EventHistorySearch' => $params,
                    'maxResults' => $limit,
                ],
            ]);

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'getEventHistory');
        }
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Configure le webhook HTTP sur la centrale.
     *
     * @param AlarmDevice $device
     * @param string $webhookUrl URL du webhook Laravel
     * @return array{success: bool, message: string}
     */
    public function configureWebhook(AlarmDevice $device, string $webhookUrl): array
    {
        try {
            $parsedUrl = parse_url($webhookUrl);

            $config = [
                'HttpHostNotification' => [
                    'id' => 1,
                    'url' => $parsedUrl['path'] ?? '/api/webhooks/hikvision/alarm',
                    'protocolType' => ($parsedUrl['scheme'] ?? 'http') === 'https' ? 'HTTPS' : 'HTTP',
                    'addressingFormatType' => 'hostname',
                    'hostName' => $parsedUrl['host'] ?? '',
                    'portNo' => $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? 'http') === 'https' ? 443 : 80),
                    'httpAuthenticationMethod' => 'none',
                ],
            ];

            $response = $this->makeRequest(
                $device,
                'PUT',
                '/ISAPI/Event/notification/httpHosts/1',
                ['json' => $config]
            );

            if ($response['success']) {
                Log::info('HikvisionApi: Webhook configured', [
                    'device_uuid' => $device->uuid,
                    'webhook_url' => $webhookUrl,
                ]);
            }

            return $response;

        } catch (Exception $e) {
            return $this->handleException($e, $device, 'configureWebhook');
        }
    }

    /**
     * Récupère la configuration actuelle du webhook.
     *
     * @param AlarmDevice $device
     * @return array{success: bool, message: string, data?: array}
     */
    public function getWebhookConfig(AlarmDevice $device): array
    {
        try {
            return $this->makeRequest($device, 'GET', '/ISAPI/Event/notification/httpHosts');
        } catch (Exception $e) {
            return $this->handleException($e, $device, 'getWebhookConfig');
        }
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Make an HTTP request to the device with digest authentication.
     *
     * @param AlarmDevice $device
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return array{success: bool, message: string, data?: array, status_code?: int}
     */
    protected function makeRequest(
        AlarmDevice $device,
        string $method,
        string $endpoint,
        array $options = []
    ): array {
        if (!$device->isConfiguredForApi()) {
            return [
                'success' => false,
                'message' => 'Device not configured for API access',
            ];
        }

        $url = $device->api_url . $endpoint;

        // Add digest auth
        $options['auth'] = [
            $device->api_username,
            $device->api_password,
            'digest',
        ];

        // Default headers
        $options['headers'] = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);

        try {
            $response = $this->client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // Parse response
            $data = [];
            if (!empty($body)) {
                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Try XML parsing
                    $data = $this->parseXmlResponse($body);
                }
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                // Record successful communication
                $device->recordHeartbeat();

                return [
                    'success' => true,
                    'message' => 'Request successful',
                    'data' => $data,
                    'status_code' => $statusCode,
                ];
            }

            // Handle errors
            $errorMessage = $data['statusString'] ?? $data['subStatusCode'] ?? "HTTP {$statusCode}";

            return [
                'success' => false,
                'message' => "API error: {$errorMessage}",
                'data' => $data,
                'status_code' => $statusCode,
            ];

        } catch (ConnectException $e) {
            $device->updateStatus(AlarmDevice::STATUS_OFFLINE);
            throw $e;
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

            if ($statusCode === 401) {
                $device->updateStatus(AlarmDevice::STATUS_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Parse XML response (fallback for devices not supporting JSON).
     */
    protected function parseXmlResponse(string $body): array
    {
        try {
            $xml = simplexml_load_string($body);
            if ($xml === false) {
                return ['raw' => $body];
            }
            return json_decode(json_encode($xml), true) ?? ['raw' => $body];
        } catch (Exception $e) {
            return ['raw' => $body];
        }
    }

    /**
     * Update device info from API response.
     */
    protected function updateDeviceInfo(AlarmDevice $device, array $data): void
    {
        $updates = [];

        if (isset($data['DeviceInfo']['firmwareVersion'])) {
            $updates['firmware_version'] = $data['DeviceInfo']['firmwareVersion'];
        }
        if (isset($data['DeviceInfo']['model'])) {
            $updates['model'] = $data['DeviceInfo']['model'];
        }
        if (isset($data['DeviceInfo']['serialNumber'])) {
            $updates['serial_number'] = $data['DeviceInfo']['serialNumber'];
        }

        if (!empty($updates)) {
            $device->update($updates);
        }
    }

    /**
     * Update device status from API response.
     */
    protected function updateDeviceStatus(AlarmDevice $device, array $data): void
    {
        $armStatus = $data['SecurityCPStatus']['armStatus'] ?? null;

        if ($armStatus) {
            $mappedStatus = match ($armStatus) {
                'away', 'armed_away' => AlarmDevice::ARM_AWAY,
                'stay', 'armed_stay' => AlarmDevice::ARM_STAY,
                'disarmed' => AlarmDevice::DISARMED,
                default => AlarmDevice::ARM_UNKNOWN,
            };

            $device->updateArmStatus($mappedStatus);
        }
    }

    /**
     * Handle exceptions and return a formatted response.
     */
    protected function handleException(Exception $e, AlarmDevice $device, string $operation): array
    {
        $message = $e->getMessage();

        if ($e instanceof ConnectException) {
            $message = 'Connection failed - device may be offline';
            $device->updateStatus(AlarmDevice::STATUS_OFFLINE);
        } elseif ($e instanceof GuzzleException) {
            $message = 'HTTP request failed: ' . $e->getMessage();
        }

        Log::error("HikvisionApi: {$operation} failed", [
            'device_uuid' => $device->uuid,
            'device_ip' => $device->ip_address,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        return [
            'success' => false,
            'message' => $message,
            'error' => $e->getMessage(),
        ];
    }

    // =========================================================================
    // BATCH OPERATIONS
    // =========================================================================

    /**
     * Test connection for multiple devices in parallel.
     *
     * @param array $devices
     * @return array
     */
    public function batchTestConnections(array $devices): array
    {
        $results = [];

        foreach ($devices as $device) {
            $results[$device->uuid] = $this->testConnection($device);
        }

        return $results;
    }

    /**
     * Get status for multiple devices.
     *
     * @param array $devices
     * @return array
     */
    public function batchGetStatus(array $devices): array
    {
        $results = [];

        foreach ($devices as $device) {
            $results[$device->uuid] = $this->getStatus($device);
        }

        return $results;
    }
}
