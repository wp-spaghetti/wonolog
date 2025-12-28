![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/wp-spaghetti/wonolog/total)
![GitHub Actions Workflow Status](https://github.com/wp-spaghetti/wonolog/actions/workflows/release.yml/badge.svg)
![GitHub Issues](https://img.shields.io/github/issues/wp-spaghetti/wonolog)
![GitHub Release](https://img.shields.io/github/v/release/wp-spaghetti/wonolog)
![License](https://img.shields.io/github/license/wp-spaghetti/wonolog)
<!--
![Coverage Status](https://img.shields.io/codecov/c/github/wp-spaghetti/wonolog)
![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=wp-spaghetti_wonolog&metric=alert_status)
![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=wp-spaghetti_wonolog&metric=security_rating)
![Known Vulnerabilities](https://snyk.io/test/github/wp-spaghetti/wonolog/badge.svg)
![PHP Version](https://img.shields.io/badge/php->=8.1-blue)
![Code Climate](https://img.shields.io/codeclimate/maintainability/wp-spaghetti/wonolog)
-->

# Wonolog (WordPress Plugin)

An **opinionated** WordPress logging plugin built on top of [Inpsyde's Wonolog](https://github.com/inpsyde/Wonolog). This plugin provides a pre-configured, production-ready logging solution with sensible defaults, environment-aware configuration, and comprehensive error tracking.

## What Makes This "Opinionated"?

This plugin takes Wonolog's powerful PSR-3 logging capabilities and wraps them in a batteries-included configuration that works out of the box. Instead of requiring manual setup, it provides:

- **Environment-aware logging**: Automatically adjusts behavior based on `WP_DEBUG` and `WP_ENVIRONMENT_TYPE`
- **Email notifications**: Sends HTML-formatted error reports to administrators (with deduplication in production)
- **Rotating log files**: Keeps your logs manageable with automatic rotation (default: 10 files)
- **Request tracking**: Captures HTTP request context with sensitive data protection
- **Database error filtering**: Pre-configured patterns to reduce noise from common database warnings
- **Zero configuration required**: Works immediately after installation with sensible defaults

If you need full control over logging configuration, use [Inpsyde's Wonolog](https://github.com/inpsyde/Wonolog) directly. If you want logging to "just work" with best practices, this plugin is for you.

## Features

- **PSR-3 Compliant**: Based on Monolog via Wonolog, supporting standard PSR-3 logging interfaces
- **Multi-Handler Setup**: File logging + email notifications for errors
- **Environment Detection**: Uses [WP Env](https://github.com/wp-spaghetti/wp-env) for reliable environment detection
- **Sensitive Data Protection**: Automatically filters passwords, keys, and tokens from logs
- **Request Context**: Captures `$_REQUEST`, `$_POST`, `$_FILES`, `$_SERVER` (with sensitive data removed)
- **Deduplication**: Prevents email spam by deduplicating repeated errors (production only)
- **Customizable Patterns**: Filter out specific error messages via environment variables or WordPress filters
- **Developer-Friendly**: Detailed error logging in development, cleaner logs in production
- **Dependency Isolation**: All dependencies are scoped via [wpify/scoper](https://github.com/wpify/scoper) to prevent conflicts with other plugins

## Requirements

- PHP ^8.1
- WordPress ^6.0

## Installation

You can install the plugin in three ways: manually, via Composer from [WPackagist](https://wpackagist.org), or via Composer from [GitHub Releases](../../releases).

<details>
<summary>Manual Installation</summary>

1. Go to the [Releases](../../releases) section of this repository.
2. Download the latest release zip file.
3. Log in to your WordPress admin dashboard.
4. Navigate to `Plugins` > `Add New`.
5. Click `Upload Plugin`.
6. Choose the downloaded zip file and click `Install Now`.

</details>

<details>
<summary>Installation via Composer from WPackagist</summary>

If you use Composer to manage WordPress plugins, you can install it from [WordPress Packagist](https://wpackagist.org):

1. Open your terminal.
2. Navigate to the root directory of your WordPress installation.
3. Ensure your `composer.json` file has the following configuration: *

```json
{
    "require": {
        "composer/installers": "^1.0 || ^2.0",
        "wpackagist-plugin/wonolog": "^0.1"
    },
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": [
               "type:wordpress-plugin"
            ]
        }
    }
}
```
4. Run the following command:

```sh
composer update
```

<sub><i>
_Note:_  
_* `composer/installers` might already be required by another dependency._
</i></sub>
</details>

<details>
<summary>Installation via Composer from GitHub Releases</summary>

If you use Composer to manage WordPress plugins, you can install it from this repository directly.

**Standard Version** (uses WordPress update system):

1. Open your terminal.
2. Navigate to the root directory of your WordPress installation.
3. Ensure your `composer.json` file has the following configuration: *

```json
{
    "require": {
        "composer/installers": "^1.0 || ^2.0",
        "wp-spaghetti/wonolog": "^0.1"
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "wp-spaghetti/wonolog",
                "version": "0.1.0",
                "type": "wordpress-plugin",
                "dist": {
                    "url": "https://github.com/wp-spaghetti/wonolog/releases/download/v0.1.0/wonolog.zip",
                    "type": "zip"
                }
            }
        }
    ],
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": [
               "type:wordpress-plugin"
            ]
        }
    }
}
```

**Version with Git Updater** (uses Git Updater Lite for updates):

For installations that need updates managed via Git instead of WordPress.org, use the `--with-git-updater` version:

```json
{
    "require": {
        "composer/installers": "^1.0 || ^2.0",
        "wp-spaghetti/wonolog": "^0.1"
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "wp-spaghetti/wonolog",
                "version": "0.1.0",
                "type": "wordpress-plugin",
                "dist": {
                    "url": "https://github.com/wp-spaghetti/wonolog/releases/download/v0.1.0/wonolog--with-git-updater.zip",
                    "type": "zip"
                }
            }
        }
    ],
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": [
               "type:wordpress-plugin"
            ]
        }
    }
}
```

4. Run the following command:

```sh
composer update
```

<sub><i>
_Note:_  
_* `composer/installers` might already be required by another dependency._  
_* The `--with-git-updater` version includes [Git Updater Lite](https://github.com/afragen/git-updater-lite) for automatic updates detection, while the standard version relies on WordPress.org update system._
</i></sub>
</details>

### Must-Use Plugin (Recommended)

For optimal performance and reliability, it's **highly recommended** to install Wonolog as a [must-use plugin](https://wordpress.org/documentation/article/must-use-plugins/). This ensures that logging is initialized as early as possible in the WordPress bootstrap process, allowing you to capture errors that occur during plugin loading.

**Why must-use?** Wonolog's underlying library uses `earlyAddAction('muplugins_loaded', ...)` to hook into WordPress as early as possible. Installing it as a regular plugin works, but you'll miss errors that occur before regular plugins are loaded.

**Automatic mu-plugin loading:** To avoid creating manual loader files, you can use [roots/bedrock-autoloader](https://github.com/roots/bedrock-autoloader) which automatically loads plugins from the `mu-plugins` directory.

## Quick Start

### Zero Configuration Mode

The plugin works immediately after activation with no configuration required:

1. Install and activate the plugin
2. Errors are automatically logged to rotating files in production or PHP error log in development (log location is [customizable via Wonolog configuration](https://inpsyde.github.io/Wonolog/06-log-handlers/#logs-folder))
3. Critical errors are emailed to recipients defined in `WONOLOG_MAIL_TO` environment variable, or to the WordPress admin email as fallback

### Development vs Production Behavior

The plugin automatically detects your environment using [WP Env](https://github.com/wp-spaghetti/wp-env) and adjusts behavior:

**Development Mode** (`WP_DEBUG=true`):
- Logs to PHP error log (`error_log`)
- Captures silenced PHP errors (`@error`)
- More verbose error reporting
- No email deduplication

**Production Mode** (`WP_DEBUG=false`):
- Logs to rotating files (default: 10 files max, customizable via `WONOLOG_MAX_FILES`)
- Email notifications with deduplication (default: 24 hours, customizable via `WONOLOG_DEDUP_TIME`)
- Cleaner error reporting (filters notices and deprecation warnings)
- Performance optimized

**Note**: Log file location defaults to `wp-content/wonolog/` but can be customized via `WP_DEBUG_LOG` constant. See [Wonolog's documentation](https://inpsyde.github.io/Wonolog/06-log-handlers/#logs-folder) for details.

## Configuration

### Environment Variables

Configure the plugin via environment variables (`.env` file or `wp-config.php`):

```php
// Email configuration
define('WONOLOG_MAIL_TO', 'admin@example.com,dev@example.com'); // Comma-separated recipients
define('WONOLOG_MAIL_FROM', 'wordpress@example.com');
define('WONOLOG_EMAIL_LEVEL', 'ERROR'); // Minimum level for email notifications (default: ERROR)

// File logging configuration
define('WONOLOG_MAX_FILES', 10); // Maximum number of rotating log files (default: 10)
define('WONOLOG_FILE_PERMISSION', 0777); // File permissions for log files (default: 0777)
define('WONOLOG_DEDUP_TIME', 86400); // Email deduplication time in seconds (default: 86400 = 24 hours)

// Environment type (auto-detected if not set)
define('WP_ENVIRONMENT_TYPE', 'production'); // or 'development', 'staging'
define('WP_ENV', 'production'); // Alternative format

// Log file location (optional)
define('WP_DEBUG_LOG', '/custom/path/to/debug.log'); // Custom log file path
```

### Ignore Patterns

Filter out specific error messages using JSON-encoded patterns:

```php
// Replace default patterns entirely
define('WONOLOG_IGNORE_PATTERNS', json_encode([
    [
        'pattern' => '^Custom error pattern$',
        'level' => 'ERROR',
        'channel' => 'DB'
    ]
]));

// Add patterns to defaults
define('WONOLOG_IGNORE_PATTERNS_ADDITIONAL', json_encode([
    [
        'pattern' => '^Another pattern to ignore$',
        'level' => null,      // Apply to all levels
        'channel' => null     // Apply to all channels
    ]
]));
```

**Default Patterns** (database errors):
- `Can't DROP '.+'; check that column/key exists`
- `Deadlock found when trying to get lock`
- `Duplicate entry '.+' for key`
- `Table '.+' doesn't exist`

### WordPress Filters

Customize behavior using WordPress filters:

```php
// Add custom sensitive patterns to filter from logs
add_filter('wonolog_sensitive_patterns', function(array $patterns): array {
    $patterns[] = 'STRIPE_SECRET';
    $patterns[] = 'OAUTH_TOKEN';
    return $patterns;
});

// Modify ignore patterns
add_filter('wonolog_ignore_patterns', function(array $patterns): array {
    $patterns[] = [
        'pattern' => '^Your custom pattern$',
        'level' => null,
        'channel' => null
    ];
    return $patterns;
});
```

## Use Cases

### 1. E-commerce Site Error Monitoring

```php
// wp-config.php
define('WP_ENVIRONMENT_TYPE', 'production');
define('WONOLOG_MAIL_TO', 'ops@shop.com,dev@shop.com');

// Ignore common WooCommerce transient errors
add_filter('wonolog_ignore_patterns', function($patterns) {
    $patterns[] = [
        'pattern' => 'Transient .+ not found',
        'level' => 'WARNING',
        'channel' => null
    ];
    return $patterns;
});
```

### 2. Multi-Environment Development

```php
// .env.development
WP_ENVIRONMENT_TYPE=development
WP_DEBUG=true

// .env.staging
WP_ENVIRONMENT_TYPE=staging
WP_DEBUG=true
WONOLOG_MAIL_TO=staging-alerts@example.com

// .env.production
WP_ENVIRONMENT_TYPE=production
WP_DEBUG=false
WONOLOG_MAIL_TO=admin@example.com,ops@example.com
```

### 3. Custom Application Logging

Use PSR-3 logger throughout your codebase:

```php
use WpSpaghetti\Deps\Inpsyde\Wonolog\Wonolog;

class MyPlugin {
    public function processOrder($orderId) {
        $logger = Wonolog::logger();
        
        $logger->info('Processing order', ['order_id' => $orderId]);
        
        try {
            // ... processing logic
            $logger->notice('Order processed successfully', [
                'order_id' => $orderId,
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            $logger->error('Order processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
```

### 4. API Integration Monitoring

```php
class APIClient {
    private $logger;
    
    public function __construct() {
        $this->logger = \WpSpaghetti\Deps\Inpsyde\Wonolog\Wonolog::logger();
    }
    
    public function makeRequest($endpoint) {
        $this->logger->debug('API request started', [
            'endpoint' => $endpoint,
            'method' => 'GET'
        ]);
        
        $response = wp_remote_get($endpoint);
        
        if (is_wp_error($response)) {
            $this->logger->error('API request failed', [
                'endpoint' => $endpoint,
                'error' => $response->get_error_message()
            ]);
            return false;
        }
        
        $this->logger->info('API request completed', [
            'endpoint' => $endpoint,
            'status' => wp_remote_retrieve_response_code($response)
        ]);
        
        return $response;
    }
}
```

## Log Levels

The plugin uses standard PSR-3 log levels:

- **DEBUG**: Detailed debugging information (development only)
- **INFO**: Interesting events (user login, SQL queries)
- **NOTICE**: Normal but significant events
- **WARNING**: Exceptional occurrences that are not errors
- **ERROR**: Runtime errors (emailed in production)
- **CRITICAL**: Critical conditions (emailed in production)
- **ALERT**: Action must be taken immediately (emailed in production)
- **EMERGENCY**: System unusable (emailed in production)

## Log File Location

- **Production**: Rotating files with automatic cleanup (default location can be customized via `WP_DEBUG_LOG`, default max files: 10, customizable via `WONOLOG_MAX_FILES`)
- **Development**: PHP error log (location depends on PHP configuration)
- **Deduplication log**: Used in production to track duplicate errors (default: 24-hour window, customizable via `WONOLOG_DEDUP_TIME`)

See [Wonolog's documentation on log handlers](https://inpsyde.github.io/Wonolog/06-log-handlers/#logs-folder) for advanced log file location configuration.

## Dependencies

This plugin uses [WP Env](https://github.com/wp-spaghetti/wp-env) for reliable environment detection and configuration management. All environment-related features (development/staging/production detection, Docker detection, configuration loading) are powered by WP Env.

## Recommended Packages

**[WP Logger](https://github.com/wp-spaghetti/wp-logger)** is a wrapper service specifically designed for plugin developers who want to integrate logging capabilities into their WordPress plugins while maintaining flexibility and WordPress.org compliance.

Key features:
- Automatically detects and uses Wonolog when available
- Provides secure file logging fallback when Wonolog is not installed
- Isolates logs per plugin for easier debugging
- Ensures WordPress.org compliance for distributed plugins

Use **WP Logger** if you:
- Are developing plugins for WordPress.org distribution
- Need plugin-specific log isolation
- Want automatic fallback without Wonolog dependency
- Prefer simplified WordPress-native configuration

Use **Wonolog** directly if you:
- Need full control over Monolog configuration
- Have Wonolog as a must-use plugin (always available)
- Require advanced Monolog features (custom handlers, processors)
- Want enterprise-level logging customization

## Frequently Asked Questions

### Does this require any configuration?

No! The plugin works out of the box with sensible defaults. However, you can customize it via environment variables or WordPress filters if needed.

### Where are logs stored?

* **Development mode** (`WP_DEBUG=true`): PHP error log
* **Production mode** (`WP_DEBUG=false`): Rotating log files (default location can be customized via `WP_DEBUG_LOG` constant, see [Wonolog documentation](https://inpsyde.github.io/Wonolog/06-log-handlers/#logs-folder))

### Will I get email notifications for every error?

In production mode, email notifications are deduplicated (default: 24-hour window, customizable via `WONOLOG_DEDUP_TIME`) to prevent spam. In development mode, emails are sent immediately. Email recipients are defined via `WONOLOG_MAIL_TO` environment variable, with WordPress admin email as fallback.

### Can I filter out specific errors?

Yes! Use the `WONOLOG_IGNORE_PATTERNS` or `WONOLOG_IGNORE_PATTERNS_ADDITIONAL` environment variables, or the `wonolog_ignore_patterns` WordPress filter.

### What's the difference between this and Inpsyde's Wonolog?

Inpsyde's Wonolog is a powerful, flexible logging framework that requires configuration. This plugin provides an opinionated, pre-configured setup that works immediately. Use Wonolog if you need full control; use this plugin if you want it to "just work".

## Troubleshooting

### Logs Not Appearing

1. Check file permissions on `wp-content/wonolog/` directory
2. Verify `WP_DEBUG` setting matches your expectation
3. Check PHP error log location (development mode)
4. Ensure the plugin is activated

### Email Notifications Not Sent

1. Verify `WONOLOG_MAIL_TO` is set or WordPress admin email is configured
2. Check spam folder
3. Test WordPress email functionality with a test plugin
4. Review deduplication log to see if errors are being suppressed

### Too Many Email Notifications

1. In production, deduplication is automatic (24-hour window)
2. Use `WONOLOG_IGNORE_PATTERNS_ADDITIONAL` to filter specific errors
3. Consider raising the minimum log level for emails

### Debug Environment Information

```php
// Temporary debugging snippet
add_action('init', function() {
    $env = \WpSpaghetti\WpEnv\Environment::getDebugInfo();
    error_log(print_r($env, true));
});
```

## More info

See [LINKS](docs/LINKS.md) file.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a detailed list of changes for each release.

We follow [Semantic Versioning](https://semver.org/) and use [Conventional Commits](https://www.conventionalcommits.org/) to automatically generate our changelog.

### Release Process

- **Major versions** (1.0.0 → 2.0.0): Breaking changes
- **Minor versions** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch versions** (1.0.0 → 1.0.1): Bug fixes, backward compatible

All releases are automatically created when changes are pushed to the `main` branch, based on commit message conventions.

## Contributing

For your contributions please use:

- [Conventional Commits](https://www.conventionalcommits.org)
- [Pull request workflow](https://docs.github.com/en/get-started/exploring-projects-on-github/contributing-to-a-project)

See [CONTRIBUTING](.github/CONTRIBUTING.md) for detailed guidelines.

## Sponsor

[<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" width="200" alt="Buy Me A Coffee">](https://buymeacoff.ee/frugan)

## License

(ɔ) Copyleft 2026 [Frugan](https://frugan.it).  
[GNU GPLv3](https://choosealicense.com/licenses/gpl-3.0/), see [LICENSE](LICENSE) file.
