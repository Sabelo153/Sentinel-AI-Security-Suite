# Sentinel AI Security Suite

**Version 0.1.0 - Foundation**

> Sentinel Application Framework (SAF) v0.1 is now the active development baseline.

## Overview

Sentinel AI Security Suite is a comprehensive security operations platform designed to manage:
- Security teams and operations
- Incident tracking and reporting
- Visitor and vehicle management
- Patrol operations
- AI-powered risk analysis and recommendations
- Advanced analytics and reporting

## Vision

This monorepo contains all components of the Sentinel platform:
- **Web Dashboard** (PHP) - Security team management interface
- **Mobile App** (Android) - Field operations and incident reporting
- **REST API** - Backend services
- **AI Engine** - Risk analysis and report generation
- **Notification System** - Multi-channel alerts
- **Database** - Centralized data layer

## Current Focus

Milestone 1 establishes the reusable platform every later delivery depends on:

- SAF bootstrap and routing
- Dependency injection container
- Environment and configuration loading
- PDO database access
- Migration and seed runners
- JWT-based authentication foundation
- Company, user, role, audit, and dashboard API groundwork

Version 1.0 scope is constrained by the MVP and product specification under `docs/`.

## Getting Started

### Prerequisites
- PHP 8.2+
- Node.js 18+ (for build tools)
- MySQL/MariaDB 8.0+
- Git
- XAMPP or similar local development environment

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/Sentinel-AI-Security-Suite.git
   cd Sentinel-AI-Security-Suite
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your local settings
   ```

4. **Set up database**
   ```bash
   php sentinel migrate
   php sentinel seed --profile=development
   ```

5. **Start development server**
   ```bash
   php sentinel serve 127.0.0.1 8080
   php sentinel serve:api 127.0.0.1 8091
   ```

6. **Verify the framework**
   ```bash
   php sentinel route:list
   ```

## Project Structure

See [docs/architecture/DIRECTORY_STRUCTURE.md](docs/architecture/DIRECTORY_STRUCTURE.md) for complete project structure.

### Key Directories

- `app/` - Root SAF framework, controllers, services, repositories, and domains
- `apps/` - User-facing applications (Web, Android, Desktop)
- `services/` - Backend services (API, AI Engine, Notifications)
- `database/` - Database schema, migrations, and scripts
- `docs/` - Documentation (architecture, API, user guides)
- `infrastructure/` - Deployment configurations
- `testing/` - Test suites
- `shared/` - Shared utilities and constants

### Milestone 1 Commands

```bash
php sentinel
php sentinel migrate --dry-run --until=008_users.sql
php sentinel seed --dry-run --profile=development
php sentinel route:list
php sentinel cache:clear
php sentinel config:clear
```

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | PHP 8.2, Bootstrap dashboard |
| Mobile | Android (Kotlin) |
| Backend | Custom SAF + PHP REST API |
| AI/ML | Python (Report Writer, Risk Engine) |
| Database | MySQL/MariaDB |
| Notifications | Email, SMS, Push, WhatsApp (future) |
| Infrastructure | Apache/Nginx, Docker (future) |

## Branch Strategy

We follow Git Flow for version control:

- **`main`** - Production releases (tagged versions)
- **`develop`** - Development branch (staging environment)
- **`feature/*`** - Feature branches (e.g., `feature/visitor-management`)
- **`bugfix/*`** - Bug fix branches (e.g., `bugfix/login-error`)
- **`release/*`** - Release preparation (e.g., `release/1.1.0`)
- **`hotfix/*`** - Production hotfixes (e.g., `hotfix/critical-security`)

### Commit Convention

```
<type>(<scope>): <subject>

<body>

<footer>
```

Examples:
- `feat(visitors): add visitor check-in system`
- `fix(auth): resolve password reset token expiry`
- `docs(api): update endpoint documentation`

## Documentation

- [Documentation Index](docs/README.md)
- [MVP Definition](docs/MVP.md)
- [Sentinel Product Specification](docs/SPS.md)
- [Development Backlog](docs/BACKLOG.md)
- [Low-Fidelity Wireframes](docs/WIREFRAMES.md)
- [Sentinel Engineering Handbook](docs/SEH/01_ProjectVision.md)
- [Business Requirements](docs/business/)
- [Architecture Guide](docs/architecture/)
- [API Documentation](docs/api/)
- [Database Design](docs/database/)
- [Developer Guide](docs/developer-guide/)
- [User Manual](docs/user-manual/)
- [Admin Manual](docs/admin-manual/)

## Development

### Setup Development Environment

1. Navigate to project root:
   ```bash
   cd C:\Development\Sentinel-AI-Security-Suite
   ```

2. Create feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. Make changes and test

4. Commit with conventional messages

5. Push and create Pull Request

### Running Tests

```bash
# Root SAF syntax check
php -l public/index.php

# Root migration dry-run
php sentinel migrate --dry-run --until=008_users.sql

# Existing API checks
php -l apps/api/public/index.php

# Android tests
cd apps/android
./gradlew test

# AI Engine tests
cd services/ai-engine
pytest
```

## Apache Virtual Host Setup (Windows/XAMPP)

Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName sentinel.local
    DocumentRoot "C:\Development\Sentinel-AI-Security-Suite\apps\web\public"
    
    <Directory "C:\Development\Sentinel-AI-Security-Suite\apps\web\public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1 sentinel.local
```

## Deployment

See [infrastructure/](infrastructure/) for deployment configurations for various environments:
- Ubuntu Server
- Docker
- Cloud platforms (AWS, Azure, GCP)

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and development process.

## License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file for details.

## Roadmap

1. Milestone 1: Foundation
2. Milestone 2: Identity & Security
3. Milestone 3: Workforce
4. Milestone 4: Operations
5. Milestone 5: Reports
6. Milestone 6: AI
7. Milestone 7: Android
8. Milestone 8: Cloud
9. Milestone 9: Enterprise
10. Milestone 10: Commercial Release

## Support

For issues and questions:
- 📧 Email: support@sentinelai.com
- 🐛 Bug Reports: [GitHub Issues](../../issues)
- 📖 Documentation: [Wiki](../../wiki)

## Team

- **Project Lead**: [Your Name]
- **Backend Architect**: [Name]
- **Mobile Lead**: [Name]
- **AI Specialist**: [Name]

---

**Last Updated**: 2026-07-06  
**Version**: 1.0.0-alpha  
**Status**: Initial Setup Phase

## Repository cleanup

The active runtime and empty-file cleanup are recorded in [docs/EMPTY_FILE_AUDIT.md](docs/EMPTY_FILE_AUDIT.md).

