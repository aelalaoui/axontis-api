# 🚀 Script de configuration rapide du système de paiement Stripe
# PowerShell version

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Configuration du système de paiement" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier si Stripe PHP est installé
Write-Host "📦 Vérification des dépendances..." -ForegroundColor Yellow

if (-not (Test-Path "vendor\stripe")) {
    Write-Host "Installation de stripe/stripe-php..." -ForegroundColor Yellow
    composer require stripe/stripe-php
} else {
    Write-Host "✓ stripe/stripe-php installé" -ForegroundColor Green
}

# 2. Vérifier si @stripe/stripe-js est installé
if (-not (Test-Path "node_modules\@stripe")) {
    Write-Host "Installation de @stripe/stripe-js..." -ForegroundColor Yellow
    npm install @stripe/stripe-js
} else {
    Write-Host "✓ @stripe/stripe-js installé" -ForegroundColor Green
}

Write-Host ""

# 3. Vérifier les variables d'environnement
Write-Host "🔑 Vérification des variables d'environnement..." -ForegroundColor Yellow

if (-not (Select-String -Path ".env" -Pattern "STRIPE_PUBLIC_KEY" -Quiet)) {
    Write-Host "⚠ STRIPE_PUBLIC_KEY non configuré" -ForegroundColor Yellow
    Write-Host "Ajoutez dans votre fichier .env :" -ForegroundColor White
    Write-Host ""
    Write-Host "STRIPE_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx" -ForegroundColor Cyan
    Write-Host "STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxx" -ForegroundColor Cyan
    Write-Host "STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host "✓ Variables Stripe configurées" -ForegroundColor Green
}

Write-Host ""

# 4. Exécuter les migrations
Write-Host "🗄️ Exécution des migrations..." -ForegroundColor Yellow
php artisan migrate --path=database/migrations/2025_12_27_222339_add_payment_intent_fields_to_payments_table.php

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Migrations exécutées" -ForegroundColor Green
} else {
    Write-Host "✗ Erreur lors des migrations" -ForegroundColor Red
}

Write-Host ""

# 5. Nettoyer le cache
Write-Host "🧹 Nettoyage du cache..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
composer dump-autoload

Write-Host "✓ Cache nettoyé" -ForegroundColor Green

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Configuration terminée !" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📚 Prochaines étapes :" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Obtenez vos clés Stripe :" -ForegroundColor White
Write-Host "   https://dashboard.stripe.com/test/apikeys" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Configurez le webhook :" -ForegroundColor White
Write-Host "   https://dashboard.stripe.com/test/webhooks" -ForegroundColor Cyan
Write-Host "   URL: https://votre-domaine.com/api/webhooks/stripe" -ForegroundColor Gray
Write-Host "   Événements: payment_intent.succeeded, payment_intent.payment_failed" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Testez avec les cartes de test Stripe :" -ForegroundColor White
Write-Host "   Succès: 4242 4242 4242 4242" -ForegroundColor Green
Write-Host "   Échec:  4000 0000 0000 0002" -ForegroundColor Red
Write-Host ""
Write-Host "📖 Documentation complète : PAYMENT_SYSTEM_README.md" -ForegroundColor Cyan
Write-Host ""

