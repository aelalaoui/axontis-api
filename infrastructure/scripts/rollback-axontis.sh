#!/bin/bash
# =============================================================================
# Script de rollback Axontis
# Usage: rollback-axontis.sh [commit-hash|backup-file]
# =============================================================================

set -e

# Configuration
APP_PATH="/var/www/axontis"
BACKUP_PATH="/var/backups/axontis"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo "⏪ ROLLBACK AXONTIS"
echo "📅 $(date)"
echo -e "==========================================${NC}"

# ============================================
# AFFICHER LES OPTIONS DE ROLLBACK
# ============================================
if [ -z "$1" ]; then
    echo -e "\n${YELLOW}📋 Options de rollback disponibles:${NC}"

    echo -e "\n${BLUE}🔄 Commits Git récents:${NC}"
    cd $APP_PATH
    git log --oneline -10

    echo -e "\n${BLUE}📦 Backups disponibles:${NC}"
    ls -lht $BACKUP_PATH/files_*.tar.gz 2>/dev/null | head -5 || echo "Aucun backup trouvé"

    echo -e "\n${YELLOW}Usage:${NC}"
    echo "  rollback-axontis.sh <commit-hash>     # Rollback vers un commit Git"
    echo "  rollback-axontis.sh <backup-file>     # Restaurer depuis un backup"
    echo ""
    exit 0
fi

TARGET="$1"

# ============================================
# DÉTERMINER LE TYPE DE ROLLBACK
# ============================================
if [ -f "$BACKUP_PATH/$TARGET" ]; then
    ROLLBACK_TYPE="backup"
    echo -e "${YELLOW}📦 Rollback depuis le backup: $TARGET${NC}"
elif [ -f "$TARGET" ]; then
    ROLLBACK_TYPE="backup"
    BACKUP_PATH=$(dirname "$TARGET")
    TARGET=$(basename "$TARGET")
    echo -e "${YELLOW}📦 Rollback depuis le backup: $TARGET${NC}"
else
    ROLLBACK_TYPE="git"
    echo -e "${YELLOW}🔄 Rollback vers le commit: $TARGET${NC}"
fi

# Confirmation
read -p "Confirmer le rollback? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Rollback annulé."
    exit 1
fi

# ============================================
# MODE MAINTENANCE
# ============================================
echo -e "\n${YELLOW}🔧 Activation du mode maintenance...${NC}"
cd $APP_PATH
php artisan down --retry=60

# ============================================
# ROLLBACK
# ============================================
if [ "$ROLLBACK_TYPE" == "git" ]; then
    echo -e "\n${YELLOW}🔄 Rollback Git vers $TARGET...${NC}"
    cd $APP_PATH

    # Sauvegarder le commit actuel au cas où
    CURRENT=$(git rev-parse --short HEAD)
    echo "Commit actuel: $CURRENT"

    git fetch origin
    git reset --hard $TARGET

    echo -e "${GREEN}✅ Rollback Git effectué${NC}"

elif [ "$ROLLBACK_TYPE" == "backup" ]; then
    echo -e "\n${YELLOW}📦 Restauration depuis backup...${NC}"

    # Sauvegarder .env
    cp $APP_PATH/.env /tmp/.env.rollback 2>/dev/null || true

    # Extraire le backup
    cd /var/www
    tar -xzf "$BACKUP_PATH/$TARGET"

    # Restaurer .env
    cp /tmp/.env.rollback $APP_PATH/.env 2>/dev/null || true

    echo -e "${GREEN}✅ Backup restauré${NC}"
fi

# ============================================
# RÉINSTALLATION
# ============================================
echo -e "\n${YELLOW}📦 Réinstallation des dépendances...${NC}"
cd $APP_PATH
composer install --no-dev --optimize-autoloader --no-interaction

# ============================================
# ROLLBACK DE LA BASE DE DONNÉES (optionnel)
# ============================================
echo -e "\n${YELLOW}🗄️ Rollback de la base de données?${NC}"
echo "Backups de base de données disponibles:"
ls -lht $BACKUP_PATH/db_*.sql 2>/dev/null | head -5 || echo "Aucun backup de DB trouvé"

read -p "Restaurer un backup de base de données? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Entrez le nom du fichier de backup:"
    read DB_BACKUP
    if [ -f "$BACKUP_PATH/$DB_BACKUP" ]; then
        echo "Restauration de $DB_BACKUP..."
        # Lire les credentials depuis .env
        if [ -f "$APP_PATH/.env" ]; then
            DB_USER=$(grep -E "^DB_USERNAME=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            DB_PASS=$(grep -E "^DB_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            DB_NAME=$(grep -E "^DB_DATABASE=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
            mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_PATH/$DB_BACKUP"
            echo -e "${GREEN}✅ Base de données restaurée${NC}"
        else
            echo -e "${RED}❌ Fichier .env non trouvé${NC}"
        fi
    else
        echo -e "${RED}❌ Fichier non trouvé${NC}"
    fi
fi

# ============================================
# OPTIMISATIONS
# ============================================
echo -e "\n${YELLOW}⚡ Optimisation...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ============================================
# PERMISSIONS
# ============================================
echo -e "\n${YELLOW}🔒 Permissions...${NC}"
chown -R www-data:www-data $APP_PATH
chmod -R 755 $APP_PATH
chmod -R 775 $APP_PATH/storage
chmod -R 775 $APP_PATH/bootstrap/cache

# ============================================
# REDÉMARRAGE
# ============================================
echo -e "\n${YELLOW}🔄 Redémarrage des services...${NC}"
systemctl reload php8.3-fpm
php artisan queue:restart
supervisorctl restart axontis-worker:* 2>/dev/null || true

# ============================================
# FIN
# ============================================
echo -e "\n${YELLOW}✅ Désactivation du mode maintenance...${NC}"
php artisan up

echo -e "\n${GREEN}=========================================="
echo "🎉 ROLLBACK TERMINÉ!"
echo -e "==========================================${NC}"
