#!/bin/bash
# ============================================================================
# Script d'installation de MySQL sur Vultr VPS
# Usage: sudo bash setup-mysql-vultr.sh
# ============================================================================

set -e

# Configuration
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-$(openssl rand -base64 32)}"
DB_NAME="${DB_NAME:-axontis}"
DB_USER="${DB_USER:-axontis_user}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 24)}"

echo "=========================================="
echo "🔧 Installation de MySQL sur Vultr VPS"
echo "=========================================="

# ============================================
# 1. MISE À JOUR DU SYSTÈME
# ============================================
echo "📦 Mise à jour du système..."
apt update && apt upgrade -y

# ============================================
# 2. INSTALLATION DE MYSQL
# ============================================
echo "🗄️ Installation de MySQL Server..."
apt install -y mysql-server mysql-client

# ============================================
# 3. DÉMARRAGE ET ACTIVATION
# ============================================
echo "🚀 Démarrage de MySQL..."
systemctl start mysql
systemctl enable mysql

# ============================================
# 4. SÉCURISATION DE MYSQL
# ============================================
echo "🔒 Sécurisation de MySQL..."

# Définir le mot de passe root
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"

# Supprimer les utilisateurs anonymes
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='';"

# Supprimer la base de test
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"

# ============================================
# 5. CRÉATION DE LA BASE ET DE L'UTILISATEUR
# ============================================
echo "📊 Création de la base de données '${DB_NAME}'..."
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "👤 Création de l'utilisateur '${DB_USER}'..."
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

# ============================================
# 6. OPTIMISATION POUR PRODUCTION
# ============================================
echo "⚡ Optimisation de MySQL pour la production..."

# Créer une configuration optimisée
cat > /etc/mysql/mysql.conf.d/axontis.cnf << 'EOF'
[mysqld]
# Performance
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connexions
max_connections = 150
wait_timeout = 600
interactive_timeout = 600

# Cache
query_cache_type = 0
query_cache_size = 0
table_open_cache = 2000
thread_cache_size = 50

# Logs (désactivés en production pour performance)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Charset
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Sécurité
bind-address = 127.0.0.1
EOF

# ============================================
# 7. REDÉMARRAGE DE MYSQL
# ============================================
echo "🔄 Redémarrage de MySQL avec la nouvelle configuration..."
systemctl restart mysql

# ============================================
# 8. VÉRIFICATION
# ============================================
echo "✅ Vérification de l'installation..."
if mysql -u "${DB_USER}" -p"${DB_PASSWORD}" -e "SELECT 1;" "${DB_NAME}" > /dev/null 2>&1; then
    echo "✅ MySQL installé et configuré avec succès!"
else
    echo "❌ Erreur lors de la vérification de MySQL"
    exit 1
fi

# ============================================
# 9. AFFICHAGE DES INFORMATIONS
# ============================================
echo ""
echo "=========================================="
echo "🎉 INSTALLATION TERMINÉE!"
echo "=========================================="
echo ""
echo "📋 INFORMATIONS DE CONNEXION:"
echo "   Host:     localhost (ou 127.0.0.1)"
echo "   Port:     3306"
echo "   Database: ${DB_NAME}"
echo "   Username: ${DB_USER}"
echo "   Password: ${DB_PASSWORD}"
echo ""
echo "🔐 MOT DE PASSE ROOT MYSQL:"
echo "   ${MYSQL_ROOT_PASSWORD}"
echo ""
echo "⚠️  IMPORTANT: Sauvegardez ces informations en lieu sûr!"
echo ""
echo "📝 Pour configurer GitHub Actions, ajoutez ces secrets:"
echo "   DB_HOST=localhost"
echo "   DB_PORT=3306"
echo "   DB_DATABASE=${DB_NAME}"
echo "   DB_USERNAME=${DB_USER}"
echo "   DB_PASSWORD=${DB_PASSWORD}"
echo ""
echo "=========================================="

# Sauvegarder les credentials dans un fichier (à supprimer après récupération)
cat > /root/.mysql_credentials << EOF
# MySQL Credentials for Axontis - DELETE THIS FILE AFTER SAVING!
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
EOF
chmod 600 /root/.mysql_credentials

echo "💾 Credentials sauvegardés dans /root/.mysql_credentials"
echo "⚠️  SUPPRIMEZ CE FICHIER après avoir noté les informations!"
