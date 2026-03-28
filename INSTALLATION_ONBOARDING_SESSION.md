# Document de passation — Session de développement du 28 mars 2026

## `axontis-api` — Onboarding Installation Client : Choix du mode, Paiement Stripe, Tâche, Email de confirmation

---

## 1. Vue d'ensemble

**Stack** : Laravel 11 · Vue 3 / Inertia.js · Stripe · Tailwind CSS · MySQL

**Domaine métier** : Axontis est une plateforme de gestion d'installations de systèmes de sécurité (centrales d'alarme Hikvision, contrats clients, techniciens).

Cette session a ajouté un **tunnel d'onboarding** qui s'intercale entre la connexion du client et la prise de rendez-vous d'installation. Le client choisit désormais s'il veut être installé par un technicien Axontis (payant, 500 DH) ou s'auto-installer (livraison postale, gratuit). Tout le downstream (paiement Stripe, création de tâche opérateur, email transactionnel, bannières de la page d'accueil) a été adapté en conséquence.

---

## 2. Flux global — Vue d'ensemble

```
Connexion client
      │
      ▼
ClientController::home()
  ├── step === SCHEDULE_STEP && installation_mode non défini ?
  │         └──► Redirect → GET /client/installation-setup
  │                              (ClientInstallationChoiceController::show)
  │
  └── sinon → rendu Client/Home (page d'accueil)

GET /client/installation-setup
  └── Vue InstallationChoice.vue
        ├── STEP 0 : Choix du mode
        │       ├── "Technicien" (500 DH)
        │       └── "J'installe moi-même" (Gratuit)
        │
        └── STEP 1 (selon choix)
              ├── Technicien → widget Stripe Card
              │     POST /api/payments/installation-fee/init
              │     stripe.confirmCardPayment(clientSecret)
              │     └── succès → POST /client/installation-setup (redirect_to_schedule: true)
              │                       └──► Redirect → GET /installation/{uuid}/schedule
              │
              └── Self → Formulaire adresse de livraison
                    POST /client/installation-setup
                    └──► Redirect → GET /client (home, avec flash)

POST /client/installation-setup (store)
  1. Persiste client.properties: installation_mode, delivery_address
  2. Crée Task (type=installation, status=scheduled, user_id=null)
  3. Met à jour client.step → COMPLETED_STEPS
  4. Envoie email (mode self uniquement ; mode technicien : différé après scheduling)
  5. Redirige (schedule ou home)
```

---

## 3. Détail des fichiers créés / modifiés

### 3.1 `app/Http/Controllers/ClientInstallationChoiceController.php` *(NOUVEAU)*

**Deux méthodes** :

- **`show(Request $request)`** — `GET /client/installation-setup`
  - Guard : si `installation_mode` déjà défini → redirect `client.home`
  - Guard : si aucune installation active → redirect `client.home`
  - Lit le montant des frais depuis le sous-produit `Installation Technicien` (`Product::where('property_name', 'installation_mode')->where('default_value', 'technician')`) ; fallback hardcodé : `50 000 centimes = 500 DH`
  - Rend la page Inertia `Client/Operations/InstallationChoice` avec les props : `client`, `installation`, `contract`, `installation_fee`, `installation_fee_currency`

- **`store(Request $request)`** — `POST /client/installation-setup`
  - Validation : `installation_uuid`, `installation_mode` (in:technician,self), `delivery_address` (required_if self), `same_address`, `redirect_to_schedule`
  - Calcule `deliveryAddress` (adresse installation ou adresse personnalisée)
  - `$client->setProperty('installation_mode', $mode)` — stocke via trait `HasProperties`
  - `$client->setProperty('delivery_address', $deliveryAddress)` — pour mode self
  - Crée un enregistrement `Task` :
    ```php
    Task::create([
        'taskable_type' => Installation::class,
        'taskable_uuid' => $installation->uuid,
        'address'       => $installation->address,
        'type'          => 'installation',
        'status'        => 'scheduled',
        'user_id'       => null,   // à assigner par un opérateur
        'notes'         => '...',  // décrit le mode + adresse si self
    ]);
    ```
  - Met à jour `client.step = COMPLETED_STEPS`
  - **Email** : envoyé immédiatement pour mode `self` ; skippé pour `technician` (différé à la planification)
  - **Redirect** : si `redirect_to_schedule && mode === technician` → `installation.schedule` ; sinon → `client.home` avec flash

---

### 3.2 `resources/js/Pages/Client/Operations/InstallationChoice.vue` *(NOUVEAU)*

Page Vue à 2 étapes.

**Props reçues** : `client`, `installation`, `contract`, `installation_fee` (Number), `installation_fee_currency` (String)

**State** :

| Variable | Type | Rôle |
|---|---|---|
| `step` | `ref(0)` | 0 = choix du mode, 1 = paiement/adresse |
| `selectedMode` | `ref(null)` | `'technician'` \| `'self'` |
| `sameAddress` | `ref(true)` | Utiliser l'adresse de l'installation |
| `customAddress` | `ref('')` | Adresse de livraison personnalisée |
| `stripe`, `cardElement` | let | Instances Stripe.js |
| `stripeReady` | `ref(false)` | Stripe Elements monté |
| `stripePaymentSuccess` | `ref(false)` | Paiement confirmé côté Stripe |
| `stripeProcessing` | `ref(false)` | Paiement en cours |
| `clientSecret` | `ref('')` | Client secret Stripe |
| `form` | `useForm(...)` | Formulaire Inertia pour le POST final |

**Flux Stripe (mode technicien)** :
1. `goToStep2()` → `initStripe()` : appel `POST /api/payments/installation-fee/init` → récupère `client_secret` + `stripe_public_key` → monte `card` element
2. `payAndStore()` : `stripe.confirmCardPayment(clientSecret)` → si `succeeded` : `stripePaymentSuccess = true` → `setTimeout(() => submitChoice(true), 1800)` → POST Inertia avec `redirect_to_schedule: true`

**Flux self-install** :
1. `goToStep2()` → affiche formulaire adresse
2. `storeSelf()` → validation adresse → `submitChoice(false)` → POST Inertia

---

### 3.3 `app/Http/Controllers/ClientController.php` *(MODIFIÉ)*

**Méthode `home()`** :

```php
// Onboarding intercept
if (
    $client->step->value === ClientStep::SCHEDULE_STEP->value
    && !$client->getProperty('installation_mode')
) {
    return redirect()->route('client.installation-setup');
}
```

**Nouvelles props passées à `Client/Home`** :

| Prop | Valeur | Description |
|---|---|---|
| `installation_mode` | `$client->getProperty('installation_mode')` | null / `'technician'` / `'self'` |
| `has_active_panel` | bool | Au moins un `InstallationDevice` alarm panel avec `last_heartbeat_at` |
| `has_scheduled_technician` | bool | Mode technicien ET `installations.scheduled_date` non nul |

**Logique `has_active_panel`** :
```php
$hasActivePanel = InstallationDevice::alarmPanels()
    ->whereHas('task', fn($q) =>
        $q->whereHas('taskable', fn($q2) => $q2->where('client_uuid', $client->uuid))
          ->where('taskable_type', Installation::class)
    )
    ->whereHas('properties', fn($q) =>
        $q->where('property', 'last_heartbeat_at')->whereNotNull('value')
    )
    ->exists();
```

---

### 3.4 `app/Services/PaymentService.php` *(MODIFIÉ)*

**Nouvelle méthode `initializeInstallationFeePayment(string $clientUuid, string $contractUuid): array`** :
- Vérifie client/contrat et leur appartenance mutuelle
- Lit le montant depuis `Product` (sub-produit `Installation Technicien`, `caution_price_cents`), fallback 50 000 centimes
- Crée un enregistrement `Payment` avec `notes = 'installation_fee'` et `status = 'pending'`
- Appelle `$provider->createPaymentIntent(...)` avec `payment_type = 'installation_fee'` dans les métadonnées
- Retourne `client_secret`, `payment_intent_id`, `stripe_public_key`

---

### 3.5 `app/Http/Controllers/PaymentController.php` *(MODIFIÉ)*

**Nouvelle action `initializeInstallationFeePayment(Request $request)`** — `POST /api/payments/installation-fee/init` :
- Validation : `client_uuid` (exists:clients,uuid), `contract_uuid` (exists:contracts,uuid)
- Délègue à `$this->paymentService->initializeInstallationFeePayment(...)`
- Retourne JSON avec `client_secret` et `stripe_public_key`

---

### 3.6 `app/Providers/Payment/StripeProvider.php` *(MODIFIÉ)*

**`createPaymentIntent()`** : passe désormais `payment_type` dans les métadonnées Stripe du `PaymentIntent` (champ `metadata.payment_type`).

**`handlePaymentIntentSucceeded()`** : lit `$paymentIntent->metadata->payment_type`. Si `=== 'installation_fee'` : met à jour uniquement le statut `Payment` → `successful`, puis **sort immédiatement** (early return) sans toucher au contrat ni au statut client. Ce comportement évite de ré-écraser un contrat ou un client déjà actifs lors d'un second paiement.

---

### 3.7 `app/Notifications/InstallationChoiceNotification.php` *(NOUVEAU)*

Étend `BaseNotification`. Canal : `mail` uniquement. Queue : `emails`.

**Champs** :

| Champ | Type | Description |
|---|---|---|
| `clientName` | string | Nom complet du client |
| `installationMode` | string | `'technician'` \| `'self'` |
| `deliveryAddress` | ?string | Adresse livraison (mode self) |
| `installationFeeAmount` | ?float | Montant payé (mode technicien) |
| `currency` | string | Devise (défaut MAD) |
| `scheduledDate` | ?string | Date planifiée (mode technicien après scheduling) |
| `scheduledTime` | ?string | Heure planifiée |

**Objet email** :
- Mode technicien : `"Confirmation – Installation par un technicien Axontis"`
- Mode self : `"Confirmation – Votre matériel sera livré chez vous"`

**⚠️ Bug fixé** : `App\Models\Client` n'a **pas** le trait `Notifiable`. La notification doit être envoyée via `$client->user->notify(...)` (le modèle `User` a `Notifiable`).

---

### 3.8 `resources/views/emails/installation-choice.blade.php` *(NOUVEAU)*

Template Markdown Blade. Deux branches `@if($installationMode === 'technician')` / `@else`.

**Mode technicien** :
- Récapitulatif (blockquote `>`) : mode, montant payé, date si disponible (`$scheduledDate`)
- Prochaines étapes différentes selon qu'une date est planifiée ou non

**Mode self** :
- Récapitulatif (blockquote `>`) : mode, adresse de livraison
- Prochaines étapes : préparation commande, envoi colis, guide inclus

**⚠️ Décision technique** : Utilisation de blockquotes Markdown (`>`) au lieu du composant `@component('mail::panel')` pour les récapitulatifs. Ce composant génère du HTML tabulaire (`</td></tr></table>`) qui "fuit" dans le rendu Markdown quand il contient des listes numérotées.

**Contact support** : `contact@axontis.com` hardcodé — `config('mail.from.address')` retournait `noreply@axontis.net`, adresse non destinée aux clients.

---

### 3.9 `app/Http/Controllers/InstallationController.php` *(MODIFIÉ)*

**Méthode `storeSchedule()`** : après enregistrement réussi de la date planifiée, vérifie si le client est en mode technicien (`$client->getProperty('installation_mode') === 'technician'`). Si oui, envoie `InstallationChoiceNotification` avec `scheduledDate` et `scheduledTime`. C'est le **seul** endroit où l'email de confirmation technicien est envoyé (car il doit contenir la date d'intervention choisie).

---

### 3.10 `app/Http/Middleware/HandleInertiaRequests.php` *(MODIFIÉ)*

**Ajout dans le tableau `share()`** :
```php
'flash' => [
    'success'                     => fn () => $request->session()->get('success'),
    'error'                       => fn () => $request->session()->get('error'),
    'message'                     => fn () => $request->session()->get('message'),
    'installation_choice_success' => fn () => $request->session()->get('installation_choice_success'),
    'installation_mode'           => fn () => $request->session()->get('installation_mode'),
],
```

Les données flash n'étaient pas accessibles dans les composants Vue. Elles le sont maintenant via `usePage().props.flash`.

---

### 3.11 `resources/js/Pages/Client/Operations/Schedule.vue` *(MODIFIÉ)*

- Affiche une **bannière de confirmation de paiement** en haut de page lorsque le client arrive depuis le tunnel de paiement (`page.props.flash.success`)
- Correction de typos SVG préexistantes (`x=` → `x1=`)

---

### 3.12 `resources/js/Pages/Client/Home.vue` *(MODIFIÉ)*

**Nouvelles propriétés calculées** :

| Computed | Condition | Usage |
|---|---|---|
| `installationChoiceSuccess` | `flash.installation_choice_success` | Texte du message flash post-choix |
| `installationMode` | `flash.installation_mode \|\| client.installation_mode` | Mode actuel |
| `hasChosenInstallationMode` | `!!client.installation_mode` | Choix effectué ou non |
| `hasActivePanel` | `!!client.has_active_panel` | Système physiquement en ligne |
| `hasScheduledTechnician` | `!!client.has_scheduled_technician` | Technicien + date planifiée |
| `needsToScheduleTechnician` | `mode === 'technician' && !has_scheduled_technician` | Doit encore planifier |

**Système de bannières multi-état** (toutes mutuellement exclusives) :

| Bannière | Couleur | Condition v-if |
|---|---|---|
| Confirmation choix (✅) | Vert | `installationChoiceSuccess \|\| hasChosenInstallationMode` |
| "Planification en attente" | Ambre | `hasPendingContracts && !hasChosenInstallationMode` |
| "Choisissez votre date technicien" | Bleu pulsant | `needsToScheduleTechnician` |
| Installation planifiée | Vert | `hasScheduledContracts` |
| "Installation en cours de préparation" | Gris | `hasChosenInstallationMode && !needsToScheduleTechnician` et `!hasActivePanel` |
| "Votre système est actif" | Bleu/Violet | `hasActivePanel` |

---

### 3.13 `routes/web.php` *(MODIFIÉ)*

```php
Route::get('/client/installation-setup',  [ClientInstallationChoiceController::class, 'show'])
     ->name('client.installation-setup');
Route::post('/client/installation-setup', [ClientInstallationChoiceController::class, 'store'])
     ->name('client.installation-setup.store');
```

Intégrées dans le groupe `middleware('client.active')` existant.

---

### 3.14 `routes/api.php` *(MODIFIÉ)*

```php
Route::post('/payments/installation-fee/init',
    [PaymentController::class, 'initializeInstallationFeePayment']);
```

---

### 3.15 `database/migrations/2026_03_28_000001_add_installation_fee_sub_product.php` *(NOUVEAU)*

Insère le sous-produit `Installation Technicien` sous `Pack Business` et `Pack Particular` :

| Champ | Valeur |
|---|---|
| `property_name` | `installation_mode` |
| `default_value` | `technician` |
| `caution_price_cents` | `50000` (= 500,00 DH) |
| `subscription_price_cents` | null |
| `device_uuid` | null |

> ⚠️ Cette migration dépend de l'existence des produits parents. Elle doit être jouée après `2026_03_27_154519_add_products.php`.

---

## 4. Décisions d'architecture

### 4.1 Montant non hardcodé

Le montant de 500 DH est toujours lu depuis la table `products` via le sous-produit `Installation Technicien`. Cela permet de modifier le tarif depuis la base de données sans changer le code.

### 4.2 `HasProperties` au lieu de colonnes

`installation_mode` et `delivery_address` sont stockés comme propriétés clé-valeur polymorphiques via le trait `HasProperties`, **pas** comme colonnes directes sur `clients`. S'aligne avec l'architecture existante.

### 4.3 `$client->user->notify()` — jamais `$client->notify()`

Le modèle `Client` **n'a pas** le trait `Notifiable`. Seul `User` l'a. Toutes les notifications passent par `$client->user->notify(...)`.

### 4.4 Email technicien différé

L'email de confirmation technicien est envoyé **après** la planification de la date (dans `storeSchedule()`), pas lors du choix initial — car il doit contenir la date et l'heure d'intervention.

### 4.5 Webhook Stripe : `payment_type` dans les métadonnées

`metadata.payment_type = 'installation_fee'` déclenche un early return dans `handlePaymentIntentSucceeded()` : seul le `Payment` est mis à jour, sans toucher au `Contract` ni au `Client`.

### 4.6 `Task` sans technicien assigné

La `Task` créée lors du choix a `user_id = null`. Elle est visible dans le back-office pour qu'un opérateur assigne un technicien disponible.

---

## 5. Bugs corrigés

### Bug 1 : Balises HTML brutes dans les emails (`mail::panel`)

**Symptôme** : `</td></tr></table>` apparaissaient dans le corps des emails.  
**Cause** : `@component('mail::panel')` avec des listes numérotées génère des conflits de rendu Markdown/HTML.  
**Fix** : Remplacement par des blockquotes Markdown natifs (`>`).

### Bug 2 : Flash data absentes dans Vue

**Symptôme** : `usePage().props.flash` était vide après un redirect avec `->with(...)`.  
**Cause** : `HandleInertiaRequests::share()` ne partageait pas les clés flash.  
**Fix** : Ajout des 5 clés flash dans le middleware.

### Bug 3 : `BadMethodCallException: Call to undefined method App\Models\Client::notify()`

**Cause** : `Client` n'implémente pas `Notifiable`.  
**Fix** : `$client->user->notify(...)` avec guard `if ($client->user)`.

### Bug 4 : Banner "Planification en attente" affiché après le choix

**Symptôme** : Après avoir choisi son mode, le client voyait encore le banner ambre "en attente".  
**Cause** : La condition ne vérifiait pas `hasChosenInstallationMode`.  
**Fix** : `v-if="hasPendingContracts && !hasChosenInstallationMode"`.

### Bug 5 : Banner "Système actif" affiché sans système installé

**Symptôme** : "Votre système de sécurité est actif" s'affichait alors que le matériel n'était pas encore livré.  
**Fix** : Conditionné sur `hasActivePanel` (présence de `last_heartbeat_at` sur un `InstallationDevice` alarm panel).

---

## 6. Inventaire complet des fichiers

| Fichier | Statut |
|---|---|
| `app/Http/Controllers/ClientInstallationChoiceController.php` | **NOUVEAU** |
| `app/Http/Controllers/ClientController.php` | **MODIFIÉ** |
| `app/Http/Controllers/InstallationController.php` | **MODIFIÉ** |
| `app/Http/Controllers/PaymentController.php` | **MODIFIÉ** |
| `app/Services/PaymentService.php` | **MODIFIÉ** |
| `app/Providers/Payment/StripeProvider.php` | **MODIFIÉ** |
| `app/Http/Middleware/HandleInertiaRequests.php` | **MODIFIÉ** |
| `app/Notifications/InstallationChoiceNotification.php` | **NOUVEAU** |
| `resources/views/emails/installation-choice.blade.php` | **NOUVEAU** |
| `resources/js/Pages/Client/Operations/InstallationChoice.vue` | **NOUVEAU** |
| `resources/js/Pages/Client/Operations/Schedule.vue` | **MODIFIÉ** |
| `resources/js/Pages/Client/Home.vue` | **MODIFIÉ** |
| `routes/web.php` | **MODIFIÉ** |
| `routes/api.php` | **MODIFIÉ** |
| `database/migrations/2026_03_28_000001_add_installation_fee_sub_product.php` | **NOUVEAU** |

---

## 7. Points de vigilance pour la suite

1. **Migration** : Jouer `2026_03_28_000001_add_installation_fee_sub_product.php` après `2026_03_27_154519_add_products.php`. Sans les produits parents, le sous-produit n'est pas inséré (la migration cherche `Pack Business` et `Pack Particular` par nom).

2. **Client sans User** : Le guard `if ($client->user)` est en place dans les deux endroits qui envoient des notifications. Si un client arrive à `COMPLETED_STEPS` sans `User` associé, l'email est silencieusement skippé. Pas de crash.

3. **Devise Stripe** : Le code utilise `$contract->currency ?? 'MAD'`. Vérifier que Stripe est configuré pour accepter `MAD` côté dashboard Stripe (certains comptes n'ont pas toutes les devises activées).

4. **`redirect_to_schedule` sans date d'installation** : Si `pendingContracts` est vide au moment où le banner "Choisissez votre date" s'affiche dans `Home.vue`, les liens `v-for="contract in pendingContracts"` ne s'affichent pas. Cas rare mais à vérifier si le statut du contrat change entre le choix et le retour sur la home.

5. **Email technicien sans `$feeAmount`** : Si le sous-produit `Installation Technicien` est supprimé de la base, `$feeAmount` tombe sur le fallback `50000 / 100 = 500`. Le template email affichera `500,00 MAD`. Non critique.

6. **`translatedFormat`** : La date dans l'email est formatée avec `\Carbon\Carbon::parse($scheduledDate)->translatedFormat('l d F Y')`. Requires que la locale Carbon soit configurée en français dans l'application (`Carbon::setLocale('fr')`).

