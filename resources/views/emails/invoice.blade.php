@component('mail::message')
{{-- Salutation --}}
# {{ __('Facture :number', ['number' => $invoiceNumber]) }}

{{ __('Bonjour :name,', ['name' => $clientName]) }}

{{ __('Veuillez trouver ci-dessous les détails de votre facture.') }}

{{-- Détails de la facture --}}
@component('mail::panel')
**{{ __('Numéro de facture') }}** : {{ $invoiceNumber }}
**{{ __('Date d\'émission') }}** : {{ $invoiceDate }}
**{{ __('Montant dû') }}** : **{{ number_format($amountDue, 2, ',', ' ') }} €**
**{{ __('Date d\'échéance') }}** : {{ $dueDate }}
@endcomponent

{{-- Bouton de téléchargement --}}
@if($pdfUrl)
@component('mail::button', ['url' => $pdfUrl, 'color' => 'primary'])
{{ __('Télécharger la facture PDF') }}
@endcomponent
@endif

{{-- Informations de paiement --}}
## {{ __('Moyens de paiement') }}

{{ __('Vous pouvez régler cette facture par :') }}

- {{ __('Virement bancaire') }}
- {{ __('Carte bancaire via votre espace client') }}
- {{ __('Prélèvement automatique (si activé)') }}

{{-- Coordonnées bancaires --}}
@component('mail::panel')
**{{ __('Coordonnées bancaires') }}**
IBAN : FR76 XXXX XXXX XXXX XXXX XXXX XXX
BIC : XXXXXXXX
{{ __('Référence à indiquer') }} : {{ $invoiceNumber }}
@endcomponent

{{-- Rappel d'échéance --}}
@component('mail::subcopy')
{{ __('Merci de régler cette facture avant le :date pour éviter tout retard.', ['date' => $dueDate]) }}
@endcomponent

{{ __('Merci pour votre confiance.') }}<br>
{{ __('L\'équipe :app', ['app' => config('app.name')]) }}
@endcomponent
