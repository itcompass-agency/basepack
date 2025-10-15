# Changelog

All notable changes to BasePack will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive README.md with installation, usage, and troubleshooting guides
- SECURITY.md with security policy and vulnerability reporting guidelines
- CHANGELOG.md for tracking version history

### Security
- **[CVE-2025-49844]** Implemented comprehensive Redis security hardening
  - Added `redis.conf` with security best practices
  - Enabled Redis password authentication requirement via `REDIS_PASSWORD`
  - Removed public port exposure for Redis (network isolation)
  - Disabled dangerous commands (FLUSHDB, FLUSHALL, CONFIG, SHUTDOWN, DEBUG)
  - Enabled protected mode to prevent unauthorized access
  - Upgraded Redis to version 7.4-alpine (latest stable with security patches)
  - Added healthcheck with authentication support
  - Updated `.env.docker.stub` with security warnings and strong password requirement

### Changed
- Updated docker-compose.yml to use Redis 7.4-alpine with security configuration
- Updated docker-compose-prod.yml with enhanced security for production
- Modified environment template to require strong Redis password

### Documentation
- Added security checklist for production deployment
- Documented strong password generation methods
- Added troubleshooting guide for common issues
- Created comprehensive feature documentation
- Added contribution guidelines and code standards

## [1.0.0] - 2025-01-XX

### Added
- Initial release of BasePack DevOps Toolkit
- 9 comprehensive Artisan commands:
  - `basepack:install` - Complete installation wizard
  - `basepack:build` - Docker container builder
  - `basepack:dashboard` - Real-time monitoring dashboard
  - `basepack:status` - Container status checker
  - `basepack:diagnose` - Diagnostic tool with auto-fix
  - `basepack:ssl-check` - SSL certificate validator
  - `basepack:test` - Test runner with coverage
  - `basepack:exec` - Container command executor
  - `basepack:publish` - Selective asset publisher
- Complete Docker stack configuration:
  - PHP 8.1/8.2/8.3 support
  - Nginx web server with SSL
  - MySQL 8.0 database
  - Redis for caching and sessions
  - Xdebug for development debugging
- Smart SSL certificate detection and management
  - Scans 6+ default locations
  - Extracts domain from certificate
  - Validates expiration dates
- Comprehensive Makefile with 30+ automation commands
- Separate development and production configurations
- Helper classes for Docker and environment management
- Real-time container monitoring dashboard
- Auto-fix diagnostic capabilities
- Project name inference from multiple sources

### Testing
- Comprehensive test suite with 3-tier architecture:
  - Unit tests for isolated components
  - Integration tests for component interactions
  - Feature tests for end-to-end workflows
- CI/CD pipeline with GitHub Actions
- Test matrix across:
  - PHP 8.1, 8.2, 8.3
  - Laravel 10.x, 11.x, 12.x
  - prefer-lowest and prefer-stable dependencies
- Code coverage reporting to Codecov
- 14 total test matrix combinations

### Documentation
- TESTING.md - Comprehensive testing guide
- LICENSE.md - MIT License
- Inline code documentation
- Command help text and descriptions

### Support
- Laravel 10.x, 11.x, 12.x compatibility
- PHP 8.1, 8.2, 8.3 support
- Docker and Docker Compose integration
- Make automation support

---

## Version History

### Semantic Versioning

BasePack follows Semantic Versioning (SemVer):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backward-compatible functionality additions
- **PATCH** version for backward-compatible bug fixes

### Upgrade Guides

When upgrading between major versions, please refer to the upgrade guide in the release notes.

---

## Security Advisories

### How We Handle Security Updates

Security vulnerabilities are treated with highest priority:

1. **Critical** vulnerabilities are patched immediately (within 24-48 hours)
2. **High** severity issues are addressed within 7 days
3. **Medium/Low** severity fixes are included in next scheduled release

All security updates are:
- Released as patch versions (e.g., 1.0.1, 1.0.2)
- Announced in release notes with `[SECURITY]` tag
- Documented in SECURITY.md
- Backported to supported versions when possible

### Current Security Fixes

- **[CVE-2025-49844]** Redis Lua scripting vulnerability mitigation
  - Affects: All versions prior to 1.0.0 release
  - Fixed in: Version 1.0.0+
  - Severity: Critical
  - Action Required: Update Redis password, rebuild containers

---

## Deprecation Policy

### How We Deprecate Features

When deprecating features, we follow this timeline:

1. **Announcement** - Feature marked as deprecated with warning message
2. **Deprecation Period** - Feature continues to work for at least one major version
3. **Removal** - Feature removed in next major version

Example:
- v1.5.0: Feature X deprecated (warning added)
- v1.6.0 - v1.9.x: Feature X still works with deprecation warning
- v2.0.0: Feature X removed

### Currently Deprecated

None at this time.

---

## Future Roadmap

### Planned Features

Potential additions for future versions:

- [ ] PostgreSQL database support
- [ ] MongoDB container configuration
- [ ] Elasticsearch integration
- [ ] CI/CD pipeline templates (GitHub Actions, GitLab CI)
- [ ] Kubernetes deployment manifests
- [ ] Auto-backup automation for databases
- [ ] Horizontal scaling support
- [ ] Multi-environment configuration (staging, QA)
- [ ] Performance monitoring integration (New Relic, DataDog)
- [ ] Log aggregation (ELK stack integration)

Want to contribute to these features? See [CONTRIBUTING.md](CONTRIBUTING.md)!

---

## Breaking Changes

### Version 1.0.0

None - initial release.

---

## Links

- [GitHub Repository](https://github.com/ibigforko/basepack)
- [Issue Tracker](https://github.com/ibigforko/basepack/issues)
- [Packagist](https://packagist.org/packages/itcompass/basepack)
- [ITCompass Website](https://itcompass.io)

---

**Maintained by [ITCompass](https://itcompass.io)**

For support, contact: contact@itcompass.io
