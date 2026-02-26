#!/bin/bash
# =============================================================================
# 🚀 Script de migration vers Laravel Horizon
# =============================================================================
# Ce script migre les workers Supervisor individuels vers Laravel Horizon.
# Horizon gère automatiquement tous les workers (emails, sms, whatsapp, etc.)
#
# Usage: sudo bash migrate-to-horizon.sh
# =============================================================================

set -e

# Configuration
APP_PATH="/var/www/axontis"
SUPERVISOR_CONF="/etc/supervisor/conf.d"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}=============================================="
echo "🚀 MIGRATION VERS LARAVEL HORIZON"
echo "📅 $(date)"
echo -e "==============================================${NC}"

# ============================================
# ÉTAPE 0 : PRÉ-REQUIS
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📋 ÉTAPE 0 : Vérification des pré-requis${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Vérifier PHP
echo -n "  PHP 8.x ........... "
PHP_VERSION=$(php -v | head -1 | awk '{print $2}')
echo -e "${GREEN}✅ $PHP_VERSION${NC}"

# Vérifier ext-pcntl
echo -n "  ext-pcntl ......... "
if php -m | grep -q pcntl; then
    echo -e "${GREEN}✅ installé${NC}"
else
    echo -e "${RED}❌ MANQUANT !${NC}"
    echo -e "${RED}  → Installez : sudo apt install php8.3-pcntl && sudo systemctl restart php8.3-fpm${NC}"
    exit 1
fi

# Vérifier ext-posix
echo -n "  ext-posix ......... "
if php -m | grep -q posix; then
    echo -e "${GREEN}✅ installé${NC}"
else
    echo -e "${RED}❌ MANQUANT !${NC}"
    echo -e "${RED}  → Installez : sudo apt install php8.3-posix && sudo systemctl restart php8.3-fpm${NC}"
    exit 1
fi

# Vérifier Redis
echo -n "  Redis Server ...... "
if systemctl is-active --quiet redis-server; then
    REDIS_VERSION=$(redis-server --version | awk '{print $3}' | cut -d= -f2)
    echo -e "${GREEN}✅ actif ($REDIS_VERSION)${NC}"
else
    echo -e "${RED}❌ Redis n'est pas actif !${NC}"
    echo -e "${RED}  → Démarrez : sudo systemctl start redis-server${NC}"
    exit 1
fi

# Vérifier la connexion Redis depuis Laravel
echo -n "  Laravel ↔ Redis ... "
cd $APP_PATH
REDIS_TEST=$(php artisan tinker --execute="try { \Illuminate\Support\Facades\Redis::ping(); echo 'OK'; } catch (\Exception \$e) { echo 'FAIL:' . \$e->getMessage(); }" 2>/dev/null)
if [[ "$REDIS_TEST" == *"OK"* ]]; then
    echo -e "${GREEN}✅ connexion OK${NC}"
else
    echo -e "${RED}❌ Échec connexion Redis${NC}"
    echo -e "${RED}  → Vérifiez REDIS_HOST, REDIS_PASSWORD dans .env${NC}"
    exit 1
fi

# Vérifier que Horizon est installé
echo -n "  Laravel Horizon ... "
if php artisan horizon:status >/dev/null 2>&1 || [[ $? -eq 1 ]]; then
    echo -e "${GREEN}✅ installé${NC}"
else
    echo -e "${RED}❌ Horizon n'est pas installé !${NC}"
    echo -e "${RED}  → Exécutez d'abord : composer require laravel/horizon${NC}"
    exit 1
fi

# Vérifier QUEUE_CONNECTION=redis
echo -n "  QUEUE_CONNECTION .. "
QUEUE_CONN=$(grep -E "^QUEUE_CONNECTION=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
if [[ "$QUEUE_CONN" == "redis" ]]; then
    echo -e "${GREEN}✅ redis${NC}"
else
    echo -e "${RED}❌ Actuellement '$QUEUE_CONN' — doit être 'redis'${NC}"
    echo -e "${RED}  → Modifiez QUEUE_CONNECTION=redis dans .env${NC}"
    exit 1
fi

echo -e "\n${GREEN}✅ Tous les pré-requis sont satisfaits !${NC}"

# ============================================
# ÉTAPE 1 : ARRÊTER LES ANCIENS WORKERS
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🛑 ÉTAPE 1 : Arrêt des anciens workers Supervisor${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo "  Arrêt des workers existants..."
supervisorctl stop axontis-workers:* 2>/dev/null || true
supervisorctl stop axontis-worker-emails:* 2>/dev/null || true
supervisorctl stop axontis-worker-sms:* 2>/dev/null || true
supervisorctl stop axontis-worker-whatsapp:* 2>/dev/null || true
supervisorctl stop axontis-worker-messaging:* 2>/dev/null || true
supervisorctl stop axontis-worker-default:* 2>/dev/null || true
sleep 2
echo -e "${GREEN}  ✅ Anciens workers arrêtés${NC}"

# ============================================
# ÉTAPE 2 : DÉSACTIVER LES ANCIENNES CONFIGS
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📁 ÉTAPE 2 : Désactivation des anciennes configs Supervisor${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Sauvegarder et désactiver
for conf in axontis-worker.conf axontis-workers.conf; do
    if [ -f "$SUPERVISOR_CONF/$conf" ]; then
        cp "$SUPERVISOR_CONF/$conf" "$SUPERVISOR_CONF/${conf}.pre-horizon-backup"
        mv "$SUPERVISOR_CONF/$conf" "$SUPERVISOR_CONF/${conf}.disabled"
        echo -e "  ${GREEN}✅ $conf → ${conf}.disabled (backup: ${conf}.pre-horizon-backup)${NC}"
    fi
done

# ============================================
# ÉTAPE 3 : INSTALLER LA CONFIG HORIZON
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}⚙️  ÉTAPE 3 : Installation de la config Supervisor pour Horizon${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Copier la config Horizon
cp "$APP_PATH/infrastructure/scripts/axontis-horizon.conf" "$SUPERVISOR_CONF/axontis-horizon.conf"
echo -e "  ${GREEN}✅ axontis-horizon.conf installé${NC}"

# ============================================
# ÉTAPE 4 : RECACHER LA CONFIG LARAVEL
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}⚡ ÉTAPE 4 : Rebuild du cache Laravel${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

cd $APP_PATH
php artisan config:cache
php artisan route:cache
echo -e "  ${GREEN}✅ Cache Laravel reconstruit${NC}"

# ============================================
# ÉTAPE 5 : DÉMARRER HORIZON
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🚀 ÉTAPE 5 : Démarrage de Horizon via Supervisor${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

supervisorctl reread
supervisorctl update
sleep 2
supervisorctl start axontis-horizon 2>/dev/null || true
sleep 5

echo -e "  ${GREEN}✅ Horizon démarré${NC}"

# ============================================
# ÉTAPE 6 : VÉRIFICATION COMPLÈTE
# ============================================
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🔍 ÉTAPE 6 : Vérification complète${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# 6a. Supervisor status
echo -e "\n  ${BLUE}[Supervisor]${NC}"
supervisorctl status | grep -E "axontis" 2>/dev/null || echo "  Aucun processus axontis trouvé"

# 6b. Horizon status
echo -e "\n  ${BLUE}[Horizon Status]${NC}"
cd $APP_PATH
HORIZON_STATUS=$(php artisan horizon:status 2>&1)
echo "  $HORIZON_STATUS"

# 6c. Horizon supervisors
echo -e "\n  ${BLUE}[Horizon Supervisors]${NC}"
php artisan horizon:supervisors 2>&1 | head -20 || echo "  Pas encore de superviseurs (attendez quelques secondes)"

# 6d. Vérifier l'accès au dashboard
echo -e "\n  ${BLUE}[Dashboard]${NC}"
DASHBOARD_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/horizon/api/stats 2>/dev/null || echo "000")
if [[ "$DASHBOARD_CODE" == "200" ]] || [[ "$DASHBOARD_CODE" == "302" ]] || [[ "$DASHBOARD_CODE" == "401" ]]; then
    echo -e "  ${GREEN}✅ Dashboard Horizon accessible (/horizon) — HTTP $DASHBOARD_CODE${NC}"
else
    echo -e "  ${YELLOW}⚠️ Dashboard HTTP $DASHBOARD_CODE — vérifiez la config Nginx${NC}"
fi

# 6e. Redis queues
echo -e "\n  ${BLUE}[Redis Queues]${NC}"
REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
REDIS_DB=$(grep -E "^REDIS_QUEUE_DB=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
REDIS_DB=${REDIS_DB:-3}

if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
    QUEUE_KEYS=$(redis-cli -a "$REDIS_PASSWORD" -n $REDIS_DB KEYS "*" --no-auth-warning 2>/dev/null | wc -l)
else
    QUEUE_KEYS=$(redis-cli -n $REDIS_DB KEYS "*" 2>/dev/null | wc -l)
fi
echo -e "  Queue Redis DB $REDIS_DB : $QUEUE_KEYS clé(s)"

# ============================================
# RÉSUMÉ FINAL
# ============================================
echo -e "\n${GREEN}=============================================="
echo "🎉 MIGRATION VERS HORIZON TERMINÉE !"
echo "=============================================="
echo ""
echo "  📊 Dashboard  : https://axontis.net/horizon"
echo "  📋 Status     : php artisan horizon:status"
echo "  🔄 Restart    : php artisan horizon:terminate"
echo "  ⏸  Pause      : php artisan horizon:pause"
echo "  ▶  Continue   : php artisan horizon:continue"
echo "  📈 Snapshot   : php artisan horizon:snapshot"
echo ""
echo "  ⚙️  Config Supervisor : $SUPERVISOR_CONF/axontis-horizon.conf"
echo "  📁 Logs Horizon      : $APP_PATH/storage/logs/horizon.log"
echo ""
echo "  🗑  Pour nettoyer les anciennes configs :"
echo "     rm $SUPERVISOR_CONF/axontis-worker.conf.disabled"
echo "     rm $SUPERVISOR_CONF/axontis-workers.conf.disabled"
echo -e "==============================================${NC}"

