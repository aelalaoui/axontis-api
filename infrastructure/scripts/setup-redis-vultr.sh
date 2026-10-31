#!/bin/bash
# ============================================================================
# Script d'installation et configuration de Redis pour Axontis
# Usage: sudo bash setup-redis-vultr.sh
# ============================================================================

set -e

echo "=========================================="
echo "🚀 Installation et configuration de Redis"
echo "=========================================="

# ============================================
# 1. INSTALLATION DE REDIS
# ============================================
echo "📦 Installation de Redis Server..."
apt update
apt install -y redis-server redis-tools

# ============================================
# 2. CONFIGURATION DE REDIS
# ============================================
echo "⚙️  Configuration de Redis..."

# Backup de la configuration par défaut
cp /etc/redis/redis.conf /etc/redis/redis.conf.backup

# Configuration de Redis pour la production
cat > /etc/redis/redis.conf << 'EOF'
# Configuration Redis pour Axontis - Production

# Network
bind 127.0.0.1 ::1
protected-mode yes
port 6379
tcp-backlog 511
timeout 300
tcp-keepalive 300

# General
daemonize no
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16

# Snapshotting (persistance)
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis

# Security
# requirepass VotreMotDePasseSecuriseIci

# Limits
maxclients 10000
maxmemory 512mb
maxmemory-policy allkeys-lru

# Append Only File (AOF) - Pour plus de durabilité
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb

# Slow log
slowlog-log-slower-than 10000
slowlog-max-len 128

# Latency monitor
latency-monitor-threshold 100
EOF

echo "✅ Configuration Redis créée"

# ============================================
# 3. AJUSTEMENTS DU SYSTÈME
# ============================================
echo "🔧 Optimisation du système pour Redis..."

# Désactiver Transparent Huge Pages (THP)
echo never > /sys/kernel/mm/transparent_hugepage/enabled
echo never > /sys/kernel/mm/transparent_hugepage/defrag

# Rendre permanent (au redémarrage)
cat > /etc/rc.local << 'EOF'
#!/bin/bash
echo never > /sys/kernel/mm/transparent_hugepage/enabled
echo never > /sys/kernel/mm/transparent_hugepage/defrag
exit 0
EOF
chmod +x /etc/rc.local

# Augmenter le nombre de connexions simultanées
sysctl -w net.core.somaxconn=65535
echo "net.core.somaxconn=65535" >> /etc/sysctl.conf

# Configurer overcommit memory
sysctl -w vm.overcommit_memory=1
echo "vm.overcommit_memory=1" >> /etc/sysctl.conf

echo "✅ Optimisations système appliquées"

# ============================================
# 4. PERMISSIONS ET RÉPERTOIRES
# ============================================
echo "📁 Configuration des permissions..."

# S'assurer que les répertoires existent avec les bonnes permissions
mkdir -p /var/lib/redis
mkdir -p /var/log/redis
mkdir -p /var/run/redis

chown -R redis:redis /var/lib/redis
chown -R redis:redis /var/log/redis
chown -R redis:redis /var/run/redis

chmod 750 /var/lib/redis
chmod 750 /var/log/redis

echo "✅ Permissions configurées"

# ============================================
# 5. CONFIGURATION DU SERVICE SYSTEMD
# ============================================
echo "🔧 Configuration du service Redis..."

systemctl enable redis-server
systemctl restart redis-server

# Vérifier le statut
sleep 2
if systemctl is-active --quiet redis-server; then
    echo "✅ Redis est démarré et actif"
else
    echo "❌ Erreur: Redis n'a pas démarré correctement"
    systemctl status redis-server
    exit 1
fi

# ============================================
# 6. TESTS DE CONNEXION
# ============================================
echo "🧪 Test de connexion à Redis..."

if redis-cli ping | grep -q "PONG"; then
    echo "✅ Redis répond correctement (PONG)"
else
    echo "❌ Redis ne répond pas correctement"
    exit 1
fi

# Test de lecture/écriture
redis-cli SET test_key "Axontis Redis Test" > /dev/null
TEST_VALUE=$(redis-cli GET test_key)
if [ "$TEST_VALUE" = "Axontis Redis Test" ]; then
    echo "✅ Test lecture/écriture réussi"
    redis-cli DEL test_key > /dev/null
else
    echo "❌ Test lecture/écriture échoué"
    exit 1
fi

# ============================================
# 7. INSTALLATION DE L'EXTENSION PHP REDIS
# ============================================
echo "🐘 Vérification de l'extension PHP Redis..."

if php -m | grep -q "redis"; then
    echo "✅ Extension PHP Redis déjà installée"
else
    echo "📦 Installation de l'extension PHP Redis..."
    apt install -y php8.5-redis
    systemctl restart php8.5-fpm
    echo "✅ Extension PHP Redis installée"
fi

# ============================================
# 8. AFFICHAGE DES INFORMATIONS
# ============================================
echo ""
echo "=========================================="
echo "✅ Installation Redis terminée avec succès!"
echo "=========================================="
echo ""
echo "📊 Informations Redis:"
redis-cli INFO server | grep "redis_version"
redis-cli INFO server | grep "redis_mode"
echo ""
echo "💾 Bases de données configurées:"
echo "  - DB 0: Cache Laravel (par défaut)"
echo "  - DB 1: Cache Laravel (dédié)"
echo "  - DB 2: Sessions Laravel"
echo "  - DB 3: Files d'attente (queues)"
echo ""
echo "📝 Configuration:"
echo "  - Host: 127.0.0.1"
echo "  - Port: 6379"
echo "  - Max Memory: 512MB"
echo "  - Eviction Policy: allkeys-lru"
echo "  - Persistence: RDB + AOF"
echo ""
echo "🔍 Commandes utiles:"
echo "  - Statut: systemctl status redis-server"
echo "  - Logs: tail -f /var/log/redis/redis-server.log"
echo "  - CLI: redis-cli"
echo "  - Monitor: redis-cli MONITOR"
echo "  - Info: redis-cli INFO"
echo "  - Stats: redis-cli INFO stats"
echo ""
echo "⚠️  IMPORTANT:"
echo "  1. Pour la production, décommentez et configurez 'requirepass' dans /etc/redis/redis.conf"
echo "  2. Mettez à jour votre fichier .env avec REDIS_PASSWORD"
echo "  3. Redémarrez Redis après modification: systemctl restart redis-server"
echo ""
echo "📋 Prochaines étapes:"
echo "  1. Mettre à jour le .env de l'application:"
echo "     CACHE_DRIVER=redis"
echo "     SESSION_DRIVER=redis"
echo "     QUEUE_CONNECTION=redis"
echo "     REDIS_HOST=127.0.0.1"
echo "     REDIS_PASSWORD=null"
echo "     REDIS_PORT=6379"
echo ""
echo "  2. Vider le cache et redémarrer les workers:"
echo "     php artisan config:cache"
echo "     php artisan cache:clear"
echo "     php artisan queue:restart"
echo "     supervisorctl restart axontis-worker:*"
echo ""
echo "=========================================="
