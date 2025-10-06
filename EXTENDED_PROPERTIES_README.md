# Properties System - Documentation

## Vue d'ensemble

Le système de propriétés permet d'ajouter des propriétés dynamiques à n'importe quel modèle Eloquent sans modifier la structure de la base de données principale. **Le système utilise les UUID pour les relations polymorphes.**

## Structure

### Table `properties`
- `id` : Clé primaire
- `extendable_type` : Type du modèle parent (polymorphe)
- `extendable_id` : **UUID du modèle parent (polymorphe)** - String(36)
- `property` : Nom de la propriété
- `value` : Valeur stockée sous forme de texte
- `type` : Type de données (string, integer, float, boolean, array, date)
- `created_at`, `updated_at` : Timestamps

### Modèle `Property`
Le modèle `App\Models\Property` gère les propriétés étendues et utilise la table `properties`.

### Index créés
- Index polymorphe sur `extendable_type` et `extendable_id` (UUID)
- Index sur `property`
- Index sur `type`
- Index composé `properties_lookup` pour les requêtes optimisées
- Index composé `properties_property_type` pour les requêtes par propriété et type
- Index unique `properties_unique` pour éviter les doublons de propriétés **basé sur UUID**

### ⚠️ Prérequis important
Les modèles utilisant ce système **DOIVENT** avoir le trait `HasUuid` et une colonne `uuid` pour fonctionner correctement.

## Utilisation

### 1. Ajouter le trait à un modèle

```php
use App\Traits\HasProperties;

class Client extends Model
{
    use HasProperties;
    // ...
}
```

### 2. Définir des propriétés étendues

```php
$client = Client::find(1);

// Définir une propriété simple
$client->setExtendedProperty('preferred_language', 'fr');

// Définir avec un type spécifique
$client->setExtendedProperty('max_budget', 5000, 'integer');

// Types automatiquement détectés
$client->setExtendedProperty('is_vip', true); // boolean
$client->setExtendedProperty('tags', ['premium', 'loyal']); // array
$client->setExtendedProperty('last_contact', now()); // date
```

### 3. Récupérer des propriétés étendues

```php
// Récupérer une propriété avec son type correct
$language = $client->getExtendedProperty('preferred_language'); // string: "fr"
$budget = $client->getExtendedProperty('max_budget'); // int: 5000
$isVip = $client->getExtendedProperty('is_vip'); // bool: true
$tags = $client->getExtendedProperty('tags'); // array: ['premium', 'loyal']

// Avec valeur par défaut
$theme = $client->getExtendedProperty('theme', 'default');

// Récupérer la valeur brute (string)
$rawBudget = $client->getExtendedPropertyRaw('max_budget'); // "5000"

// Récupérer le type
$budgetType = $client->getExtendedPropertyType('max_budget'); // "integer"
```

### 4. Vérifier et gérer les propriétés

```php
// Vérifier l'existence
if ($client->hasExtendedProperty('preferred_language')) {
    // La propriété existe
}

// Supprimer une propriété
$client->removeExtendedProperty('old_property');

// Récupérer toutes les propriétés
$allProperties = $client->getAllExtendedProperties();
// Résultat: ['preferred_language' => 'fr', 'max_budget' => 5000, ...]

// Récupérer avec les types
$allWithTypes = $client->getAllExtendedPropertiesWithTypes();
// Résultat: [
//   'preferred_language' => ['value' => 'fr', 'type' => 'string', 'raw_value' => 'fr'],
//   'max_budget' => ['value' => 5000, 'type' => 'integer', 'raw_value' => '5000']
// ]
```

### 5. Opérations en lot

```php
// Définir plusieurs propriétés à la fois
$client->setExtendedProperties([
    'preferred_language' => 'fr',
    'max_budget' => 5000,
    'is_vip' => true,
    'tags' => ['premium', 'loyal']
]);

// Avec types spécifiques
$client->setExtendedProperties([
    'score' => ['value' => 95.5, 'type' => 'float'],
    'notes' => ['value' => 'Client important', 'type' => 'string']
]);

// Vider toutes les propriétés étendues
$client->clearExtendedProperties();
```

### 6. Requêtes avancées

```php
// Propriétés par type
$booleanProps = $client->getExtendedPropertiesByType('boolean');

// Recherche par pattern
$languageProps = $client->searchExtendedProperties('%language%');

// Requêtes avec Eloquent
$clientsWithVIP = Client::whereHas('extendedProperties', function ($query) {
    $query->where('property', 'is_vip')
          ->where('value', '1');
})->get();
```

## Exemples pratiques

### Client avec propriétés métier

```php
$client = Client::create([
    'email' => 'john@example.com',
    'country' => 'France'
]);

// Le système utilise automatiquement l'UUID du client
// Pas besoin de spécifier l'ID - l'UUID est utilisé automatiquement
$client->setExtendedProperties([
    'subscription_type' => 'premium',
    'max_monthly_budget' => 2000,
    'preferred_contact_time' => '09:00-18:00',
    'marketing_consent' => true,
    'referral_source' => 'google_ads',
    'custom_discount_rate' => 15.5,
    'special_requirements' => ['accessibility', 'multilingual'],
    'onboarding_completed_at' => now()
]);
```

### Recherche et filtrage avec UUID

```php
// Trouver tous les clients VIP - utilise automatiquement les UUID
$vipClients = Client::whereHas('extendedProperties', function ($query) {
    $query->where('property', 'subscription_type')
          ->where('value', 'premium');
})->get();

// Requête directe avec UUID spécifique
$clientUuid = '123e4567-e89b-12d3-a456-426614174000';
$properties = Property::where('extendable_type', 'App\\Models\\Client')
    ->where('extendable_id', $clientUuid)
    ->get();
```

### Migration de données existantes (si nécessaire)

```php
// Si vous migrez depuis un système basé sur ID classiques
// Cette approche permet de convertir les anciennes données
use App\Models\Property;
use App\Models\Client;

// Exemple de script de migration
Client::chunk(100, function ($clients) {
    foreach ($clients as $client) {
        // Mettre à jour les propriétés existantes qui utilisaient l'ancien ID
        Property::where('extendable_type', 'App\\Models\\Client')
            ->where('extendable_id', $client->id) // ancien ID numérique
            ->update(['extendable_id' => $client->uuid]); // nouveau UUID
    }
});
```

## Types de données supportés

- **string** : Texte simple (par défaut)
- **integer/int** : Nombres entiers
- **float/double** : Nombres décimaux
- **boolean/bool** : Valeurs booléennes
- **array/json** : Tableaux et objets JSON
- **date** : Dates Carbon

## Performances avec UUID

- **Index optimisés** : Les UUID (36 caractères) sont indexés efficacement
- **Relations polymorphes** : Utilisation native d'Eloquent avec UUID
- **Requêtes rapides** : Index composés sur `extendable_type` + `extendable_id` (UUID) + `property`
- **Pas de contraintes de clés étrangères** : Les UUID permettent plus de flexibilité

## Bonnes pratiques avec UUID

1. **Modèles compatibles** : Assurez-vous que vos modèles utilisent le trait `HasUuid`
2. **Validation UUID** : Les UUID sont automatiquement générés et validés
3. **Performance** : Les requêtes utilisent automatiquement les UUID pour les relations
4. **Cohérence** : Tous les modèles utilisant ce système doivent avoir des UUID
5. **Migration** : Si vous migrez depuis des ID classiques, planifiez la conversion des données existantes

## Compatibilité

✅ **Compatible avec :**
- Modèles utilisant `HasUuid` trait
- Relations polymorphes basées sur UUID
- Recherches et filtres complexes
- Opérations en lot

❌ **Non compatible avec :**
- Modèles sans UUID
- Relations basées uniquement sur ID auto-incrémentés
- Anciennes structures utilisant des ID numériques classiques
