<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la validation des webhooks Hikvision.
 *
 * Valide le format JSON des événements CID envoyés par les centrales.
 * Supporte plusieurs formats de payload Hikvision.
 */
class HikvisionWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Device identification
            'ipAddress' => 'nullable|ip',
            'macAddress' => 'nullable|string|max:17',
            'portNo' => 'nullable|integer|min:1|max:65535',
            'protocol' => 'nullable|string|max:20',
            'channelID' => 'nullable|integer|min:0',

            // Event information
            'eventType' => 'required|string|max:50',
            'eventState' => 'nullable|string|in:active,inactive,restore',
            'eventDescription' => 'nullable|string|max:500',
            'dateTime' => 'nullable|string|max:50',

            // CID Event details
            'CIDEvent' => 'nullable|array',
            'CIDEvent.code' => 'nullable|integer|min:0|max:9999',
            'CIDEvent.standardCIDcode' => 'nullable|integer|min:0|max:9999',
            'CIDEvent.type' => 'nullable|string|max:50',
            'CIDEvent.trigger' => 'nullable|string|max:50',
            'CIDEvent.zone' => 'nullable|integer|min:0|max:255',
            'CIDEvent.user' => 'nullable|integer|min:0|max:255',
            'CIDEvent.partition' => 'nullable|integer|min:0|max:8',

            // System event details (alternative format)
            'SystemEvent' => 'nullable|array',
            'SystemEvent.type' => 'nullable|string|max:50',
            'SystemEvent.status' => 'nullable|string|max:50',

            // Zone status (alternative format)
            'ZoneStatus' => 'nullable|array',
            'ZoneStatus.zoneNo' => 'nullable|integer|min:0|max:255',
            'ZoneStatus.status' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eventType.required' => 'Event type is required',
            'eventType.max' => 'Event type must not exceed 50 characters',
            'ipAddress.ip' => 'IP address must be a valid IP',
            'CIDEvent.code.integer' => 'CID code must be an integer',
            'CIDEvent.zone.integer' => 'Zone number must be an integer',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Handle XML payloads converted to JSON
        if ($this->isXml()) {
            $this->convertXmlToJson();
        }

        // Normalize MAC address to uppercase
        if ($this->has('macAddress')) {
            $this->merge([
                'macAddress' => strtoupper($this->macAddress),
            ]);
        }
    }

    /**
     * Check if the request is XML.
     */
    protected function isXml(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'xml');
    }

    /**
     * Convert XML payload to JSON array.
     */
    protected function convertXmlToJson(): void
    {
        $content = $this->getContent();

        if (empty($content)) {
            return;
        }

        try {
            $xml = simplexml_load_string($content);
            if ($xml !== false) {
                $json = json_encode($xml);
                $data = json_decode($json, true);

                if (is_array($data)) {
                    $this->replace($data);
                }
            }
        } catch (\Exception $e) {
            // Leave as-is, validation will fail if needed
        }
    }

    /**
     * Get the validated payload with defaults.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return array_merge([
            'eventType' => 'unknown',
            'eventState' => 'active',
            'dateTime' => now()->toIso8601String(),
        ], $this->validated());
    }
}
