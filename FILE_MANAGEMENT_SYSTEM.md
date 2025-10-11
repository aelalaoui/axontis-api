# Système Générique de Gestion des Fichiers - Documentation

## Vue d'ensemble

Ce système fournit une approche cohérente et réutilisable pour la gestion des fichiers dans tous les contrôleurs de l'application. Il comprend un service centralisé, un trait générique, des routes automatisées et un contrôleur dédié.

## Architecture

### 1. FileService (app/Services/FileService.php)
Service centralisé pour toutes les opérations sur les fichiers :
- Upload de fichiers simples et multiples
- Suppression de fichiers (simple et multiple)
- Renommage de fichiers
- Génération d'URLs de téléchargement/visualisation
- Validation des fichiers
- Gestion automatique du stockage (local/cloud)

### 2. ManagesFiles Trait (app/Traits/ManagesFiles.php)
Trait générique qui peut être utilisé dans n'importe quel contrôleur pour ajouter automatiquement les fonctionnalités de gestion des fichiers :

#### Méthodes disponibles :
- `uploadDocument()` - Upload d'un seul document
- `uploadMultipleDocuments()` - Upload multiple
- `deleteDocument()` - Suppression d'un document
- `deleteMultipleDocuments()` - Suppression multiple
- `renameDocument()` - Renommage d'un document
- `downloadDocument()` - Téléchargement d'un document
- `viewDocument()` - Visualisation d'un document
- `getDocuments()` - Liste des documents

### 3. FileController (app/Http/Controllers/FileController.php)
Contrôleur dédié pour la gestion centralisée des fichiers avec interface utilisateur complète.

### 4. FileRouteServiceProvider (app/Providers/FileRouteServiceProvider.php)
Service provider qui enregistre des macros de routes pour automatiser la création des routes de gestion des fichiers.

## Utilisation

### Dans un contrôleur existant

1. **Ajouter le trait** :
```php
use App\Traits\ManagesFiles;

class MyController extends Controller
{
    use ManagesFiles;
    
    // Vos méthodes existantes...
}
```

2. **Les routes sont automatiquement disponibles** grâce aux macros de routes.

### Enregistrement des routes

#### Routes standards avec gestion des fichiers :
```php
Route::resourceWithFiles('models', ModelController::class);
```

#### Routes de fichiers seulement :
```php
Route::fileRoutes('models', ModelController::class);
```

#### Routes API pour les fichiers :
```php
Route::apiFileRoutes('models', ModelController::class);
```

## Routes générées automatiquement

### Routes Web
Pour un modèle `devices` par exemple :

- `GET /devices/{device}/documents` - Liste des documents
- `POST /devices/{device}/documents/upload` - Upload d'un document
- `POST /devices/{device}/documents/upload-multiple` - Upload multiple
- `DELETE /devices/{device}/documents/delete-multiple` - Suppression multiple
- `GET /devices/{device}/documents/{fileUuid}/download` - Téléchargement
- `GET /devices/{device}/documents/{fileUuid}/view` - Visualisation
- `PATCH /devices/{device}/documents/{fileUuid}/rename` - Renommage
- `DELETE /devices/{device}/documents/{fileUuid}` - Suppression

### Routes API
- `GET /api/devices/{device}/files` - Liste des fichiers (JSON)
- `POST /api/devices/{device}/files` - Upload (JSON)
- `POST /api/devices/{device}/files/multiple` - Upload multiple (JSON)
- `DELETE /api/devices/{device}/files/multiple` - Suppression multiple (JSON)
- `GET /api/devices/{device}/files/{fileUuid}` - Téléchargement (JSON)
- `PATCH /api/devices/{device}/files/{fileUuid}` - Renommage (JSON)
- `DELETE /api/devices/{device}/files/{fileUuid}` - Suppression (JSON)

## Configuration du modèle

Votre modèle doit avoir la relation polymorphe avec les fichiers :

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MyModel extends Model
{
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
    
    // Alias pour les documents spécifiquement
    public function documents(): MorphMany
    {
        return $this->files()->where('type', 'document');
    }
}
```

## Validation des fichiers

Le système utilise des règles de validation prédéfinies :

```php
// Types de fichiers autorisés par défaut
FileService::getDefaultDocumentMimes(); // PDF, DOC, XLS, images etc.

// Règles de validation Laravel
FileService::getFileValidationRules(10240); // Max 10MB par défaut
```

## Exemples d'utilisation

### Upload depuis un formulaire
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'document' => 'nullable|' . FileService::getFileValidationRules(),
    ]);

    $model = MyModel::create($validated);

    if ($request->hasFile('document')) {
        $this->initializeFileService();
        $this->fileService->uploadFile($request->file('document'), $model, 'document');
    }

    return redirect()->back()->with('success', 'Créé avec succès');
}
```

### Upload via API
```php
// Les méthodes du trait gèrent automatiquement les réponses JSON
// si la requête attend du JSON (Content-Type: application/json)
```

## Stockage

Le système s'adapte automatiquement au disque de stockage configuré :
- **Local** : stockage dans `storage/app/public`
- **Cloud (R2)** : génération d'URLs temporaires sécurisées

## Avantages du système générique

1. **Cohérence** : Même interface pour tous les modèles
2. **Réutilisabilité** : Un seul trait à ajouter
3. **Maintenabilité** : Logique centralisée dans le service
4. **Flexibilité** : Support web et API automatique
5. **Sécurité** : Validation et gestion d'erreurs intégrées
6. **Performance** : Gestion optimisée du stockage

## Migration depuis l'ancien système

1. Ajouter le trait `ManagesFiles` dans vos contrôleurs
2. Remplacer les routes manuelles par `Route::resourceWithFiles()`
3. Supprimer les anciennes méthodes de gestion des fichiers
4. Les URLs restent compatibles grâce aux macros de routes

## Personnalisation

Le système peut être étendu facilement :
- Ajouter de nouveaux types de fichiers dans `FileService`
- Modifier les règles de validation
- Personnaliser les chemins de stockage
- Ajouter des transformations d'images

Ce système générique simplifie grandement la gestion des fichiers tout en maintenant la flexibilité nécessaire pour les besoins spécifiques de chaque modèle.
