# Script de configuration PWA pour AXONTIS (Windows)
# Usage: .\setup-pwa.ps1

Write-Host "🚀 Configuration PWA pour AXONTIS" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Green
Write-Host ""

# 1. Vérifier Node.js et npm
Write-Host "✓ Vérification de l'environnement..." -ForegroundColor Yellow

try {
    $nodeVersion = node -v
    $npmVersion = npm -v
    Write-Host "✓ Node.js: $nodeVersion" -ForegroundColor Green
    Write-Host "✓ npm: $npmVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js ou npm n'est pas installé" -ForegroundColor Red
    exit 1
}

Write-Host ""

# 2. Installer les dépendances
Write-Host "📦 Installation des dépendances PWA..." -ForegroundColor Yellow
npm install -D vite-plugin-pwa workbox-build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de l'installation des dépendances" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Dépendances installées" -ForegroundColor Green
Write-Host ""

# 3. Vérifier les fichiers PWA
Write-Host "📋 Vérification des fichiers PWA..." -ForegroundColor Yellow

$files = @(
    "vite.config.js",
    "public/sw.js",
    "resources/js/components/PWANotification.vue",
    "resources/js/composables/usePWA.js",
    "resources/js/config/pwa.config.js",
    "resources/views/app.blade.php",
    "app/Http/Middleware/PWAHeaders.php",
    "PWA_GUIDE.md"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "✓ $file" -ForegroundColor Green
    } else {
        Write-Host "✗ $file (MANQUANT)" -ForegroundColor Red
    }
}

Write-Host ""

# 4. Vérifier les icônes PWA
Write-Host "🎨 Vérification des icônes PWA..." -ForegroundColor Yellow

$icons = @(
    "public/favicon.ico",
    "public/favicon-16x16.png",
    "public/favicon-32x32.png",
    "public/apple-touch-icon.png",
    "public/pwa-192x192.png",
    "public/pwa-512x512.png",
    "public/screenshot-1.png",
    "public/screenshot-2.png"
)

$missing = 0
foreach ($icon in $icons) {
    if (Test-Path $icon) {
        Write-Host "✓ $icon" -ForegroundColor Green
    } else {
        Write-Host "✗ $icon (À ajouter)" -ForegroundColor Yellow
        $missing++
    }
}

if ($missing -gt 0) {
    Write-Host ""
    Write-Host "⚠️  $missing icône(s) manquante(s)" -ForegroundColor Yellow
    Write-Host "Générez-les avec:" -ForegroundColor Yellow
    Write-Host "  node generate-pwa-icons.js logo.png" -ForegroundColor Cyan
    Write-Host "Ou utilisez un service en ligne:" -ForegroundColor Yellow
    Write-Host "  https://www.favicon-generator.org/" -ForegroundColor Cyan
    Write-Host "  https://pwabuilder.com/" -ForegroundColor Cyan
}

Write-Host ""

# 5. Récapitulatif
Write-Host "✨ Configuration PWA complétée!" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Prochaines étapes:" -ForegroundColor Yellow
Write-Host "1. Ajouter les icônes PWA dans public/" -ForegroundColor White
Write-Host "2. Configurer le middleware PWAHeaders dans Kernel.php" -ForegroundColor White
Write-Host "3. Vérifier que app.blade.php inclut les métadonnées PWA" -ForegroundColor White
Write-Host "4. Exécuter: npm run build" -ForegroundColor White
Write-Host "5. Déployer et vérifier avec Lighthouse" -ForegroundColor White
Write-Host ""
Write-Host "📚 Documentation: PWA_GUIDE.md" -ForegroundColor Cyan
Write-Host ""

