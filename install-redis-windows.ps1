# ============================================================================
# Script d'installation de Redis pour Windows (développement local)
# Usage: .\install-redis-windows.ps1
# ============================================================================

Write-Host "=========================================="
Write-Host "🔴 Installation de Redis pour Windows" -ForegroundColor Cyan
Write-Host "=========================================="
Write-Host ""

# Vérifier si Redis est déjà installé
Write-Host "🔍 Vérification de Redis..." -ForegroundColor Yellow
$redisInstalled = Get-Command redis-server -ErrorAction SilentlyContinue

if ($redisInstalled) {
    Write-Host "✅ Redis est déjà installé!" -ForegroundColor Green
    Write-Host ""
    redis-server --version
    Write-Host ""

    $choice = Read-Host "Voulez-vous réinstaller Redis? (o/N)"
    if ($choice -ne "o" -and $choice -ne "O") {
        Write-Host "Installation annulée." -ForegroundColor Yellow
        exit 0
    }
}

# Vérifier si Chocolatey est installé
Write-Host ""
Write-Host "🔍 Vérification de Chocolatey..." -ForegroundColor Yellow
$chocoInstalled = Get-Command choco -ErrorAction SilentlyContinue

if (-not $chocoInstalled) {
    Write-Host "❌ Chocolatey n'est pas installé." -ForegroundColor Red
    Write-Host ""
    Write-Host "📥 Installation de Chocolatey..." -ForegroundColor Cyan

    # Installer Chocolatey
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

    # Rafraîchir l'environnement
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    Write-Host "✅ Chocolatey installé avec succès!" -ForegroundColor Green
} else {
    Write-Host "✅ Chocolatey est déjà installé" -ForegroundColor Green
}

# Installer Redis
Write-Host ""
Write-Host "📦 Installation de Redis via Chocolatey..." -ForegroundColor Cyan
choco install redis-64 -y

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Redis installé avec succès!" -ForegroundColor Green
} else {
    Write-Host "❌ Erreur lors de l'installation de Redis" -ForegroundColor Red
    exit 1
}

# Rafraîchir l'environnement
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Démarrer Redis
Write-Host ""
Write-Host "🚀 Démarrage de Redis..." -ForegroundColor Cyan

# Vérifier si le service existe déjà
$redisService = Get-Service -Name Redis -ErrorAction SilentlyContinue

if ($redisService) {
    Start-Service Redis
    Write-Host "✅ Service Redis démarré" -ForegroundColor Green
} else {
    # Démarrer Redis en arrière-plan
    Start-Process -NoNewWindow -FilePath "redis-server.exe"
    Write-Host "✅ Redis démarré en processus d'arrière-plan" -ForegroundColor Green
}

Start-Sleep -Seconds 2

# Tester la connexion
Write-Host ""
Write-Host "🧪 Test de connexion à Redis..." -ForegroundColor Yellow

try {
    $testResult = redis-cli ping
    if ($testResult -eq "PONG") {
        Write-Host "✅ Redis répond correctement (PONG)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Redis répond mais pas avec PONG: $testResult" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Impossible de se connecter à Redis" -ForegroundColor Red
    exit 1
}

# Vérifier l'extension PHP Redis
Write-Host ""
Write-Host "🐘 Vérification de l'extension PHP Redis..." -ForegroundColor Yellow

$phpRedis = php -m | Select-String "redis"

if ($phpRedis) {
    Write-Host "✅ Extension PHP Redis installée" -ForegroundColor Green
} else {
    Write-Host "⚠️  Extension PHP Redis non trouvée" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "📋 Pour installer l'extension PHP Redis:" -ForegroundColor Cyan
    Write-Host "   1. Téléchargez php_redis.dll depuis https://pecl.php.net/package/redis" -ForegroundColor White
    Write-Host "   2. Placez le fichier dans votre dossier PHP ext/" -ForegroundColor White
    Write-Host "   3. Ajoutez 'extension=redis' dans votre php.ini" -ForegroundColor White
    Write-Host "   4. Redémarrez votre serveur web" -ForegroundColor White
    Write-Host ""
    Write-Host "   Ou utilisez XAMPP/WAMP qui incluent souvent Redis" -ForegroundColor White
}

# Afficher les informations
Write-Host ""
Write-Host "=========================================="
Write-Host "✅ Installation terminée!" -ForegroundColor Green
Write-Host "=========================================="
Write-Host ""
Write-Host "📊 Informations Redis:" -ForegroundColor Cyan
redis-cli INFO server | Select-String "redis_version"
Write-Host ""
Write-Host "🔍 Commandes utiles:" -ForegroundColor Cyan
Write-Host "   redis-cli ping             # Tester la connexion" -ForegroundColor White
Write-Host "   redis-cli                  # Ouvrir le CLI" -ForegroundColor White
Write-Host "   redis-cli MONITOR          # Monitorer en temps réel" -ForegroundColor White
Write-Host "   redis-server --service-stop    # Arrêter Redis" -ForegroundColor White
Write-Host "   redis-server --service-start   # Démarrer Redis" -ForegroundColor White
Write-Host ""
Write-Host "📝 Configuration Laravel:" -ForegroundColor Cyan
Write-Host "   Mettez à jour votre fichier .env:" -ForegroundColor White
Write-Host "   CACHE_DRIVER=redis" -ForegroundColor Yellow
Write-Host "   SESSION_DRIVER=redis" -ForegroundColor Yellow
Write-Host "   QUEUE_CONNECTION=redis" -ForegroundColor Yellow
Write-Host "   REDIS_HOST=127.0.0.1" -ForegroundColor Yellow
Write-Host "   REDIS_PASSWORD=null" -ForegroundColor Yellow
Write-Host "   REDIS_PORT=6379" -ForegroundColor Yellow
Write-Host ""
Write-Host "🚀 Prochaines étapes:" -ForegroundColor Cyan
Write-Host "   1. Mettre à jour le .env comme indiqué ci-dessus" -ForegroundColor White
Write-Host "   2. php artisan config:cache" -ForegroundColor White
Write-Host "   3. php artisan cache:clear" -ForegroundColor White
Write-Host "   4. Démarrer votre application" -ForegroundColor White
Write-Host ""
Write-Host "=========================================="
Write-Host ""

# Pause pour lire les informations
Read-Host "Appuyez sur Entrée pour terminer"
