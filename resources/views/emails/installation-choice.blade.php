@component('mail::message')

# {{ __('Bonjour :name,', ['name' => $clientName]) }}

@if($installationMode === 'technician')

{{ __('Merci pour votre confiance ! Nous avons bien enregistré votre choix : un technicien Axontis se déplacera chez vous pour réaliser l\'installation de votre système de sécurité.') }}

> 🔧 **{{ __('Mode d\'installation') }}** : {{ __('Installation par technicien Axontis') }}
>
> 💳 **{{ __('Frais d\'installation réglés') }}** : {{ number_format($installationFeeAmount, 2, ',', ' ') }} {{ $currency }}
@if($scheduledDate)
>
> 📅 **{{ __('Date d\'intervention') }}** : {{ \Carbon\Carbon::parse($scheduledDate)->translatedFormat('l d F Y') }} {{ __('à') }} {{ $scheduledTime }}
@endif

## {{ __('Prochaines étapes') }}

@if($scheduledDate)
1. {{ __('Un technicien Axontis certifié se déplacera chez vous à la date convenue') }}
2. {{ __('L\'installation complète du système sera réalisée et testée devant vous') }}
3. {{ __('Vous recevrez une démonstration complète de votre système de sécurité') }}
4. {{ __('Votre système sera opérationnel dès la fin de l\'intervention') }}
@else
1. {{ __('Choisissez votre date d\'intervention depuis votre espace client') }}
2. {{ __('Un technicien certifié se déplacera à l\'adresse de votre installation') }}
3. {{ __('L\'installation complète du système sera réalisée et testée devant vous') }}
4. {{ __('Vous recevrez une démonstration de l\'utilisation de votre système') }}
@endif

@else

{{ __('Merci pour votre confiance ! Nous avons bien enregistré votre choix : votre matériel vous sera livré par voie postale afin que vous puissiez réaliser l\'installation vous-même.') }}

> 📦 **{{ __('Mode d\'installation') }}** : {{ __('Auto-installation (livraison postale)') }}
>
> 📍 **{{ __('Adresse de livraison') }}** : {{ $deliveryAddress }}

## {{ __('Prochaines étapes') }}

1. {{ __('Votre commande est en cours de préparation') }}
2. {{ __('Vous recevrez un email de suivi lorsque votre colis sera expédié') }}
3. {{ __('Un guide d\'installation détaillé sera inclus dans votre colis') }}
4. {{ __('Notre support technique reste disponible si vous avez besoin d\'aide') }}

@endif

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'primary'])
{{ __('Accéder à mon espace') }}
@endcomponent

---

{{ __('Une question ? Contactez notre service client :') }} [contact@axontis.com](mailto:contact@axontis.com)

{{ __('Merci pour votre confiance !') }}
**{{ __('L\'équipe :company', ['company' => $companyName]) }}**

@endcomponent

