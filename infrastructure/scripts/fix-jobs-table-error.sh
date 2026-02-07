#!/bin/bash
# =============================================================================
# Script de correction urgente : Erreur "Table 'jobs' doesn't exist"
# Usage: bash fix-jobs-table-error.sh
# =============================================================================

set -e

APP_PATH="/var/www/axontis"
ENV_FILE="$APP_PATH/.env"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${RED}=========================================="
echo "🚨 CORRECTION URGENTE"
echo "Erreur: Table 'axontis.jobs' doesn't exist"
echo -e "==========================================${NC}\n"

# Vérifier que le fichier .env existe
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Fichier .env introuvable: $ENV_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Diagnostic...${NC}\n"

# Vérifier la configuration actuelle
CURRENT_QUEUE=$(grep -E "^QUEUE_CONNECTION=" "$ENV_FILE" | cut -d'=' -f2 | tr -d ' "' | tr -d "'")
echo "Configuration actuelle: QUEUE_CONNECTION=${CURRENT_QUEUE:-non défini}"

if [ "$CURRENT_QUEUE" = "database" ]; then
    echo -e "${RED}❌ PROBLÈME IDENTIFIÉ: QUEUE_CONNECTION=database${NC}"
    echo -e "${YELLOW}Les workers essaient d'utiliser la table 'jobs' qui n'existe pas!${NC}\n"
else
    echo -e "${YELLOW}⚠️  Configuration semble correcte, mais vérifions...${NC}\n"
fi

# Vérifier que Redis est actif
echo -e "${YELLOW}🔍 Vérification de Redis...${NC}"
if ! systemctl is-active --quiet redis-server; then
    echo -e "${RED}❌ Redis n'est pas actif!${NC}"
    echo "Installez d'abord Redis avec: bash setup-redis-vultr.sh"
    exit 1
fi

# Détecter si Redis nécessite un mot de passe
echo -e "${YELLOW}🔐 Détection du mot de passe Redis...${NC}"
REDIS_HAS_PASSWORD=false
REDIS_SERVER_PASSWORD=""

# Vérifier si requirepass est configuré dans redis.conf
if [ -f /etc/redis/redis.conf ]; then
    REDIS_SERVER_PASSWORD=$(grep "^requirepass" /etc/redis/redis.conf | awk '{print $2}' | head -1)
    if [ -n "$REDIS_SERVER_PASSWORD" ]; then
        REDIS_HAS_PASSWORD=true
        echo -e "${YELLOW}⚠️  Redis nécessite un mot de passe${NC}"
    fi
fi

# Test de connexion Redis
if [ "$REDIS_HAS_PASSWORD" = true ]; then
    if redis-cli -a "$REDIS_SERVER_PASSWORD" ping --no-auth-warning >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Redis est opérationnel (avec mot de passe)${NC}\n"
    else
        echo -e "${RED}❌ Redis ne répond pas avec le mot de passe détecté!${NC}"
        exit 1
    fi
else
    if redis-cli ping >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Redis est opérationnel (sans mot de passe)${NC}\n"
    else
        # Peut-être qu'il y a un mot de passe mais pas dans le fichier
        echo -e "${RED}❌ Redis ne répond pas!${NC}"
        echo "Vérifiez la configuration Redis"
        exit 1
    fi
fi

# Créer une sauvegarde du .env
echo -e "${YELLOW}📦 Sauvegarde du .env...${NC}"
cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
echo -e "${GREEN}✅ Sauvegarde créée${NC}\n"

# Fonction pour mettre à jour ou ajouter une variable
update_env() {
    local key=$1
    local value=$2

    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
        echo -e "${GREEN}  ✓ ${key}=${value}${NC}"
    else
        echo "${key}=${value}" >> "$ENV_FILE"
        echo -e "${GREEN}  + ${key}=${value}${NC}"
    fi
}

echo -e "${YELLOW}🔧 Correction de la configuration...${NC}\n"

# Corriger les drivers pour utiliser Redis
update_env "CACHE_DRIVER" "redis"
update_env "SESSION_DRIVER" "redis"
update_env "QUEUE_CONNECTION" "redis"

# Ajouter les variables Redis si absentes
if ! grep -q "^REDIS_CLIENT=" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Redis Configuration" >> "$ENV_FILE"
fi

update_env "REDIS_CLIENT" "phpredis"
update_env "REDIS_HOST" "127.0.0.1"

# Configurer le mot de passe Redis selon la détection
if [ "$REDIS_HAS_PASSWORD" = true ] && [ -n "$REDIS_SERVER_PASSWORD" ]; then
    update_env "REDIS_PASSWORD" "$REDIS_SERVER_PASSWORD"
    echo -e "${GREEN}  ✓ REDIS_PASSWORD=${REDIS_SERVER_PASSWORD:0:10}... (mot de passe configuré)${NC}"
else
    update_env "REDIS_PASSWORD" "null"
    echo -e "${GREEN}  ✓ REDIS_PASSWORD=null${NC}"
fi

update_env "REDIS_PORT" "6379"
update_env "REDIS_DB" "0"
update_env "REDIS_CACHE_DB" "1"
update_env "REDIS_SESSION_DB" "2"
update_env "REDIS_QUEUE_DB" "3"

echo ""
echo -e "${GREEN}✅ Configuration .env corrigée${NC}\n"

# Appliquer les changements Laravel
echo -e "${YELLOW}⚡ Application des changements Laravel...${NC}"
cd "$APP_PATH"

php artisan config:clear
php artisan config:cache
php artisan cache:clear

echo -e "${GREEN}✅ Cache Laravel mis à jour${NC}\n"

# Redémarrer les workers
echo -e "${YELLOW}🔄 Redémarrage des workers...${NC}"
php artisan queue:restart

# Vérifier la configuration Supervisor
SUPERVISOR_CONFIG="/etc/supervisor/conf.d/axontis-worker.conf"
if [ -f "$SUPERVISOR_CONFIG" ]; then
    WORKER_COMMAND=$(grep "^command=" "$SUPERVISOR_CONFIG" | cut -d'=' -f2)

    if echo "$WORKER_COMMAND" | grep -q "queue:work database"; then
        echo -e "${RED}⚠️  Supervisor utilise encore 'database'!${NC}"
        echo -e "${YELLOW}Correction de la configuration Supervisor...${NC}"

        sed -i 's|queue:work database|queue:work redis|g' "$SUPERVISOR_CONFIG"

        supervisorctl reread
        supervisorctl update
        echo -e "${GREEN}✅ Configuration Supervisor corrigée${NC}"
    fi
fi

supervisorctl restart axontis-worker:* 2>/dev/null || true
echo -e "${GREEN}✅ Workers redémarrés${NC}\n"

# Vérifications finales
echo -e "${YELLOW}🔍 Vérifications finales...${NC}\n"

# Vérifier le .env
echo "1. Configuration .env:"
grep -E "^(CACHE_DRIVER|SESSION_DRIVER|QUEUE_CONNECTION)=" "$ENV_FILE"

echo ""
echo "2. Workers Supervisor:"
supervisorctl status axontis-worker:* 2>/dev/null || echo "Aucun worker configuré"

echo ""
echo "3. Redis:"
if [ "$REDIS_HAS_PASSWORD" = true ]; then
    if redis-cli -a "$REDIS_SERVER_PASSWORD" ping --no-auth-warning >/dev/null 2>&1; then
        CACHE_KEYS=$(redis-cli -a "$REDIS_SERVER_PASSWORD" -n 1 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
        SESSION_KEYS=$(redis-cli -a "$REDIS_SERVER_PASSWORD" -n 2 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
        QUEUE_KEYS=$(redis-cli -a "$REDIS_SERVER_PASSWORD" -n 3 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
        echo -e "${GREEN}✅ Redis OK (avec mot de passe) - Cache: ${CACHE_KEYS}, Sessions: ${SESSION_KEYS}, Queues: ${QUEUE_KEYS}${NC}"
    else
        echo -e "${RED}❌ Redis ne répond pas${NC}"
    fi
else
    if redis-cli ping >/dev/null 2>&1; then
        CACHE_KEYS=$(redis-cli -n 1 DBSIZE 2>/dev/null | awk '{print $2}')
        SESSION_KEYS=$(redis-cli -n 2 DBSIZE 2>/dev/null | awk '{print $2}')
        QUEUE_KEYS=$(redis-cli -n 3 DBSIZE 2>/dev/null | awk '{print $2}')
        echo -e "${GREEN}✅ Redis OK (sans mot de passe) - Cache: ${CACHE_KEYS}, Sessions: ${SESSION_KEYS}, Queues: ${QUEUE_KEYS}${NC}"
    else
        echo -e "${RED}❌ Redis ne répond pas${NC}"
    fi
fi

echo ""
echo -e "${GREEN}=========================================="
echo "✅ CORRECTION APPLIQUÉE!"
echo -e "==========================================${NC}\n"

echo -e "${BLUE}📊 Résumé des changements:${NC}"
echo "  • QUEUE_CONNECTION: database → redis"
echo "  • Supervisor: queue:work database → queue:work redis"
echo "  • Cache Laravel rechargé"
echo "  • Workers redémarrés"
echo ""

echo -e "${BLUE}📝 Vérifier dans Sentry:${NC}"
echo "  L'erreur 'Table jobs doesn't exist' devrait disparaître"
echo "  dans les prochaines minutes."
echo ""

echo -e "${YELLOW}💡 Monitorer les logs:${NC}"
echo "  tail -f $APP_PATH/storage/logs/laravel.log"
echo "  tail -f $APP_PATH/storage/logs/worker.log"
echo ""
