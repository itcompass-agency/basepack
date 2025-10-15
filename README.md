<div align="center">

# BasePack

**DevOps Toolkit for Laravel Projects**

[![Latest Version](https://img.shields.io/packagist/v/itcompass/basepack.svg?style=flat-square)](https://packagist.org/packages/itcompass/basepack)
[![Total Downloads](https://img.shields.io/packagist/dt/itcompass/basepack.svg?style=flat-square)](https://packagist.org/packages/itcompass/basepack)
[![License](https://img.shields.io/packagist/l/itcompass/basepack.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/itcompass/basepack.svg?style=flat-square)](https://packagist.org/packages/itcompass/basepack)

*A complete Docker-based development and production environment for Laravel with SSL support, smart automation, and comprehensive DevOps tools.*

[Installation](#installation) •
[Features](#features) •
[Quick Start](#quick-start) •
[Documentation](#documentation) •
[Contributing](#contributing)

</div>

---

## Overview

BasePack is a comprehensive DevOps toolkit that streamlines Docker-based Laravel development and deployment. It provides pre-configured Docker containers, SSL certificate management, intelligent diagnostics, and powerful automation commands - everything you need to go from development to production seamlessly.

**Built by developers, for developers** - BasePack eliminates the complexity of Docker configuration while maintaining flexibility for customization.

### Why BasePack?

- **Zero Configuration** - Install and run in minutes with smart defaults
- **Production Ready** - Separate dev/prod configurations with security hardening
- **SSL Made Easy** - Automatic certificate detection and validation
- **Smart Diagnostics** - Auto-fix common issues with built-in diagnostic tools
- **Real-time Monitoring** - Live dashboard for container stats and health checks
- **Full Stack Included** - PHP, Nginx, MySQL, Redis, with Xdebug for development
- **Battle Tested** - Comprehensive test coverage across PHP 8.1-8.3 and Laravel 10-12

---

## Features

### 🚀 Quick Setup & Automation

- **One-Command Installation** - `php artisan basepack:install` sets up everything
- **Smart SSL Detection** - Automatically finds and validates certificates from 6+ locations
- **Project Name Inference** - Intelligently detects project name from various sources
- **Makefile Integration** - 295 lines of pre-configured Make commands for common tasks

### 🐳 Docker Environment

- **Multi-Version PHP Support** - PHP 8.1, 8.2, 8.3 with FPM
- **Modern Stack** - Nginx, MySQL 8.0, Redis 7.4
- **Xdebug Integration** - Pre-configured for PhpStorm and VSCode
- **Separate Configurations** - Optimized dev and production environments
- **Security Hardened** - Redis authentication, protected mode, disabled dangerous commands (CVE-2025-49844 mitigation)

### 🛠 Artisan Commands

| Command | Description |
|---------|-------------|
| `basepack:install` | Install and configure the complete toolkit |
| `basepack:build` | Build Docker containers with dev/prod options |
| `basepack:dashboard` | Real-time container monitoring with auto-refresh |
| `basepack:status` | Quick container status overview |
| `basepack:diagnose` | Comprehensive diagnostics with `--fix` auto-repair |
| `basepack:ssl-check` | Validate SSL certificates and check expiration |
| `basepack:test` | Run package tests with coverage options |
| `basepack:exec` | Execute commands in specific containers |
| `basepack:publish` | Publish specific assets (docker, make, compose, config) |

### 📊 Monitoring & Diagnostics

- **Real-time Dashboard** - CPU, memory, port mappings, health checks
- **Smart Diagnostics** - Detects missing files, invalid configs, SSL issues
- **Auto-Fix Capability** - One-command resolution of common problems
- **Detailed Reporting** - Clear, actionable feedback with next steps

### 🔒 Security Features

- **Redis Security Hardening** - Password auth, protected mode, network isolation
- **CVE-2025-49844 Mitigation** - Disabled dangerous Lua scripting commands
- **SSL/TLS Support** - Self-signed or production certificates
- **No Public Exposure** - Redis and MySQL accessible only via internal Docker network
- **Environment Validation** - Checks for weak passwords and security misconfigurations

---

## Requirements

- PHP 8.1, 8.2, or 8.3
- Laravel 10.x, 11.x, or 12.x
- Docker & Docker Compose
- Make (optional, but recommended)

---

## Installation

### 1. Install via Composer

```bash
composer require itcompass/basepack --dev
```

### 2. Run Installation Wizard

```bash
php artisan basepack:install
```

The installer will:
- Detect or prompt for SSL certificates
- Create `.env.docker` with secure defaults
- Publish Docker configuration files
- Generate Makefile for automation
- Create docker-compose files for dev/prod
- Update `.gitignore` with sensible defaults

### 3. Configure Environment

**Option A: New Project**
```bash
cp .env.docker .env
```

**Option B: Existing Project**
```bash
# Merge Docker settings from .env.docker into your existing .env
```

**Important**: Change default passwords!
```env
# .env or .env.docker
DB_PASSWORD=your_strong_database_password
REDIS_PASSWORD=your_strong_redis_password  # Required for security
```

Generate strong passwords:
```bash
# 32-character password
openssl rand -base64 32
```

### 4. Build and Start

```bash
make build
make start
make composer-install
make migrate
```

### 5. Access Your Application

```
https://localhost (or your configured domain)
```

---

## Quick Start

### Development Workflow

```bash
# Build containers
make build

# Start all services
make start

# View logs
make logs

# SSH into Laravel container
make ssh

# Run migrations
make migrate

# Install Composer dependencies
make composer-install

# Run tests
make test

# Stop all services
make stop
```

### Production Deployment

```bash
# Install for production
php artisan basepack:install --prod

# Build production containers
make build-prod

# Start production services
make start-prod
```

### Monitoring & Debugging

```bash
# Real-time dashboard
php artisan basepack:dashboard

# Check container status
php artisan basepack:status

# Run diagnostics
php artisan basepack:diagnose

# Auto-fix issues
php artisan basepack:diagnose --fix

# Check SSL certificates
php artisan basepack:ssl-check
```

---

## Configuration

### Project Structure

After installation, BasePack creates:

```
your-project/
├── .docker/                    # Docker configuration
│   ├── Dockerfile             # Main Laravel container
│   ├── nginx/                 # Nginx web server
│   ├── redis/                 # Redis configuration with security hardening
│   ├── dev/                   # Development configs (nginx, php, xdebug)
│   ├── prod/                  # Production configs
│   └── general/               # Shared configs (SSL, supervisor, cron)
├── .env.docker                # Docker-specific environment
├── Makefile                   # Automation commands
├── docker-compose.yml         # Development environment
└── docker-compose-prod.yml    # Production environment
```

### Environment Variables

Key variables in `.env.docker`:

```env
# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret              # CHANGE THIS!
DB_OUTER_PORT=3306

# Redis (Security Hardened)
REDIS_HOST=redis
REDIS_PASSWORD=CHANGE_ME_TO_STRONG_PASSWORD  # REQUIRED - CHANGE THIS!
REDIS_PORT=6379
# REDIS_OUTER_PORT removed for security - not exposed publicly

# Web Ports
WEB_PORT_HTTP=80
WEB_PORT_SSL=443

# Xdebug (dev only)
XDEBUG_CONFIG=main

# Project Name
COMPOSE_PROJECT_NAME=your_project
```

### SSL Certificates

BasePack looks for SSL certificates in these locations (in order):

1. `./ssl/`
2. `./.ssl/`
3. `./certificates/`
4. `./.docker/ssl/`
5. `/etc/ssl/certs/`
6. `~/.ssl/`

Or specify custom path:
```bash
php artisan basepack:install --ssl-path=/path/to/certificates
```

**Certificate Requirements:**
- Files must be named `cert.pem` and `key.pem`
- For development: self-signed certificates work fine
- For production: use valid certificates (Let's Encrypt recommended)

**Generate self-signed certificate:**
```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout key.pem -out cert.pem \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"
```

---

## Makefile Commands

BasePack includes a comprehensive Makefile with 30+ commands:

### Container Management
```bash
make build          # Build all containers
make build-prod     # Build production containers
make start          # Start all services
make stop           # Stop all services
make restart        # Restart all services
make destroy        # Stop and remove containers
```

### Development
```bash
make ssh            # SSH into Laravel container
make ssh-root       # SSH as root
make logs           # View all logs
make logs-app       # View Laravel logs only
```

### Composer & NPM
```bash
make composer-install    # Install PHP dependencies
make composer-update     # Update PHP dependencies
make npm-install        # Install Node dependencies
make npm-dev           # Run npm dev build
make npm-prod          # Run npm production build
```

### Database
```bash
make migrate           # Run migrations
make migrate-fresh     # Fresh migration
make migrate-rollback  # Rollback migrations
make seed             # Run database seeds
```

### Cache & Queue
```bash
make cache-clear      # Clear all caches
make queue-work       # Start queue worker
make queue-restart    # Restart queue workers
```

### Testing
```bash
make test            # Run all tests
make test-unit       # Run unit tests only
make test-coverage   # Run with coverage report
```

---

## Advanced Usage

### Custom Container Commands

```bash
# Execute command in Laravel container
php artisan basepack:exec --container=laravel "php artisan cache:clear"

# Execute as root
php artisan basepack:exec --container=mysql --root "mysql -u root -p"
```

### Selective Publishing

```bash
# Publish only docker files
php artisan basepack:publish --docker

# Publish only Makefile
php artisan basepack:publish --make

# Publish docker-compose files
php artisan basepack:publish --compose

# Force overwrite existing files
php artisan basepack:publish --docker --force
```

### Environment-Specific Installation

```bash
# Development only
php artisan basepack:install --dev

# Production only
php artisan basepack:install --prod

# Both environments
php artisan basepack:install
# (selects "both" when prompted)
```

### Xdebug Configuration

BasePack includes pre-configured Xdebug for development:

**PhpStorm:**
1. Enable Xdebug in `.env.docker`: `XDEBUG_CONFIG=main`
2. Configure PhpStorm: Settings → PHP → Servers
3. Add server with name `localhost` and port `80`
4. Enable "Start listening for PHP Debug Connections"

**VSCode:**
1. Use `.docker/dev/xdebug-main.ini` configuration
2. Install PHP Debug extension
3. Configure launch.json with provided settings

**macOS Users:**
- Use `XDEBUG_CONFIG=osx` for Docker Desktop on macOS
- Uses `xdebug-osx.ini` with Docker host gateway

---

## Testing

BasePack includes comprehensive tests across three tiers:

### Running Tests

```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Integration
vendor/bin/phpunit --testsuite=Feature

# With coverage
vendor/bin/phpunit --coverage-html coverage-report

# Via Makefile
make test
make test-unit
make test-coverage

# Via Artisan
php artisan basepack:test --suite=unit --coverage
```

### Test Coverage

BasePack is tested against:
- **PHP Versions:** 8.1, 8.2, 8.3
- **Laravel Versions:** 10.x, 11.x, 12.x
- **Dependency Variants:** prefer-lowest, prefer-stable
- **Total Matrix Combinations:** 14 test scenarios

See [TESTING.md](TESTING.md) for detailed testing documentation.

---

## Security

### Security Hardening

BasePack implements comprehensive security measures:

**Redis Security (CVE-2025-49844 Mitigation):**
- Password authentication required
- Protected mode enabled
- Network isolation (no public exposure)
- Dangerous commands disabled (FLUSHDB, FLUSHALL, CONFIG, etc.)
- Updated to Redis 7.4-alpine with latest patches

**Before Production Deployment:**
- [ ] Change all default passwords
- [ ] Use strong passwords (32+ characters)
- [ ] Verify Redis is not publicly exposed
- [ ] Use valid SSL certificates
- [ ] Review `.gitignore` excludes sensitive files
- [ ] Run `php artisan basepack:diagnose --fix`

### Reporting Vulnerabilities

See [SECURITY.md](SECURITY.md) for our security policy and how to report vulnerabilities.

**Contact:** contact@itcompass.io

---

## Troubleshooting

### Common Issues

**Containers won't start:**
```bash
php artisan basepack:diagnose --fix
make destroy
make build --no-cache
make start
```

**SSL certificate errors:**
```bash
php artisan basepack:ssl-check
# Verify certificate files exist and are not expired
```

**Permission issues:**
```bash
# Fix Laravel storage permissions
make ssh
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Redis connection refused:**
```bash
# Verify REDIS_PASSWORD is set in .env
# Check Redis container is running: make status
# View Redis logs: docker logs <project>-redis
```

**Xdebug not working:**
```bash
# Verify XDEBUG_CONFIG in .env.docker
# Rebuild containers: make build
# Check Xdebug is loaded: make ssh, then php -m | grep xdebug
```

### Getting Help

1. Run diagnostics: `php artisan basepack:diagnose`
2. Check logs: `make logs`
3. Review configuration: `php artisan basepack:status`
4. Consult [TESTING.md](TESTING.md) and [SECURITY.md](SECURITY.md)
5. [Open an issue](https://github.com/ibigforko/basepack/issues)

---

## Contributing

We welcome contributions! Please follow these guidelines:

### Development Setup

```bash
# Clone repository
git clone https://github.com/ibigforko/basepack.git
cd basepack

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage-report
```

### Contribution Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`vendor/bin/phpunit`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write comprehensive tests (unit, integration, feature)
- Document new features in README.md
- Update CHANGELOG.md with your changes
- Ensure CI/CD pipeline passes

### Testing Requirements

All contributions must include tests and pass the full test matrix:
- PHP 8.1, 8.2, 8.3
- Laravel 10.x, 11.x, 12.x
- Both prefer-lowest and prefer-stable dependencies

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

## Credits

### Created By

**ITCompass** - Digital Solutions Agency
- Website: [itcompass.io](https://itcompass.io)
- Email: contact@itcompass.io

### Lead Developer

**Artem Shevchenko** ([@ibigforko](https://github.com/ibigforko))

### Contributors

See [all contributors](https://github.com/ibigforko/basepack/graphs/contributors) who have helped make BasePack better.

---

## License

BasePack is open-source software licensed under the [MIT License](LICENSE.md).

Copyright (c) 2025 ITCompass

---

## Support

### Commercial Support

Need help with implementation, custom features, or dedicated support?

Contact us: **contact@itcompass.io**

### Community Support

- [GitHub Issues](https://github.com/ibigforko/basepack/issues) - Bug reports and feature requests
- [GitHub Discussions](https://github.com/ibigforko/basepack/discussions) - Questions and community help

---

## Acknowledgments

BasePack is built with and inspired by these amazing projects:

- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [Docker](https://docker.com) - Containerization platform
- [Redis](https://redis.io) - In-memory data structure store
- [Nginx](https://nginx.org) - High-performance web server
- [MySQL](https://mysql.com) - Popular open-source database

---

<div align="center">

**Made with ❤️ by [ITCompass](https://itcompass.io)**

⭐ Star us on [GitHub](https://github.com/ibigforko/basepack) if you find BasePack useful!

[Installation](#installation) •
[Features](#features) •
[Documentation](#documentation) •
[Contributing](#contributing) •
[License](#license)

</div>
