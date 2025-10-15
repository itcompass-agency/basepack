# Security Policy

## Supported Versions

We actively support the latest version of BasePack with security updates.

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Redis Security Configuration

### CVE-2025-49844 Mitigation

BasePack includes comprehensive security hardening for Redis to protect against CVE-2025-49844 and other vulnerabilities:

**Implemented Security Measures:**

1. **Authentication Required**
   - Redis requires password authentication via `REDIS_PASSWORD` environment variable
   - No default passwords - must be changed from `CHANGE_ME_TO_STRONG_PASSWORD`

2. **Network Isolation**
   - Redis is NOT exposed publicly by default
   - Access only via internal Docker network
   - No port mapping to host in production configuration

3. **Dangerous Commands Disabled**
   - `FLUSHDB`, `FLUSHALL`, `CONFIG`, `SHUTDOWN`, `DEBUG` commands are disabled
   - Prevents unauthorized data deletion and configuration changes

4. **Protected Mode Enabled**
   - Redis protected-mode prevents external connections without authentication
   - Bind address configured with authentication enforcement

5. **Updated Redis Version**
   - Uses Redis 7.4-alpine (latest stable) instead of older alpine versions
   - Includes latest security patches

**Configuration Files:**

- `.docker/redis/redis.conf` - Hardened Redis configuration
- `docker-compose.yml` - Development environment with security defaults
- `docker-compose-prod.yml` - Production environment with enhanced security
- `.env.docker` - Environment variables with security comments

### Security Checklist for Deployment

Before deploying to production, ensure:

- [ ] Changed `REDIS_PASSWORD` from default value to a strong password (minimum 32 characters)
- [ ] Verified Redis port is NOT exposed in docker-compose (no ports mapping)
- [ ] Confirmed `protected-mode yes` in redis.conf
- [ ] Reviewed disabled commands in redis.conf match your security requirements
- [ ] SSL certificates are properly configured and not expired
- [ ] `.env` file is added to `.gitignore` and not committed to repository
- [ ] Database passwords are strong and unique
- [ ] Docker containers are running with non-root users where possible

### Generating Strong Passwords

Generate a strong Redis password:

```bash
# Using OpenSSL (32 character password)
openssl rand -base64 32

# Using /dev/urandom (48 character password)
head -c 48 /dev/urandom | base64
```

Update in `.env.docker` or `.env`:
```env
REDIS_PASSWORD=your_generated_strong_password_here
```

## Reporting a Vulnerability

If you discover a security vulnerability in BasePack, please report it to:

**Email:** contact@itcompass.io

**Subject:** [SECURITY] BasePack Vulnerability Report

**Please include:**

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

**Response Time:**

- We will acknowledge receipt within 48 hours
- We will provide an initial assessment within 7 days
- We will work on a fix and keep you updated on progress
- Once fixed, we will credit you in the release notes (unless you prefer to remain anonymous)

**Please do NOT:**

- Create public GitHub issues for security vulnerabilities
- Disclose the vulnerability publicly before we've had a chance to address it
- Exploit the vulnerability beyond what is necessary to demonstrate it

## Security Best Practices

### Docker Security

1. **Keep Images Updated**
   ```bash
   docker pull redis:7.4-alpine
   docker pull mysql:8.0
   docker pull php:8.3-fpm-alpine
   ```

2. **Scan for Vulnerabilities**
   ```bash
   docker scan basepack-laravel:latest
   ```

3. **Limit Container Resources**
   - Configure memory and CPU limits in docker-compose.yml
   - Prevent resource exhaustion attacks

### Application Security

1. **Environment Variables**
   - Never commit `.env` or `.env.docker` files
   - Use different credentials for dev/staging/production
   - Rotate credentials regularly

2. **SSL/TLS Certificates**
   - Use valid certificates in production (Let's Encrypt recommended)
   - Ensure certificates are not expired
   - Use strong cipher suites in Nginx configuration

3. **Database Security**
   - Use strong, unique passwords
   - Limit database user permissions
   - Enable MySQL query logging for auditing
   - Regular backups with encryption

4. **Regular Updates**
   ```bash
   # Update BasePack package
   composer update itcompass/basepack

   # Rebuild Docker containers
   make build --no-cache
   ```

## Security Resources

- [Redis Security Best Practices](https://redis.io/docs/management/security/)
- [Docker Security](https://docs.docker.com/engine/security/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

## License

This security policy is part of the BasePack package, released under the MIT License.

Copyright (c) 2025 ITCompass - https://itcompass.io
