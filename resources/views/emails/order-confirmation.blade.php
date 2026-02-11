@component('mail::message')
{{-- En-tête avec numéro de commande --}}
# {{ __('Merci pour votre commande !') }}

{{ __('Bonjour :name,', ['name' => $customerName]) }}

{{ __('Nous avons bien reçu votre commande et elle est en cours de traitement.') }}

{{-- Résumé de la commande --}}
@component('mail::panel')
**{{ __('Commande') }}** : #{{ $orderNumber }}
**{{ __('Date') }}** : {{ $orderDate }}
**{{ __('Total') }}** : {{ number_format($totalAmount, 2, ',', ' ') }} €
@endcomponent

{{-- Tableau des articles --}}
@if(count($items) > 0)
## {{ __('Détails de votre commande') }}

@component('mail::table')
| {{ __('Article') }} | {{ __('Quantité') }} | {{ __('Prix') }} |
|:-------------------|:--------------------:|------------------:|
@foreach($items as $item)
| {{ $item['name'] ?? $item['product_name'] ?? 'Article' }} | {{ $item['quantity'] ?? 1 }} | {{ number_format($item['price'] ?? 0, 2, ',', ' ') }} € |
@endforeach
| **{{ __('Total') }}** | | **{{ number_format($totalAmount, 2, ',', ' ') }} €** |
@endcomponent
@endif

{{-- Bouton de suivi --}}
@if($trackingLink)
@component('mail::button', ['url' => $trackingLink, 'color' => 'primary'])
{{ __('Suivre ma commande') }}
@endcomponent
@endif

{{-- Informations complémentaires --}}
## {{ __('Que se passe-t-il ensuite ?') }}

1. {{ __('Nous préparons votre commande avec soin') }}
2. {{ __('Vous recevrez un email lorsque votre colis sera expédié') }}
3. {{ __('Suivez votre livraison en temps réel') }}

{{-- Contact support --}}
@component('mail::subcopy')
{{ __('Une question sur votre commande ? Contactez notre service client à') }} [{{ config('mail.from.address') }}](mailto:{{ config('mail.from.address') }})
@endcomponent

{{ __('Merci pour votre confiance !') }}<br>
{{ __('L\'équipe :app', ['app' => config('app.name')]) }}
@endcomponent
