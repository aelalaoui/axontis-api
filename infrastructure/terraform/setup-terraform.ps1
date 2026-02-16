# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                    AXONTIS - SETUP TERRAFORM                                 ║
# ╚══════════════════════════════════════════════════════════════════════════════╝
#
# Ce script initialise la configuration Terraform pour Vultr
# Usage: .\setup-terraform.ps1

param(
    [Parameter(Mandatory=$false)]
    [string]$VultrApiKey
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                    AXONTIS - CONFIGURATION TERRAFORM                         ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Vérification de Terraform
Write-Host "🔍 Vérification de Terraform..." -ForegroundColor Yellow
try {
    $terraformVersion = terraform --version 2>$null
    Write-Host "✅ Terraform trouvé: $($terraformVersion -split "`n" | Select-Object -First 1)" -ForegroundColor Green
} catch {
    Write-Host "❌ Terraform n'est pas installé. Installation..." -ForegroundColor Red
    winget install Hashicorp.Terraform --accept-package-agreements --accept-source-agreements
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
}

# Vérification de la clé SSH
$sshKeyPath = "$env:USERPROFILE\.ssh\axontis-vultr.pub"
Write-Host ""
Write-Host "🔑 Vérification de la clé SSH..." -ForegroundColor Yellow

if (Test-Path $sshKeyPath) {
    $sshPublicKey = Get-Content $sshKeyPath -Raw
    $sshPublicKey = $sshPublicKey.Trim()
    Write-Host "✅ Clé SSH trouvée: $sshKeyPath" -ForegroundColor Green
} else {
    Write-Host "⚠️  Clé SSH non trouvée. Création..." -ForegroundColor Yellow
    ssh-keygen -t ed25519 -C "axontis-vultr" -f "$env:USERPROFILE\.ssh\axontis-vultr" -N '""'
    $sshPublicKey = Get-Content $sshKeyPath -Raw
    $sshPublicKey = $sshPublicKey.Trim()
    Write-Host "✅ Clé SSH créée" -ForegroundColor Green
}

# API Key Vultr
Write-Host ""
Write-Host "🔐 Configuration de l'API Key Vultr..." -ForegroundColor Yellow

if (-not $VultrApiKey) {
    Write-Host ""
    Write-Host "📋 Pour obtenir votre API Key Vultr:" -ForegroundColor White
    Write-Host "   1. Connectez-vous à https://my.vultr.com" -ForegroundColor Gray
    Write-Host "   2. Allez dans Settings > API" -ForegroundColor Gray
    Write-Host "   3. Cliquez sur 'Enable API' si nécessaire" -ForegroundColor Gray
    Write-Host "   4. Copiez la clé API" -ForegroundColor Gray
    Write-Host ""
    $VultrApiKey = Read-Host "Entrez votre API Key Vultr"
}

if ([string]::IsNullOrWhiteSpace($VultrApiKey)) {
    Write-Host "❌ API Key requise. Abandon." -ForegroundColor Red
    exit 1
}

# Création du fichier terraform.tfvars
$tfvarsPath = Join-Path $PSScriptRoot "terraform.tfvars"
Write-Host ""
Write-Host "📝 Création de terraform.tfvars..." -ForegroundColor Yellow

$tfvarsContent = @"
# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                         TERRAFORM VARIABLES                                   ║
# ╚══════════════════════════════════════════════════════════════════════════════╝
# ⚠️  NE JAMAIS COMMITER CE FICHIER ⚠️

# VULTR API
vultr_api_key = "$VultrApiKey"

# VPS CONFIGURATION
region   = "cdg"           # Paris
plan     = "vc2-1c-1gb"    # 1 CPU, 1GB RAM, ~6$/mois
hostname = "axontis-prod"
label    = "Axontis Production"

# DOMAINE
domain = "axontis.net"

# SSH
ssh_public_key = "$sshPublicKey"

# ENVIRONNEMENT
environment = "production"
tags        = ["axontis", "laravel", "production"]
"@

Set-Content -Path $tfvarsPath -Value $tfvarsContent -Encoding UTF8
Write-Host "✅ terraform.tfvars créé" -ForegroundColor Green

# Initialisation de Terraform
Write-Host ""
Write-Host "🚀 Initialisation de Terraform..." -ForegroundColor Yellow
Push-Location $PSScriptRoot
try {
    terraform init
    Write-Host ""
    Write-Host "✅ Terraform initialisé avec succès!" -ForegroundColor Green
} finally {
    Pop-Location
}

# Résumé
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    CONFIGURATION TERMINÉE !                                  ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Prochaines étapes:" -ForegroundColor White
Write-Host "   1. Vérifiez terraform.tfvars" -ForegroundColor Gray
Write-Host "   2. cd infrastructure\terraform" -ForegroundColor Gray
Write-Host "   3. terraform plan        # Prévisualiser les changements" -ForegroundColor Gray
Write-Host "   4. terraform apply       # Créer l'infrastructure" -ForegroundColor Gray
Write-Host ""
Write-Host "💡 Conseil: Conservez votre clé SSH privée en lieu sûr:" -ForegroundColor Yellow
Write-Host "   $env:USERPROFILE\.ssh\axontis-vultr" -ForegroundColor Gray
Write-Host ""
