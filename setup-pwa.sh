#!/bin/bash
# Script de configuration PWA pour AXONTIS
# Usage: ./setup-pwa.sh

echo "🚀 Configuration PWA pour AXONTIS"
echo "=================================="
echo ""

# 1. Vérifier Node.js et npm
echo "✓ Vérification de l'environnement..."
if ! command -v node &> /dev/null; then
    echo "❌ Node.js n'est pas installé"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo "❌ npm n'est pas installé"
    exit 1
fi

echo "✓ Node.js: $(node -v)"
echo "✓ npm: $(npm -v)"
echo ""

# 2. Installer les dépendances
echo "📦 Installation des dépendances PWA..."
npm install -D vite-plugin-pwa workbox-build

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de l'installation des dépendances"
    exit 1
fi

echo "✓ Dépendances installées"
echo ""

# 3. Vérifier les fichiers PWA
echo "📋 Vérification des fichiers PWA..."

files=(
    "vite.config.js"
    "public/sw.js"
    "resources/js/components/PWANotification.vue"
    "resources/js/composables/usePWA.js"
    "resources/js/config/pwa.config.js"
    "resources/views/app.blade.php"
    "app/Http/Middleware/PWAHeaders.php"
    "PWA_GUIDE.md"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo "✗ $file (MANQUANT)"
    fi
done

echo ""

# 4. Vérifier les icônes PWA
echo "🎨 Vérification des icônes PWA..."

icons=(
    "public/favicon.ico"
    "public/favicon-16x16.png"
    "public/favicon-32x32.png"
    "public/apple-touch-icon.png"
    "public/pwa-192x192.png"
    "public/pwa-512x512.png"
    "public/screenshot-1.png"
    "public/screenshot-2.png"
)

missing=0
for icon in "${icons[@]}"; do
    if [ -f "$icon" ]; then
        echo "✓ $icon"
    else
        echo "✗ $icon (À ajouter)"
        ((missing++))
    fi
done

if [ $missing -gt 0 ]; then
    echo ""
    echo "⚠️  $missing icône(s) manquante(s)"
    echo "Générez-les avec:"
    echo "  node generate-pwa-icons.js logo.png"
    echo "Ou utilisez un service en ligne:"
    echo "  https://www.favicon-generator.org/"
    echo "  https://pwabuilder.com/"
fi

echo ""

# 5. Récapitulatif
echo "✨ Configuration PWA complétée!"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Ajouter les icônes PWA dans public/"
echo "2. Configurer le middleware PWAHeaders dans Kernel.php"
echo "3. Vérifier que app.blade.php inclut les métadonnées PWA"
echo "4. Exécuter: npm run build"
echo "5. Déployer et vérifier avec Lighthouse"
echo ""
echo "📚 Documentation: PWA_GUIDE.md"
echo ""

