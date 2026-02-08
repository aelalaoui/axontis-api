@component('mail::message')
{{-- Salutation personnalisée --}}
# {{ __('Bienvenue, :name !', ['name' => $userName]) }}

{{ __('Nous sommes ravis de vous accueillir sur :company.', ['company' => $companyName]) }}

{{ __('Votre compte a été créé avec succès. Pour activer votre accès et définir votre mot de passe, veuillez cliquer sur le bouton ci-dessous :') }}

@component('mail::button', ['url' => $activationLink, 'color' => 'primary'])
{{ __('Activer mon compte') }}
@endcomponent

{{-- Étapes suivantes --}}
## {{ __('Prochaines étapes') }}

@component('mail::panel')
1. {{ __('Cliquez sur le bouton ci-dessus pour activer votre compte') }}
2. {{ __('Définissez un mot de passe sécurisé') }}
3. {{ __('Connectez-vous et découvrez votre espace personnel') }}
@endcomponent

{{-- Lien alternatif --}}
{{ __('Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :') }}

<small>{{ $activationLink }}</small>

{{-- Note de sécurité --}}
@component('mail::subcopy')
{{ __('Ce lien d\'activation est valide pendant 48 heures. Si vous n\'avez pas demandé la création de ce compte, vous pouvez ignorer cet email.') }}
@endcomponent

{{ __('Cordialement,') }}<br>
{{ __('L\'équipe :company', ['company' => $companyName]) }}
@endcomponent
