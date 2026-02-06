#!/bin/bash
# ============================================================================
# Script de setup complet du serveur Vultr pour Axontis
# Usage: sudo bash setup-vultr-server.sh
# ============================================================================

set -e

echo "=========================================="
echo "🚀 Configuration du serveur Vultr pour Axontis"
echo "=========================================="

# ============================================
# 1. MISE À JOUR DU SYSTÈME
# ============================================
echo "📦 Mise à jour du système..."
apt update && apt upgrade -y

# ============================================
# 2. INSTALLATION DE PHP 8.3
# ============================================
echo "🐘 Installation de PHP 8.3..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-sqlite3 \
    php8.3-gd \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-readline \
    php8.3-soap \
    php8.3-redis \
    php8.3-imagick \
    php8.3-fileinfo

# ============================================
# 3. INSTALLATION DE NGINX
# ============================================
echo "🌐 Installation de Nginx..."
apt install -y nginx

# ============================================
# 4. INSTALLATION DE COMPOSER
# ============================================
echo "📦 Installation de Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ============================================
# 5. INSTALLATION DE NODE.JS 22
# ============================================
echo "📦 Installation de Node.js 22..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs

# ============================================
# 6. INSTALLATION DE SUPERVISOR
# ============================================
echo "👷 Installation de Supervisor..."
apt install -y supervisor

# ============================================
# 7. INSTALLATION D'OUTILS UTILES
# ============================================
echo "🔧 Installation des outils utiles..."
apt install -y \
    git \
    unzip \
    htop \
    curl \
    wget \
    ufw \
    fail2ban \
    certbot \
    python3-certbot-nginx

# ============================================
# 8. CRÉATION DU RÉPERTOIRE DE L'APPLICATION
# ============================================
echo "📁 Création du répertoire de l'application..."
mkdir -p /var/www/axontis
mkdir -p /var/backups/axontis
chown -R www-data:www-data /var/www/axontis

# ============================================
# 9. CONFIGURATION DE NGINX
# ============================================
echo "🌐 Configuration de Nginx..."
cat > /etc/nginx/sites-available/axontis << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/axontis/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript application/json;
    gzip_disable "MSIE [1-6]\.";

    # Limites
    client_max_body_size 100M;
}
EOF

# Activer le site
ln -sf /etc/nginx/sites-available/axontis /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Tester et recharger Nginx
nginx -t && systemctl reload nginx

# ============================================
# 10. CONFIGURATION DE PHP-FPM
# ============================================
echo "🐘 Configuration de PHP-FPM..."
cat > /etc/php/8.3/fpm/pool.d/axontis.conf << 'EOF'
[axontis]
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500

php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
EOF

systemctl restart php8.3-fpm

# ============================================
# 11. CONFIGURATION DE SUPERVISOR (Queue Worker)
# ============================================
echo "👷 Configuration de Supervisor pour les queues..."
cat > /etc/supervisor/conf.d/axontis-worker.conf << 'EOF'
[program:axontis-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/axontis/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/axontis/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update

# ============================================
# 12. CONFIGURATION DU PARE-FEU
# ============================================
echo "🔥 Configuration du pare-feu..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable

# ============================================
# 13. CONFIGURATION DE FAIL2BAN
# ============================================
echo "🛡️ Configuration de Fail2Ban..."
systemctl enable fail2ban
systemctl start fail2ban

# ============================================
# RÉSUMÉ
# ============================================
echo ""
echo "=========================================="
echo "🎉 SERVEUR CONFIGURÉ AVEC SUCCÈS!"
echo "=========================================="
echo ""
echo "📋 SERVICES INSTALLÉS:"
echo "   ✅ PHP 8.3-FPM"
echo "   ✅ Nginx"
echo "   ✅ Composer"
echo "   ✅ Node.js 22"
echo "   ✅ Supervisor"
echo "   ✅ Certbot (SSL)"
echo "   ✅ UFW (Firewall)"
echo "   ✅ Fail2Ban"
echo ""
echo "⚠️  PROCHAINES ÉTAPES:"
echo "   1. Exécutez: sudo bash setup-mysql-vultr.sh"
echo "   2. Configurez les secrets GitHub Actions"
echo "   3. Configurez SSL: sudo certbot --nginx -d votre-domaine.com"
echo ""
echo "=========================================="
