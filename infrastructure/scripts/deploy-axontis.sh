#!/bin/bash
# =============================================================================
# Script de déploiement Axontis
# Usage: deploy-axontis.sh [branch]
# =============================================================================

set -e

# Configuration
APP_PATH="/var/www/axontis"
BACKUP_PATH="/var/backups/axontis"
BRANCH="${1:-main}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "🚀 DÉPLOIEMENT AXONTIS"
echo "📅 $(date)"
echo "🌿 Branche: $BRANCH"
echo -e "==========================================${NC}"

cd $APP_PATH

# ============================================
# 1. VÉRIFICATION DE L'ÉTAT GIT
# ============================================
echo -e "\n${YELLOW}📋 Vérification de l'état Git...${NC}"
CURRENT_COMMIT=$(git rev-parse --short HEAD)
echo "Commit actuel: $CURRENT_COMMIT"

# Vérifier les modifications locales
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}⚠️  Modifications locales détectées!${NC}"
    git status --short
    read -p "Continuer et écraser les modifications? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Déploiement annulé."
        exit 1
    fi
fi

# ============================================
# 2. BACKUP
# ============================================
echo -e "\n${YELLOW}📦 Création du backup...${NC}"
mkdir -p $BACKUP_PATH

# Backup de la base de données (utilise les credentials du .env)
echo "Backup de la base de données..."
if [ -f "$APP_PATH/.env" ]; then
    DB_USER=$(grep -E "^DB_USERNAME=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep -E "^DB_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep -E "^DB_DATABASE=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_${TIMESTAMP}.sql" 2>/dev/null || true
fi

# Backup des fichiers
tar -czf "$BACKUP_PATH/files_${TIMESTAMP}_${CURRENT_COMMIT}.tar.gz" \
    -C /var/www axontis \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='vendor' \
    --exclude='node_modules' 2>/dev/null || true

echo -e "${GREEN}✅ Backup créé: files_${TIMESTAMP}_${CURRENT_COMMIT}.tar.gz${NC}"

# Nettoyer les vieux backups (garder les 5 derniers)
cd $BACKUP_PATH
ls -t files_*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm --
ls -t db_*.sql 2>/dev/null | tail -n +6 | xargs -r rm --

# ============================================
# 3. MODE MAINTENANCE
# ============================================
echo -e "\n${YELLOW}🔧 Activation du mode maintenance...${NC}"
cd $APP_PATH
php artisan down --retry=60 --refresh=5

# ============================================
# 4. PULL DU CODE
# ============================================
echo -e "\n${YELLOW}📥 Récupération du code...${NC}"
git fetch origin
git reset --hard origin/$BRANCH
NEW_COMMIT=$(git rev-parse --short HEAD)
echo -e "${GREEN}✅ Nouveau commit: $NEW_COMMIT${NC}"

# ============================================
# 5. INSTALLATION DES DÉPENDANCES
# ============================================
echo -e "\n${YELLOW}📦 Installation des dépendances...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# ============================================
# 6. MIGRATIONS
# ============================================
echo -e "\n${YELLOW}🗄️ Exécution des migrations...${NC}"
php artisan migrate --force

# ============================================
# 7. OPTIMISATIONS
# ============================================
echo -e "\n${YELLOW}⚡ Optimisation de Laravel...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ============================================
# 8. PERMISSIONS
# ============================================
echo -e "\n${YELLOW}🔒 Configuration des permissions...${NC}"
chown -R www-data:www-data $APP_PATH
chmod -R 755 $APP_PATH
chmod -R 775 $APP_PATH/storage
chmod -R 775 $APP_PATH/bootstrap/cache

# ============================================
# 9. REDÉMARRAGE DES SERVICES
# ============================================
echo -e "\n${YELLOW}🔄 Redémarrage des services...${NC}"
systemctl reload php8.3-fpm
php artisan queue:restart
supervisorctl restart axontis-worker:* 2>/dev/null || true

# ============================================
# 10. DÉSACTIVATION DU MODE MAINTENANCE
# ============================================
echo -e "\n${YELLOW}✅ Désactivation du mode maintenance...${NC}"
php artisan up

# ============================================
# 11. VÉRIFICATION
# ============================================
echo -e "\n${YELLOW}🔍 Vérification...${NC}"

# Test HTTP
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    echo -e "${GREEN}✅ Application accessible (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️ L'application répond avec HTTP $HTTP_CODE${NC}"
fi

# Afficher les logs récents
echo -e "\n${YELLOW}📜 Dernières lignes de log:${NC}"
tail -5 $APP_PATH/storage/logs/laravel.log 2>/dev/null || echo "Pas de logs"

echo -e "\n${GREEN}=========================================="
echo "🎉 DÉPLOIEMENT TERMINÉ!"
echo "📌 $CURRENT_COMMIT → $NEW_COMMIT"
echo -e "==========================================${NC}"
