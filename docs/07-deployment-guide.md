# Deployment Guide

## 1. Architecture Production

```
Internet → Nginx (SSL) → Vue Static + Phalcon API → MariaDB
                                              ↓
                                         Odoo Server
```

---

## 2. Server Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 core | 4 core |
| RAM | 4 GB | 8 GB |
| Storage | 40 GB SSD | 100 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

---

## 3. MariaDB Deployment

```bash
sudo apt install mariadb-server
sudo mysql_secure_installation

CREATE DATABASE pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pos_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL ON pos_db.* TO 'pos_user'@'localhost';
FLUSH PRIVILEGES;

mysql -u pos_user -p pos_db < database/migrations/001_initial_schema.sql
```

### Backup Strategy

```bash
# Daily backup cron (2 AM)
0 2 * * * mysqldump -u pos_user -p'password' pos_db | gzip > /backup/pos_db_$(date +\%Y\%m\%d).sql.gz

# Retention: keep 30 days
find /backup -name "pos_db_*.sql.gz" -mtime +30 -delete
```

---

## 4. Backend Deployment (Phalcon PHP)

```bash
sudo apt install php8.1-fpm php8.1-mysql php8.1-json php8.1-mbstring
# Install Phalcon extension per OS documentation

cd /var/www/pos/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edit .env for production

chmod -R 775 storage/logs
chown -R www-data:www-data storage
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name api.pos.company.com;

    ssl_certificate /etc/letsencrypt/live/api.pos.company.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.pos.company.com/privkey.pem;

    root /var/www/pos/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?_url=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Environment Variables (Production)

```env
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<generate-64-char-random-string>
DB_HOST=127.0.0.1
DB_NAME=pos_db
DB_USER=pos_user
DB_PASS=<strong-password>
ODOO_URL=https://odoo.company.com
ODOO_SYNC_ENABLED=true
```

---

## 5. Frontend Deployment (Vue.js)

```bash
cd /var/www/pos/frontend
npm ci
cp .env.production .env

# .env.production
VITE_API_BASE_URL=https://api.pos.company.com/api/v1

npm run build
# Output: dist/
```

### Nginx Static Files

```nginx
server {
    listen 443 ssl http2;
    server_name pos.company.com;

    root /var/www/pos/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass https://api.pos.company.com;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 6. Odoo Deployment

```bash
# Using Docker (recommended)
docker run -d \
  -e POSTGRES_USER=odoo \
  -e POSTGRES_PASSWORD=odoo \
  -e POSTGRES_DB=postgres \
  --name odoo-db postgres:15

docker run -d \
  -p 8069:8069 \
  --link odoo-db:db \
  -v /path/to/odoo/addons:/mnt/extra-addons \
  --name odoo odoo:17

# Copy custom module
cp -r odoo/pos_integration_manager /path/to/odoo/addons/
# Install via Odoo Apps menu
```

---

## 7. Security Setup

| Item | Action |
|------|--------|
| HTTPS | Let's Encrypt SSL on all domains |
| Firewall | UFW: allow 22, 80, 443 only |
| JWT Secret | 64+ char random, never commit |
| DB Password | Strong, unique per environment |
| File Permissions | storage/logs writable by www-data only |
| Rate Limiting | Nginx limit_req on /api/v1/auth/login |
| Headers | X-Frame-Options, X-Content-Type-Options |

```nginx
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

location /api/v1/auth/login {
    limit_req zone=login burst=3 nodelay;
    # ... php handler
}
```

---

## 8. Monitoring

- **Application logs:** `backend/storage/logs/app.log`
- **Nginx access/error logs:** `/var/log/nginx/`
- **MariaDB slow query log:** enable for optimization
- **Odoo sync status:** monitor via Odoo POS Integration dashboard

---

## 9. Deployment Checklist

- [ ] MariaDB installed, migrated, seeded
- [ ] Backend .env configured
- [ ] Phalcon extension loaded
- [ ] Frontend built and deployed
- [ ] SSL certificates active
- [ ] Odoo module installed
- [ ] Odoo sync tested end-to-end
- [ ] Backup cron configured
- [ ] Default passwords changed
- [ ] Firewall configured
