# Contributing to k2_connect_woocommerce

🎉 Thank you for considering contributing to **k2_connect_woocommerce**!  
Your contributions help improve the official Kopo Kopo for WooCommerce plugin.  

---

## How to Contribute

### 1. Fork & Clone
- Fork the repository on GitHub
- Clone your fork locally:
```bash
git clone https://github.com/kopokopo/k2-connect-woocommerce.git
cd k2-connect-woocommerce
```
2. Create a Branch
- Use a descriptive branch name:
```bash
git checkout -b feature/your-feature-name
```
3. Make Changes
- Write clear, well-structured code
- Follow Effective Wordpress plugin development guidelines

4. Write & Run Tests
- Add tests for new features in tests/
- Run all tests

### Commit Guidelines
Use clear commit messages:
- `feat: add STK Push payment initiation`
- `fix: handle network timeout gracefully`
- `docs: update usage example in README`

### Pull Requests
1. Push your branch to your fork:

```bash
git push origin feature/your-feature-name
```
2. Open a Pull Request (PR) against the `development` branch of this repo

3. Ensure your PR description:
- Explains why the change is needed
- Shows what was changed
- Includes screenshots/logs if relevant

### Code of Conduct
By participating in this project, you agree to uphold the Contributor Covenant.
Be respectful, inclusive, and constructive.


## 🧪 Testing Guide

This section explains how to set up and run **all tests** for this plugin, including **PHPUnit** (backend / WordPress) and **Playwright** (UI Integration tests).

---

### Prerequisites

Ensure you have the following installed:

- PHP (compatible with WordPress & PHPUnit)
- Composer
- Node.js (v18+ recommended)
- npm or pnpm
- MySQL / MariaDB
- WordPress test utilities

---

### Set Up the WordPress Test Environment

Setting up the WordPress Test environment creates a test database that resets during test runs therefore DO NOT use your production or development credentials but rather create a new database for testing purposes. 

Run the WordPress test installer using your local database credentials:

```bash
bin/install-wp-tests.sh <db_name> <db_user> <db_password> <db_host> <wp_version>
```

Example
```bash
bin/install-wp-tests.sh wp_test root 'my_password' localhost latest
```

### Set Up the Plugin Test Environment

Prepare the plugin and dependencies for testing:

```bash
bin/setup-test-env.sh
```

This script typically:

- Installs required plugins (e.g. WooCommerce)
- Clears caches
- Prepares WordPress for testing

### Run PHPUnit Tests

Run all backend and WordPress unit tests:

```bash
php vendor/bin/phpunit
```

For more readable output:

```bash
php vendor/bin/phpunit --testdox
```

If no tests run, ensure:

- Test classes extend WP_UnitTestCase
- Test class names start with Test_
- Test methods start with test_
- phpunit.xml is correctly configured

### Set Up Playwright

Install Playwright and required browsers:

```bash
npx playwright install
```

Copy the contents of `.env.sample` to a `.env` file in the same directory. Replace the environment variables with your wordpress application's variables. This is necessary for the UI tests to access your WooCommerce store.

### Run Playwright UI Integration Tests

Run all UI integration tests:

```bash
npx playwright test tests/e2e
```

Run tests with the browser UI visible (useful for debugging):

```bash
npx playwright test tests/e2e --headed
```


Run a single test file:

```bash
npx playwright test tests/e2e/<test-file>.spec.ts
```
