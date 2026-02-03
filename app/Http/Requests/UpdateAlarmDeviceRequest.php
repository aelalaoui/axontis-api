<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request pour la mise à jour d'une centrale d'alarme.
 */
class UpdateAlarmDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization check
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $deviceUuid = $this->route('uuid');

        return [
            'installation_uuid' => 'nullable|string|uuid|exists:installations,uuid',
            'name' => 'sometimes|required|string|max:100',
            'serial_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('alarm_devices', 'serial_number')->ignore($deviceUuid, 'uuid'),
            ],
            'model' => [
                'nullable',
                'string',
                'max:50',
                Rule::in(config('hikvision.supported_models', [])),
            ],
            'ip_address' => 'nullable|ip',
            'mac_address' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
                Rule::unique('alarm_devices', 'mac_address')->ignore($deviceUuid, 'uuid'),
            ],
            'port' => 'nullable|integer|min:1|max:65535',
            'api_username' => 'nullable|string|max:100',
            'api_password' => 'nullable|string|max:255',
            'status' => [
                'nullable',
                Rule::in(['online', 'offline', 'error', 'configuring', 'unknown']),
            ],
            'arm_status' => [
                'nullable',
                Rule::in(['armed_away', 'armed_stay', 'disarmed', 'unknown']),
            ],
            'zone_count' => 'nullable|integer|min:1|max:255',
            'webhook_enabled' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'configuration' => 'nullable|array',
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
            'name.required' => 'Le nom de la centrale est obligatoire',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères',
            'serial_number.unique' => 'Ce numéro de série existe déjà',
            'mac_address.unique' => 'Cette adresse MAC existe déjà',
            'mac_address.regex' => 'L\'adresse MAC doit être au format XX:XX:XX:XX:XX:XX',
            'ip_address.ip' => 'L\'adresse IP n\'est pas valide',
            'installation_uuid.exists' => 'L\'installation spécifiée n\'existe pas',
            'model.in' => 'Le modèle n\'est pas supporté',
            'status.in' => 'Le statut n\'est pas valide',
            'arm_status.in' => 'Le statut d\'armement n\'est pas valide',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize MAC address
        if ($this->has('mac_address')) {
            $this->merge([
                'mac_address' => strtoupper($this->mac_address),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'installation_uuid' => 'installation',
            'serial_number' => 'numéro de série',
            'ip_address' => 'adresse IP',
            'mac_address' => 'adresse MAC',
            'api_username' => 'nom d\'utilisateur API',
            'api_password' => 'mot de passe API',
            'zone_count' => 'nombre de zones',
            'webhook_enabled' => 'webhook activé',
            'arm_status' => 'statut d\'armement',
        ];
    }
}
