# Local Development Environment Setup

> Getting your machine ready for Sentinel AI development

---

## 🎯 Objective

Configure your local Windows development environment with all required tools, services, and databases for productive Sentinel AI development.

---

## ✅ Prerequisites Checklist

Before starting, verify you have:

- [ ] Windows 10/11 (64-bit)
- [ ] At least 8GB RAM (16GB recommended)
- [ ] 50GB free disk space
- [ ] Administrator access
- [ ] Internet connection

---

## 🛠️ Required Tools

### 1. Version Control

**Git for Windows**
- Download: https://git-scm.com/download/win
- Install with default options
- Verify: `git --version`

```bash
git --version
# Output: git version 2.42.0.windows.1
```

### 2. Backend Development

**PHP 8.2+**
- Option A: XAMPP (includes PHP, MySQL, Apache)
  - Download: https://www.apachefriends.org/
  - Install to: `C:\xampp\`
  - Enable modules: PHP, MySQL, Apache
  
- Option B: Standalone PHP
  - Download: https://www.php.net/downloads.php
  - Extract to: `C:\php\`
  - Add to PATH

Verify:
```bash
php --version
# Output: PHP 8.2.x (CLI)
```

**Composer** (PHP package manager)
- Download: https://getcomposer.org/download/
- Install globally (adds to PATH)
- Verify: `composer --version`

```bash
composer --version
# Output: Composer version 2.6.x
```

**Laravel** (Web framework)
Install globally:
```bash
composer global require laravel/installer
```

Verify:
```bash
laravel --version
```

### 3. Database

**MySQL 8.0+**
- Included in XAMPP, or standalone from https://dev.mysql.com/downloads/mysql/
- Default credentials (XAMPP): root / (no password)
- Change password in production

Verify:
```bash
mysql --version
# Output: mysql Ver 8.0.x
```

**MySQL Workbench** (GUI tool)
- Download: https://dev.mysql.com/downloads/workbench/
- Free GUI for database design and SQL

### 4. Node.js & npm (for frontend)

**Node.js LTS**
- Download: https://nodejs.org/
- Includes npm
- Verify: `node --version` and `npm --version`

```bash
node --version
# Output: v18.x.x or v20.x.x

npm --version
# Output: 9.x.x or 10.x.x
```

### 5. Code Editor

**VS Code** (already using)
- Extensions needed:
  - PHP Intelephense
  - Laravel Extension Pack
  - MySQL (cweijan)
  - REST Client
  - ES7+ React/Redux/React-Native snippets
  - Thunder Client (or Postman)

### 6. IDE for Android

**Android Studio**
- Download: https://developer.android.com/studio
- Large download (~1GB)
- Required for Android app development

### 7. Python (for AI/ML)

**Python 3.10+**
- Download: https://www.python.org/
- Add to PATH during installation
- Verify: `python --version`

```bash
python --version
# Output: Python 3.10.x or 3.11.x
```

**pip** (Python package manager)
Verify:
```bash
pip --version
```

### 8. API Testing

**Postman** or **Thunder Client**
- Postman: https://www.postman.com/downloads/
- Thunder Client: VS Code extension (lighter)

### 9. Docker (Optional, for production)

**Docker Desktop**
- Download: https://www.docker.com/products/docker-desktop
- Requires Windows 10 Pro/Enterprise + WSL2
- Future use for containerization

---

## 📁 Directory Structure

Create the following on your C: drive:

```
C:\
├── Development/
│   ├── Sentinel-AI-Security-Suite/  (project repository)
│   ├── tools/                        (development tools)
│   └── resources/                    (shared resources)
│
├── xampp/                            (if using XAMPP)
│   ├── htdocs/                       (NOT code, web root only)
│   ├── mysql/
│   └── apache/
│
└── Android/                          (Android development)
    └── Sdk/
```

**Important**: Keep code at `C:\Development\Sentinel-AI-Security-Suite\` separate from XAMPP.

---

## ⚙️ Configuration

### 1. Apache Virtual Host (for local web development)

**File**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

Add at the end:
```apache
<VirtualHost *:80>
    ServerName sentinel.local
    ServerAlias www.sentinel.local
    DocumentRoot "C:\Development\Sentinel-AI-Security-Suite\apps\web\public"
    
    <Directory "C:\Development\Sentinel-AI-Security-Suite\apps\web\public">
        AllowOverride All
        Require all granted
        
        # Handle Laravel routing
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?/$1 [QSA,L]
    </Directory>
    
    ErrorLog "logs/sentinel_error.log"
    CustomLog "logs/sentinel_access.log" combined
</VirtualHost>
```

### 2. Windows Hosts File

**File**: `C:\Windows\System32\drivers\etc\hosts`

Add:
```
127.0.0.1 sentinel.local
127.0.0.1 www.sentinel.local
127.0.0.1 api.sentinel.local
```

### 3. MySQL Root Password Change

```bash
# Connect to MySQL
mysql -u root

# In MySQL CLI:
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_secure_password';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Create Development Database

```bash
mysql -u root -p

# In MySQL:
CREATE DATABASE sentinel_development CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE sentinel_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sentinel_user'@'localhost' IDENTIFIED BY 'PUBLIC_EXAMPLE_CONFIGURE_LOCALLY';
GRANT ALL PRIVILEGES ON sentinel_development.* TO 'sentinel_user'@'localhost';
GRANT ALL PRIVILEGES ON sentinel_testing.* TO 'sentinel_user'@'localhost';
FLUSH PRIVILEGES;
```

### 5. Project Environment File

**File**: `C:\Development\Sentinel-AI-Security-Suite\apps\web\.env`

Create from `.env.example`:
```bash
cd C:\Development\Sentinel-AI-Security-Suite\apps\web
copy .env.example .env
```

Edit `.env`:
```env
APP_NAME="Sentinel AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://sentinel.local

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sentinel_development
DB_USERNAME=sentinel_user
DB_PASSWORD=PUBLIC_EXAMPLE_CONFIGURE_LOCALLY

# JWT
JWT_SECRET=your_jwt_secret_key_here

# AI/ML Services
OPENAI_API_KEY=your_key_here
PYTHON_AI_SERVICE_URL=http://localhost:5000

# File Storage
FILESYSTEM_DISK=local
```

### 6. Node Configuration

**File**: `C:\Development\Sentinel-AI-Security-Suite\apps\web\.npmrc` (if using npm)

```
registry=https://registry.npmjs.org/
```

---

## 🚀 Startup Sequence

### Start Development Environment

```bash
# 1. Start XAMPP
Start XAMPP Control Panel
Click "Start" for Apache and MySQL

# 2. Verify services
# Apache: http://localhost
# phpMyAdmin: http://localhost/phpmyadmin

# 3. Navigate to project
cd C:\Development\Sentinel-AI-Security-Suite

# 4. Install PHP dependencies
cd apps\web
composer install

# 5. Generate app key (Laravel)
php artisan key:generate

# 6. Run migrations
php artisan migrate

# 7. Seed database (optional)
php artisan db:seed

# 8. Start Laravel dev server
php artisan serve

# 9. Access application
# Web: http://sentinel.local (XAMPP Apache)
# API: http://localhost:8000 (Laravel artisan serve)
```

### Daily Startup

```bash
# Quick startup script: start_dev.bat
@echo off
cd C:\xampp
start xampp-control.exe

cd C:\Development\Sentinel-AI-Security-Suite\apps\web
php artisan serve
```

---

## 📊 Service Ports

| Service | Port | URL | Purpose |
|---------|------|-----|---------|
| Apache (XAMPP) | 80 | http://sentinel.local | Web server |
| MySQL | 3306 | localhost:3306 | Database |
| phpMyAdmin | 80 | http://localhost/phpmyadmin | DB GUI |
| Laravel Dev | 8000 | http://localhost:8000 | API development |
| Python AI | 5000 | http://localhost:5000 | AI service |
| React Dev | 3000 | http://localhost:3000 | Frontend (future) |
| Android Emulator | 5555 | localhost:5555 | Android debugger |

---

## 🧪 Verification Tests

Run these commands to verify your setup:

```bash
# 1. Git
git --version
git config --list

# 2. PHP
php --version
php -m | findstr mysql  # Should show mysql/mysqli

# 3. Composer
composer --version
composer diagnose

# 4. MySQL
mysql --version
mysql -u root -p -e "SELECT VERSION();"

# 5. Node (if using)
node --version
npm --version

# 6. Laravel (after composer install)
php artisan --version

# 7. Database connection
cd C:\Development\Sentinel-AI-Security-Suite\apps\web
php artisan tinker
>>> DB::connection()->getPdo()
# Should return PDO object
```

---

## 📝 .env.example Template

Copy to project root:

```env
# Application
APP_NAME="Sentinel AI Security Suite"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://sentinel.local
APP_TIMEZONE="UTC"

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sentinel_development
DB_USERNAME=sentinel_user
DB_PASSWORD=PUBLIC_EXAMPLE_CONFIGURE_LOCALLY

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file

# Queue
QUEUE_CONNECTION=sync

# Mail (for notifications)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sentinel.local

# Authentication
JWT_SECRET=changeme
JWT_ALGORITHM=HS256
JWT_TTL=60

# AI Services
OPENAI_API_KEY=
PYTHON_AI_SERVICE_URL=http://localhost:5000
AI_MODEL_TYPE=gpt-3.5-turbo

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# GPS Verification (for attendance)
GPS_ACCURACY_THRESHOLD=100  # meters

# Feature Flags
FEATURE_AI_REPORTS=true
FEATURE_ANALYTICS=true
FEATURE_MOBILE_CHECKIN=true
```

---

## 🐛 Troubleshooting

### PHP MySQL Extension Missing

```bash
# Edit C:\xampp\php\php.ini
# Uncomment these lines:
extension=mysqli
extension=pdo_mysql
```

### Port 80 Already in Use

```bash
# Find what's using port 80
netstat -ano | findstr :80

# Kill process (replace PID)
taskkill /PID 1234 /F

# Or use different Apache port in httpd.conf
Listen 8080
```

### MySQL Won't Start

```bash
# Check MySQL error log
C:\xampp\mysql\data\*.err

# Restart MySQL service
net stop MySQL80
net start MySQL80
```

### Composer Out of Memory

```bash
# Increase PHP memory limit
php -d memory_limit=2G composer install
```

### Laravel Key Not Generated

```bash
cd C:\Development\Sentinel-AI-Security-Suite\apps\web
php artisan key:generate
```

---

## 📚 Learning Resources

- **Laravel Official**: https://laravel.com/docs
- **PHP Manual**: https://www.php.net/manual/
- **MySQL Reference**: https://dev.mysql.com/doc/refman/8.0/en/
- **REST API Best Practices**: https://restfulapi.net/
- **Sentinel Project Docs**: `C:\Development\Sentinel-AI-Security-Suite\docs\`

---

## ✨ Next Steps

1. ✅ Install all tools from "Required Tools" section
2. ✅ Create directory structure
3. ✅ Configure Apache virtual host
4. ✅ Create MySQL databases
5. ✅ Set up project .env file
6. ✅ Run startup sequence
7. ✅ Verify all services
8. ✅ Open project in VS Code
9. 📋 Ready for development!

---

**Status**: Setup Guide Complete  
**Estimated Setup Time**: 2-3 hours (depending on download speeds)  
**Confidence**: Enterprise Grade ✨

Once setup is complete, you're ready to start Sprint 1 development!

---

## 🤝 Support

If you encounter issues:

1. Check troubleshooting section above
2. Review `.env` configuration
3. Check Laravel logs: `storage/logs/`
4. MySQL error logs: `C:\xampp\mysql\data\`
5. Consult project documentation
6. Google the error message

You've got this! 🚀
