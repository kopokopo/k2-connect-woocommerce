# k2-connect-woocommerce
A woo commerce plugin to help you connect your application to the K2 Connect APIs


## 🧪 Testing Guide

This section explains how to set up and run **all tests** for this plugin, including **PHPUnit** (backend / WordPress) and **Playwright** (end-to-end).

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
