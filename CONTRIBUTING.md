# Contributing to BasePack

First off, thank you for considering contributing to BasePack! It's people like you that make BasePack such a great tool for the Laravel community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Enhancements](#suggesting-enhancements)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please be respectful, inclusive, and constructive in all interactions.

### Our Standards

**Positive behaviors include:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behaviors include:**
- Trolling, insulting/derogatory comments, and personal attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

## Getting Started

### Prerequisites

Before you begin, ensure you have:

- PHP 8.1, 8.2, or 8.3 installed
- Composer
- Git
- Docker and Docker Compose (for testing Docker features)
- A GitHub account

### Setting Up Development Environment

1. **Fork the repository** on GitHub

2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/basepack.git
   cd basepack
   ```

3. **Add upstream remote**:
   ```bash
   git remote add upstream https://github.com/ibigforko/basepack.git
   ```

4. **Install dependencies**:
   ```bash
   composer install
   ```

5. **Verify tests pass**:
   ```bash
   vendor/bin/phpunit
   ```

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates.

**When creating a bug report, include:**

- **Clear, descriptive title** - Summarize the problem
- **Exact steps to reproduce** - Be specific
- **Expected behavior** - What should happen
- **Actual behavior** - What actually happens
- **Environment details**:
  - BasePack version
  - PHP version
  - Laravel version
  - Operating system
  - Docker version (if relevant)
- **Code samples** - Minimal reproducible example
- **Screenshots** - If applicable
- **Possible solution** - If you have ideas

**Example:**

```markdown
**Title:** basepack:install fails when SSL path contains spaces

**Steps to Reproduce:**
1. Install BasePack
2. Run: php artisan basepack:install --ssl-path="/path with spaces/ssl"
3. Observe error

**Expected:** Installation should handle paths with spaces
**Actual:** Command fails with "path not found" error

**Environment:**
- BasePack: 1.0.0
- PHP: 8.2.10
- Laravel: 11.0
- OS: macOS 14.1

**Suggested Fix:**
Quote the path variable in InstallCommand.php line 72
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues.

**When suggesting enhancements, include:**

- **Clear, descriptive title**
- **Use case** - Why is this enhancement useful?
- **Detailed description** - What should it do?
- **Examples** - How would it work?
- **Alternatives considered** - Other ways to solve this
- **Additional context** - Screenshots, mockups, etc.

### Pull Requests

Pull requests are the best way to propose changes to the codebase.

**Good pull requests include:**
- A clear description of the problem and solution
- Tests for new functionality
- Updated documentation
- Clean, readable code following our standards
- Adherence to our testing requirements

## Development Workflow

### 1. Create a Branch

```bash
# Update your local master
git checkout master
git pull upstream master

# Create a feature branch
git checkout -b feature/your-feature-name

# Or for bug fixes
git checkout -b fix/issue-description
```

**Branch naming conventions:**
- `feature/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `docs/description` - Documentation only
- `refactor/description` - Code refactoring
- `test/description` - Test improvements

### 2. Make Your Changes

**Best practices:**
- Make focused, atomic commits
- Write clear commit messages
- Follow our coding standards
- Add tests for new functionality
- Update documentation as needed

**Commit message format:**
```
Type: Short description (50 chars or less)

More detailed explanation if needed. Wrap at 72 characters.
Explain the problem this commit solves and why this approach
was chosen.

Fixes #123
```

**Types:**
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `test:` - Test additions or changes
- `chore:` - Build process, dependencies, etc.

**Examples:**
```
feat: Add PostgreSQL support to docker-compose

- Add postgres service to docker-compose.yml
- Create postgres.conf configuration file
- Update .env.docker.stub with postgres variables
- Add tests for postgres setup

Closes #45

---

fix: Handle SSL paths with spaces in install command

Previously, paths with spaces would cause the install command
to fail. This change quotes the path variables properly.

Fixes #123

---

docs: Update README with PostgreSQL configuration

Add section explaining how to configure PostgreSQL instead
of MySQL when using BasePack.
```

### 3. Write Tests

**All code changes must include tests.**

BasePack uses a 3-tier testing architecture:

#### Unit Tests (`tests/Unit/`)

Test individual components in isolation.

```php
<?php

namespace ITCompass\BasePack\Tests\Unit;

use ITCompass\BasePack\Helpers\DockerHelper;
use ITCompass\BasePack\Tests\TestCase;

class DockerHelperTest extends TestCase
{
    public function test_can_check_if_docker_is_running(): void
    {
        $helper = new DockerHelper();

        // Test returns boolean
        $result = $helper->isDockerRunning();
        $this->assertIsBool($result);
    }
}
```

#### Integration Tests (`tests/Integration/`)

Test component interactions.

```php
<?php

namespace ITCompass\BasePack\Tests\Integration;

use ITCompass\BasePack\Helpers\EnvHelper;
use ITCompass\BasePack\Tests\TestCase;
use Illuminate\Support\Facades\File;

class EnvHelperIntegrationTest extends TestCase
{
    public function test_can_update_env_file(): void
    {
        // Setup test environment
        $envPath = base_path('.env.test');
        File::put($envPath, "APP_NAME=Laravel\nAPP_ENV=local\n");

        // Test update
        $helper = new EnvHelper();
        $helper->updateEnv($envPath, 'APP_NAME', 'BasePack');

        // Verify
        $content = File::get($envPath);
        $this->assertStringContainsString('APP_NAME=BasePack', $content);

        // Cleanup
        File::delete($envPath);
    }
}
```

#### Feature Tests (`tests/Feature/`)

Test complete workflows end-to-end.

```php
<?php

namespace ITCompass\BasePack\Tests\Feature;

use ITCompass\BasePack\Tests\TestCase;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    public function test_install_command_creates_required_files(): void
    {
        // Setup
        $this->mockSSLCertificates();

        // Run install command
        $this->artisan('basepack:install', ['--dev' => true, '--force' => true])
            ->expectsOutput('Installing BasePack DevOps toolkit...')
            ->assertExitCode(0);

        // Verify files created
        $this->assertFileExists(base_path('.env.docker'));
        $this->assertFileExists(base_path('docker-compose.yml'));
        $this->assertDirectoryExists(base_path('.docker'));
    }

    protected function mockSSLCertificates(): void
    {
        $sslDir = base_path('ssl');
        File::ensureDirectoryExists($sslDir);
        File::put($sslDir . '/cert.pem', 'fake cert');
        File::put($sslDir . '/key.pem', 'fake key');
    }
}
```

### 4. Run Tests

```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Integration
vendor/bin/phpunit --testsuite=Feature

# With coverage
vendor/bin/phpunit --coverage-html coverage-report

# Single test
vendor/bin/phpunit --filter test_install_command_creates_required_files
```

**All tests must pass** before submitting a pull request.

### 5. Update Documentation

If your changes affect user-facing functionality:

- Update README.md
- Update relevant command descriptions
- Add examples if applicable
- Update CHANGELOG.md

### 6. Push and Create Pull Request

```bash
# Push your branch
git push origin feature/your-feature-name

# Create pull request on GitHub
# Use the PR template and fill in all sections
```

## Coding Standards

### PSR-12 Compliance

BasePack follows PSR-12 coding standards.

**Key points:**
- 4 spaces for indentation (no tabs)
- Opening braces on same line for control structures
- Opening braces on new line for classes/methods
- Use type hints for all parameters and return types
- One statement per line

**Example:**

```php
<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'basepack:example {name : The user name}';

    protected $description = 'Example command description';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (empty($name)) {
            $this->error('Name is required');
            return Command::FAILURE;
        }

        $this->info("Hello, {$name}!");

        return Command::SUCCESS;
    }

    protected function validateName(string $name): bool
    {
        return strlen($name) > 0 && strlen($name) <= 255;
    }
}
```

### Laravel Best Practices

- Use Laravel facades appropriately
- Leverage service container for dependency injection
- Use Eloquent relationships over manual queries
- Follow Laravel naming conventions

### Code Quality

**Keep functions small and focused:**
```php
// Good - single responsibility
protected function copySSLCertificates(): void
{
    $destination = $this->getSSLDestination();
    $this->ensureDirectoryExists($destination);
    $this->copyCertificateFiles($destination);
}

// Bad - doing too much
protected function setupSSL(): void
{
    // 100 lines of code doing multiple things...
}
```

**Use meaningful variable names:**
```php
// Good
$dockerContainerName = $this->generateContainerName();
$sslCertificatePath = $this->detectCertificatePath();

// Bad
$dcn = $this->getName();
$path = $this->detect();
```

**Add comments for complex logic:**
```php
// Good - explains WHY
// Extract domain from certificate CN field because
// we need it for nginx server_name configuration
$domain = $this->extractDomainFromCertificate($certPath);

// Bad - explains WHAT (obvious from code)
// Set domain variable
$domain = $this->extractDomainFromCertificate($certPath);
```

## Testing Requirements

### Coverage Requirements

- Minimum 80% code coverage for new features
- All public methods must have tests
- Critical paths must have integration tests
- CLI commands must have feature tests

### Test Quality

**Good tests are:**
- **Independent** - Don't rely on other tests
- **Repeatable** - Same result every time
- **Fast** - Run quickly
- **Self-validating** - Clear pass/fail
- **Timely** - Written alongside code

**Example of good test:**

```php
public function test_diagnose_command_detects_missing_docker_files(): void
{
    // Arrange - clear, focused setup
    $this->cleanDockerDirectory();

    // Act - single action being tested
    $this->artisan('basepack:diagnose')
        ->expectsOutput('Missing .docker directory')
        ->assertExitCode(1);

    // Assert - clear expectation
    // (assertions already in expectsOutput above)
}
```

### Test Matrix

All pull requests are automatically tested against:

- **PHP versions:** 8.1, 8.2, 8.3
- **Laravel versions:** 10.x, 11.x, 12.x
- **Dependencies:** prefer-lowest, prefer-stable

**Your code must pass all combinations** (14 total).

## Pull Request Process

### Before Submitting

**Checklist:**

- [ ] All tests pass (`vendor/bin/phpunit`)
- [ ] Code follows PSR-12 standards
- [ ] New tests added for new functionality
- [ ] Documentation updated (README, CHANGELOG, etc.)
- [ ] Commit messages are clear and descriptive
- [ ] No merge conflicts with master branch
- [ ] Code is self-reviewed

### Submitting

1. **Push your branch** to your fork
2. **Open a pull request** against `ibigforko/basepack:master`
3. **Fill out the PR template** completely
4. **Wait for CI checks** to pass
5. **Respond to review feedback** promptly

### PR Title Format

```
Type: Brief description (50 chars or less)
```

Examples:
- `feat: Add PostgreSQL support`
- `fix: Handle SSL paths with spaces`
- `docs: Update installation guide`
- `test: Add integration tests for EnvHelper`

### PR Description Template

```markdown
## Description
Brief description of what this PR does.

## Motivation and Context
Why is this change required? What problem does it solve?
Fixes #(issue number)

## How Has This Been Tested?
- [ ] Unit tests
- [ ] Integration tests
- [ ] Feature tests
- [ ] Manual testing

Describe your test configuration:
- PHP version:
- Laravel version:
- OS:

## Types of Changes
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)
- [ ] Documentation update

## Checklist
- [ ] My code follows the code style of this project
- [ ] I have updated the documentation accordingly
- [ ] I have added tests to cover my changes
- [ ] All new and existing tests pass
- [ ] I have updated CHANGELOG.md
```

### Review Process

1. **Automated checks** run (tests, code style)
2. **Maintainer review** (usually within 48 hours)
3. **Address feedback** if requested
4. **Approval** from at least one maintainer
5. **Merge** by maintainer

### After Merge

- Your contribution will be included in the next release
- You'll be credited in release notes and contributors list
- Thank you for making BasePack better!

## Style Guide

### PHP

```php
// Good
public function buildContainers(string $environment, bool $noCache = false): int
{
    $this->info("Building {$environment} containers...");

    $command = "docker-compose build";
    if ($noCache) {
        $command .= " --no-cache";
    }

    return $this->runCommand($command);
}

// Bad
public function buildContainers($environment, $noCache = false)
{
    $this->info("Building ".$environment." containers...");
    $command = "docker-compose build";
    if($noCache == true) $command .= " --no-cache";
    return $this->runCommand($command);
}
```

### Documentation

```markdown
<!-- Good - clear, actionable -->
## Installation

Install BasePack via Composer:

```bash
composer require itcompass/basepack --dev
```

Then run the installation wizard:

```bash
php artisan basepack:install
```

<!-- Bad - vague, missing details -->
## Installation

Use composer to install and run install command.
```

## Questions?

Feel free to:

- **Open a discussion** on GitHub Discussions
- **Ask in issues** - we'll help guide you
- **Email us** at contact@itcompass.io

We're here to help! Don't hesitate to ask questions.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to BasePack!**

Made with ❤️ by the BasePack community
