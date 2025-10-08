# Test automation for BasePack

.PHONY: test test-unit test-integration test-feature test-coverage test-watch help

# Colors for output
GREEN=\033[0;32m
YELLOW=\033[1;33m
RED=\033[0;31m
NC=\033[0m # No Color

# Default target
help: ## Show this help message
	@echo "$(GREEN)BasePack Test Suite$(NC)"
	@echo ""
	@echo "$(YELLOW)Available targets:$(NC)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""

install: ## Install dependencies
	@echo "$(YELLOW)Installing dependencies...$(NC)"
	composer install
	@echo "$(GREEN)Dependencies installed!$(NC)"

test: ## Run all tests
	@echo "$(YELLOW)Running all tests...$(NC)"
	vendor/bin/phpunit --colors=always
	@echo "$(GREEN)All tests completed!$(NC)"

test-unit: ## Run unit tests only
	@echo "$(YELLOW)Running unit tests...$(NC)"
	vendor/bin/phpunit --testsuite=Unit --colors=always
	@echo "$(GREEN)Unit tests completed!$(NC)"

test-integration: ## Run integration tests only
	@echo "$(YELLOW)Running integration tests...$(NC)"
	vendor/bin/phpunit --testsuite=Integration --colors=always
	@echo "$(GREEN)Integration tests completed!$(NC)"

test-feature: ## Run feature tests only
	@echo "$(YELLOW)Running feature tests...$(NC)"
	vendor/bin/phpunit --testsuite=Feature --colors=always
	@echo "$(GREEN)Feature tests completed!$(NC)"

test-coverage: ## Run tests with coverage report
	@echo "$(YELLOW)Running tests with coverage...$(NC)"
	vendor/bin/phpunit --coverage-html coverage-report --coverage-text --colors=always
	@echo "$(GREEN)Coverage report generated in coverage-report/$(NC)"

test-coverage-text: ## Run tests with text coverage only
	@echo "$(YELLOW)Running tests with text coverage...$(NC)"
	vendor/bin/phpunit --coverage-text --colors=always

test-watch: ## Watch files and run tests automatically (requires entr)
	@echo "$(YELLOW)Watching for changes... (Press Ctrl+C to stop)$(NC)"
	find src tests -name "*.php" | entr -c make test-unit

test-filter: ## Run tests matching a filter pattern (use FILTER=pattern)
	@echo "$(YELLOW)Running filtered tests: $(FILTER)$(NC)"
	vendor/bin/phpunit --filter="$(FILTER)" --colors=always

test-quick: ## Run a quick test subset for development
	@echo "$(YELLOW)Running quick test subset...$(NC)"
	vendor/bin/phpunit --testsuite=Unit --stop-on-failure --colors=always

test-parallel: ## Run tests in parallel (if parallel extension available)
	@echo "$(YELLOW)Running tests in parallel...$(NC)"
	vendor/bin/phpunit --colors=always --process-isolation

clean: ## Clean test artifacts
	@echo "$(YELLOW)Cleaning test artifacts...$(NC)"
	rm -rf coverage-report/
	rm -f coverage.xml coverage.txt
	rm -rf .phpunit.cache/
	@echo "$(GREEN)Cleanup completed!$(NC)"

validate: ## Validate composer.json and check autoload
	@echo "$(YELLOW)Validating package...$(NC)"
	composer validate --strict
	composer dump-autoload --optimize
	@echo "$(GREEN)Package validation completed!$(NC)"

ci: ## Run CI pipeline locally
	@echo "$(YELLOW)Running CI pipeline...$(NC)"
	make validate
	make test-unit
	make test-integration
	make test-feature
	make test-coverage-text
	@echo "$(GREEN)CI pipeline completed!$(NC)"

test-matrix: ## Run matrix testing simulation
	@echo "$(YELLOW)Running matrix testing simulation...$(NC)"
	./scripts/test-matrix.sh
	@echo "$(GREEN)Matrix testing completed!$(NC)"

# Development shortcuts
dev-test: test-unit ## Alias for test-unit (development)
dev-coverage: test-coverage ## Alias for test-coverage (development)
dev-clean: clean ## Alias for clean (development)