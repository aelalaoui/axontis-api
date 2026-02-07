#!/bin/bash
# ============================================================================
# Script d'activation Redis pour Axontis
# Usage: sudo bash activate-redis.sh
# ============================================================================

set -e

APP_PATH="/var/www/axontis"
ENV_FILE="$APP_PATH/.env"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo "🔴 Activation de Redis pour Axontis"
echo -e "==========================================${NC}\n"

# Vérifier que Redis est installé et actif
if ! systemctl is-active --quiet redis-server; then
    echo -e "${RED}❌ Redis n'est pas actif!${NC}"
    echo "Exécutez d'abord: sudo bash setup-redis-vultr.sh"
    exit 1
fi

echo -e "${GREEN}✅ Redis est actif${NC}\n"

# Vérifier que le fichier .env existe
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Fichier .env introuvable: $ENV_FILE${NC}"
    exit 1
fi

# Créer une sauvegarde du .env
echo -e "${YELLOW}📦 Sauvegarde du .env...${NC}"
cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
echo -e "${GREEN}✅ Sauvegarde créée${NC}\n"

# Mettre à jour les variables .env
echo -e "${YELLOW}⚙️  Mise à jour du fichier .env...${NC}"

# Fonction pour mettre à jour ou ajouter une variable
update_env() {
    local key=$1
    local value=$2

    if grep -q "^${key}=" "$ENV_FILE"; then
        # Remplacer la ligne existante
        sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
    else
        # Ajouter la ligne si elle n'existe pas
        echo "${key}=${value}" >> "$ENV_FILE"
    fi
}

# Mise à jour des drivers
update_env "CACHE_DRIVER" "redis"
update_env "SESSION_DRIVER" "redis"
update_env "QUEUE_CONNECTION" "redis"

# Ajouter/mettre à jour les variables Redis si nécessaire
if ! grep -q "^REDIS_CLIENT=" "$ENV_FILE"; then
    echo "" >> "$ENV_FILE"
    echo "# Redis Configuration" >> "$ENV_FILE"
fi

update_env "REDIS_CLIENT" "phpredis"
update_env "REDIS_HOST" "127.0.0.1"
update_env "REDIS_PASSWORD" "null"
update_env "REDIS_PORT" "6379"
update_env "REDIS_DB" "0"
update_env "REDIS_CACHE_DB" "1"
update_env "REDIS_SESSION_DB" "2"
update_env "REDIS_QUEUE_DB" "3"

echo -e "${GREEN}✅ Configuration .env mise à jour${NC}\n"

# Afficher les changements
echo -e "${BLUE}📋 Variables modifiées:${NC}"
echo -e "${GREEN}CACHE_DRIVER=redis${NC}"
echo -e "${GREEN}SESSION_DRIVER=redis${NC}"
echo -e "${GREEN}QUEUE_CONNECTION=redis${NC}"
echo ""

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
supervisorctl restart axontis-worker:* 2>/dev/null || true
echo -e "${GREEN}✅ Workers redémarrés${NC}\n"

# Vérifications
echo -e "${YELLOW}🔍 Vérifications...${NC}\n"

# Test Redis
REDIS_PING=$(redis-cli ping 2>/dev/null || echo "ERROR")
if [[ "$REDIS_PING" == "PONG" ]]; then
    echo -e "${GREEN}✅ Redis répond correctement${NC}"
else
    echo -e "${RED}❌ Redis ne répond pas${NC}"
    exit 1
fi

# Vérifier les bases de données
CACHE_KEYS=$(redis-cli -n 1 DBSIZE 2>/dev/null | awk '{print $1}')
SESSION_KEYS=$(redis-cli -n 2 DBSIZE 2>/dev/null | awk '{print $1}')
QUEUE_KEYS=$(redis-cli -n 3 DBSIZE 2>/dev/null | awk '{print $1}')

echo -e "   Cache (DB 1): ${CACHE_KEYS} clés"
echo -e "   Sessions (DB 2): ${SESSION_KEYS} clés"
echo -e "   Queues (DB 3): ${QUEUE_KEYS} clés"

# Vérifier les workers
WORKER_COUNT=$(supervisorctl status axontis-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0")
if [[ "$WORKER_COUNT" -gt 0 ]]; then
    echo -e "${GREEN}✅ ${WORKER_COUNT} worker(s) actif(s)${NC}"
else
    echo -e "${YELLOW}⚠️  Aucun worker actif${NC}"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "✅ REDIS ACTIVÉ AVEC SUCCÈS!"
echo -e "==========================================${NC}\n"

echo -e "${BLUE}📊 Configuration active:${NC}"
echo "  • Cache: Redis (DB 1)"
echo "  • Sessions: Redis (DB 2)"
echo "  • Queues: Redis (DB 3)"
echo ""

echo -e "${BLUE}📝 Prochaines étapes (optionnel):${NC}"
echo "  1. Tester l'application"
echo "  2. Sécuriser Redis avec un mot de passe (voir REDIS_CONFIGURATION.md)"
echo "  3. Monitorer les performances"
echo ""

echo -e "${YELLOW}💡 Commandes utiles:${NC}"
echo "  redis-cli MONITOR           # Monitorer en temps réel"
echo "  redis-cli INFO stats        # Voir les statistiques"
echo "  supervisorctl status        # Statut des workers"
echo "  tail -f storage/logs/laravel.log  # Logs Laravel"
echo ""
