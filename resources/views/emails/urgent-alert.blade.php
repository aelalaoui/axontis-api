@component('mail::message')
{{-- Badge de sévérité --}}
@php
$severityColor = match($severity) {
    'info' => '#3498db',
    'warning' => '#f39c12',
    'critical' => '#e74c3c',
    default => '#95a5a6'
};
$severityEmoji = match($severity) {
    'info' => 'ℹ️',
    'warning' => '⚠️',
    'critical' => '🚨',
    default => '🔔'
};
$severityLabel = match($severity) {
    'info' => __('Information'),
    'warning' => __('Avertissement'),
    'critical' => __('Critique'),
    default => __('Alerte')
};
@endphp

# {{ $severityEmoji }} {{ __('Alerte :severity', ['severity' => strtoupper($severity)]) }}

<div style="background-color: {{ $severityColor }}; color: white; padding: 8px 16px; border-radius: 4px; display: inline-block; font-weight: bold; margin-bottom: 16px;">
    {{ $severityLabel }}
</div>

{{-- Titre de l'alerte --}}
## {{ $alertTitle }}

{{-- Message détaillé --}}
@component('mail::panel')
{{ $alertMessage }}
@endcomponent

{{-- Timestamp --}}
**{{ __('Date et heure') }}** : {{ $timestamp }}

{{-- Bouton d'action --}}
@if($actionUrl)
@component('mail::button', ['url' => $actionUrl, 'color' => $severity === 'critical' ? 'error' : 'primary'])
{{ __('Voir les détails') }}
@endcomponent
@endif

{{-- Informations supplémentaires --}}
@component('mail::subcopy')
{{ __('Cette alerte a été générée automatiquement par le système de monitoring de :app.', ['app' => config('app.name')]) }}

{{ __('Si vous pensez que cette alerte est une erreur, veuillez contacter l\'équipe technique.') }}
@endcomponent

{{-- Signature --}}
{{ __('Système de monitoring') }}<br>
{{ config('app.name') }}
@endcomponent
