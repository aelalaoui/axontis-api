@echo off
REM ============================================
REM Configuration PWA pour AXONTIS (Windows)
REM ============================================

setlocal enabledelayedexpansion

cls
echo.
echo  ██████╗ ██╗    ██╗ █████╗
echo  ██╔══██╗██║    ██║██╔══██╗
echo  ██████╔╝██║ █╗ ██║███████║
echo  ██╔═══╝ ██║███╗██║██╔══██║
echo  ██║     ╚███╔███╔╝██║  ██║
echo  ╚═╝      ╚══╝╚══╝ ╚═╝  ╚═╝
echo.
echo  Configuration PWA pour AXONTIS
echo  ============================================
echo.

REM 1. Vérifier Node.js
echo [1/5] Vérification de Node.js et npm...
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Node.js n'est pas installé!
    echo Visitez: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

for /f "tokens=*" %%i in ('node -v') do set NODE_VERSION=%%i
for /f "tokens=*" %%i in ('npm -v') do set NPM_VERSION=%%i

echo   ✓ Node.js %NODE_VERSION%
echo   ✓ npm %NPM_VERSION%
echo.

REM 2. Installer les dépendances
echo [2/5] Installation des dépendances PWA...
call npm install -D vite-plugin-pwa workbox-build
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Erreur lors de l'installation des dépendances!
    echo.
    pause
    exit /b 1
)
echo   ✓ Dépendances installées
echo.

REM 3. Vérifier les fichiers PWA
echo [3/5] Vérification des fichiers PWA...
set file_count=0
set missing_count=0

for %%F in (
    "vite.config.js"
    "public/sw.js"
    "resources/js/components/PWANotification.vue"
    "resources/js/composables/usePWA.js"
    "resources/js/config/pwa.config.js"
    "app/Http/Middleware/PWAHeaders.php"
) do (
    if exist %%F (
        echo   ✓ %%F
        set /a file_count+=1
    ) else (
        echo   ✗ %%F (MANQUANT)
        set /a missing_count+=1
    )
)

echo.
if %missing_count% equ 0 (
    echo   ✓ Tous les fichiers PWA présents (%file_count%)
) else (
    echo   ⚠ %missing_count% fichier(s) manquant(s)
)
echo.

REM 4. Vérifier les icônes PWA
echo [4/5] Vérification des icônes PWA...
set icon_count=0
set icon_missing=0

for %%I in (
    "public/favicon.ico"
    "public/favicon-16x16.png"
    "public/favicon-32x32.png"
    "public/apple-touch-icon.png"
    "public/pwa-192x192.png"
    "public/pwa-512x512.png"
    "public/screenshot-1.png"
    "public/screenshot-2.png"
) do (
    if exist %%I (
        echo   ✓ %%I
        set /a icon_count+=1
    ) else (
        echo   ✗ %%I (À ajouter)
        set /a icon_missing+=1
    )
)

echo.
if %icon_missing% equ 0 (
    echo   ✓ Toutes les icônes présentes (%icon_count%)
) else (
    echo   ⚠ %icon_missing% icône(s) manquante(s)
    echo.
    echo   Générez-les avec:
    echo     node generate-pwa-icons.js votre-logo.png
    echo.
    echo   Ou utilisez un service en ligne:
    echo     https://www.favicon-generator.org/
    echo     https://pwabuilder.com/
)
echo.

REM 5. Résumé
echo [5/5] Résumé et prochaines étapes...
echo.
echo ✨ Configuration PWA complétée!
echo.
echo 📝 Prochaines étapes:
echo.
if %icon_missing% gtr 0 (
    echo  1. Ajouter les icônes PWA dans public/
    echo     - Générer: node generate-pwa-icons.js votre-logo.png
    echo     - OU télécharger d'un service en ligne
    echo.
    echo  2. Configurer le middleware PWAHeaders
) else (
    echo  1. Configurer le middleware PWAHeaders
)
echo     - Ouvrir: app/Http/Middleware/Kernel.php
echo     - Ajouter: \App\Http\Middleware\PWAHeaders::class
echo.
echo  3. Ajouter PWANotification au layout
echo     - Importer le composant dans votre layout
echo.
echo  4. Build et test
echo     - npm run dev      (développement)
echo     - npm run build    (production)
echo.
echo  5. Tester dans Chrome
echo     - Ouvrir DevTools (F12)
echo     - Application → Service Workers
echo     - Vérifier "activated and running"
echo.
echo  6. Lighthouse audit
echo     - DevTools → Lighthouse
echo     - Progressive Web App
echo     - Score doit être ≥ 90
echo.
echo 📚 Documentation:
echo     - PWA_README.md (point de départ)
echo     - PWA_GUIDE.md (guide complet)
echo     - PWA_INTEGRATION_CHECKLIST.md (checklist)
echo.
echo 🆘 Support:
echo     - Lire: PWA_DOCUMENTATION_INDEX.md
echo     - Consulter: PWA_GUIDE.md
echo.
echo.

pause

