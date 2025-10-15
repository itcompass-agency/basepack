#!/bin/bash

# Test Matrix Simulation Script
# This script simulates the CI matrix testing locally

set -e

echo "🚀 BasePack CI Matrix Testing Simulation"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "info")
            echo -e "${YELLOW}ℹ️  $message${NC}"
            ;;
        "success")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "error")
            echo -e "${RED}❌ $message${NC}"
            ;;
    esac
}

# Function to run tests for a specific suite
run_test_suite() {
    local suite=$1
    print_status "info" "Running $suite tests..."

    if vendor/bin/phpunit --testsuite=$suite --colors=always; then
        print_status "success" "$suite tests passed"
        return 0
    else
        print_status "error" "$suite tests failed"
        return 1
    fi
}

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    print_status "error" "PHPUnit not found. Run 'composer install' first."
    exit 1
fi

# Check if phpunit.xml exists
if [ ! -f "phpunit.xml" ]; then
    print_status "error" "phpunit.xml not found."
    exit 1
fi

print_status "info" "Current PHP version: $(php -v | head -n1)"
print_status "info" "Current Laravel version: $(composer show laravel/framework --format=json 2>/dev/null | jq -r '.versions[0]' 2>/dev/null || echo 'Not installed')"

echo ""
echo "📊 Running Test Matrix..."
echo "========================"

# Create reports directory
mkdir -p reports

# Test counters
total_tests=0
passed_tests=0

# Test suites to run
suites=("Unit" "Integration" "Feature")

for suite in "${suites[@]}"; do
    total_tests=$((total_tests + 1))
    echo ""
    if run_test_suite $suite; then
        passed_tests=$((passed_tests + 1))
    fi
done

echo ""
echo "📈 Test Summary"
echo "==============="
echo "Total test suites: $total_tests"
echo "Passed: $passed_tests"
echo "Failed: $((total_tests - passed_tests))"

if [ $passed_tests -eq $total_tests ]; then
    print_status "success" "All test suites passed! 🎉"

    echo ""
    print_status "info" "Running complete test suite with coverage..."
    if vendor/bin/phpunit --coverage-text --colors=always 2>/dev/null || vendor/bin/phpunit --colors=always; then
        print_status "success" "Complete test suite passed!"
    else
        print_status "error" "Complete test suite failed"
        exit 1
    fi
else
    print_status "error" "Some test suites failed"
    exit 1
fi

echo ""
print_status "success" "Matrix testing simulation completed successfully!"