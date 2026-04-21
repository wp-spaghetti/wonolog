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

You can install the plugin in three ways: manually, via Composer from [WP Packages](https://wp-packages.org), or via Composer from [GitHub Releases](../../releases).

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
<summary>Installation via Composer from WP Packages</summary>

If you use Composer to manage WordPress plugins, you can install it from [WP Packages](https://wp-packages.org):

1. Open your terminal.
2. Navigate to the root directory of your WordPress installation.
3. Ensure your `composer.json` file has the following configuration: *

```json
{
    "require": {
        "composer/installers": "^1.0 || ^2.0",
        "wpackagist-plugin/wonolog": "^0.3"
    },
    "repositories": [
        {
            "name": "wp-packages",
            "type": "composer",
            "url": "https://repo.wp-packages.org"
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
        "wp-spaghetti/wonolog": "^0.3"
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "wp-spaghetti/wonolog",
                "version": "0.3.0",
                "type": "wordpress-plugin",
                "dist": {
                    "url": "https://github.com/wp-spaghetti/wonolog/releases/download/v0.3.0/wonolog.zip",
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

**Version with Plugin Update Checker** (uses PUC for updates via GitHub):

For installations that need updates managed via GitHub instead of WordPress.org, use the `--with-puc` version:

```json
{
    "require": {
        "composer/installers": "^1.0 || ^2.0",
        "wp-spaghetti/wonolog": "^0.3"
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "wp-spaghetti/wonolog",
                "version": "0.3.0",
                "type": "wordpress-plugin",
                "dist": {
                    "url": "https://github.com/wp-spaghetti/wonolog/releases/download/v0.3.0/wonolog--with-puc.zip",
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
_* The `--with-puc` version includes [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) for automatic update detection from GitHub releases, while the standard version relies on the WordPress.org update system._
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

#### IP Detection Configuration

Wonolog uses [Vectorface/whip](https://github.com/Vectorface/whip) for robust IP detection. Configure it using these filters:

**1. Configure detection methods** (default: `Whip::ALL_METHODS`):

```php
use WpSpaghetti\Deps\Vectorface\Whip\Whip;

// Example 1: Only trust Cloudflare and direct connection (recommended for Cloudflare sites)
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::CLOUDFLARE_HEADERS | Whip::REMOTE_ADDR;
});

// Example 2: For sites behind standard reverse proxy (Nginx, Traefik)
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::PROXY_HEADERS | Whip::REMOTE_ADDR;
});

// Example 3: Direct connection only (no proxy trust)
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::REMOTE_ADDR;
});

// Example 4: Use default (try all methods - suitable for most cases)
// No filter needed - Whip::ALL_METHODS is the default
```

**2. Add custom proxy headers** (for non-standard proxies):

```php
add_filter('wonolog_ip_custom_headers', function(): array {
    return [
        'X-Real-IP',              // Nginx, Traefik
        'X-My-Custom-IP-Header',  // Your custom proxy
    ];
});

// Don't forget to enable CUSTOM_HEADERS method:
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::CUSTOM_HEADERS | Whip::REMOTE_ADDR;
});
```

**3. Whitelist trusted proxy IPs** (advanced - for security):

```php
add_filter('wonolog_ip_whitelists', function(): array {
    return [
        // Only trust Cloudflare IPs for CF-Connecting-IP header
        Whip::CLOUDFLARE_HEADERS => [
            Whip::IPV4 => [
                '199.27.128.0/21',
                '173.245.48.0/20',
                '103.21.244.0/22',
                // ... full Cloudflare IP list
            ],
            Whip::IPV6 => [
                '2400:cb00::/32',
                '2606:4700::/32',
                // ... full Cloudflare IPv6 list
            ]
        ],
        
        // Only trust your load balancer for X-Forwarded-For
        Whip::PROXY_HEADERS => [
            Whip::IPV4 => [
                '10.0.0.1',      // Your load balancer IP
                '10.0.0.2',
            ]
        ]
    ];
});
```

**Available Whip Methods** (combine with `|` operator):

| Constant | Description | Headers Used |
|----------|-------------|--------------|
| `Whip::REMOTE_ADDR` | Direct connection IP | `$_SERVER['REMOTE_ADDR']` |
| `Whip::CLOUDFLARE_HEADERS` | Cloudflare's header | `CF-Connecting-IP` |
| `Whip::INCAPSULA_HEADERS` | Incapsula CDN | `Incap-Client-IP` |
| `Whip::PROXY_HEADERS` | Standard proxy headers | `X-Forwarded-For`, `X-Real-IP`, etc. |
| `Whip::CUSTOM_HEADERS` | Your custom headers | Headers from `wonolog_ip_custom_headers` |
| `Whip::ALL_METHODS` | Try all methods (default) | All of the above |

**Security Notes**:
- **Default (`ALL_METHODS`)**: Suitable for most sites, tries methods in priority order
- **`PROXY_HEADERS` risk**: Headers like `X-Forwarded-For` can be spoofed by clients if you're not behind a trusted proxy
- **Whitelisting**: Use `wonolog_ip_whitelists` to only trust specific proxy IPs
- **No proxy**: Use only `REMOTE_ADDR` if not behind any proxy

**Log Output**: The detected IP and its source are automatically logged:
- `client_ip` - The detected IP address
- `client_ip_source` - Which header/method was used (e.g., "cf-connecting-ip", "remote-addr")
- `hostbyaddr` - Reverse DNS lookup (when available)

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

### 5. Security Monitoring with Trusted Proxy Configuration

```php
// wp-config.php or mu-plugin

use WpSpaghetti\Deps\Vectorface\Whip\Whip;

// Example 1: Site behind Cloudflare only
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::CLOUDFLARE_HEADERS | Whip::REMOTE_ADDR;
});

// Optional: Whitelist only Cloudflare IPs for extra security
add_filter('wonolog_ip_whitelists', function(): array {
    return [
        Whip::CLOUDFLARE_HEADERS => [
            Whip::IPV4 => [
                '199.27.128.0/21',
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/12'
            ],
            Whip::IPV6 => [
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32'
            ]
        ]
    ];
});

// Example 2: Site behind AWS ALB (no Cloudflare)
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::PROXY_HEADERS | Whip::REMOTE_ADDR;
});

// Whitelist your ALB IP addresses
add_filter('wonolog_ip_whitelists', function(): array {
    return [
        Whip::PROXY_HEADERS => [
            Whip::IPV4 => [
                '10.0.1.0/24',  // Your ALB subnet
            ]
        ]
    ];
});

// Example 3: Custom proxy setup (Nginx with X-Real-IP)
add_filter('wonolog_ip_detection_methods', function(): int {
    return Whip::CUSTOM_HEADERS | Whip::REMOTE_ADDR;
});

add_filter('wonolog_ip_custom_headers', function(): array {
    return ['X-Real-IP'];
});

add_filter('wonolog_ip_whitelists', function(): array {
    return [
        Whip::CUSTOM_HEADERS => [
            Whip::IPV4 => ['192.168.1.1']  // Your Nginx server
        ]
    ];
});

// Example 4: Monitor failed login attempts with real client IP
add_action('wp_login_failed', function(string $username) {
    do_action('wonolog.log.warning', [
        'message' => 'Failed login attempt',
        'channel' => \WpSpaghetti\Deps\Inpsyde\Wonolog\Channels::SECURITY,
        'context' => [
            'username' => $username,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            // client_ip and client_ip_source are automatically added by Wonolog
        ],
    ]);
});

// Example 5: Rate limiting by IP using Whip directly
add_action('rest_api_init', function() {
    // Create Whip instance matching your configuration
    $whip = new Whip(Whip::CLOUDFLARE_HEADERS | Whip::REMOTE_ADDR);
    $ip = $whip->getValidIpAddress();
    
    if ($ip) {
        $transient_key = 'api_rate_limit_' . md5($ip);
        $count = (int) get_transient($transient_key);
        
        if ($count > 100) {
            do_action('wonolog.log.error', [
                'message' => 'API rate limit exceeded',
                'context' => [
                    'ip' => $ip,
                    'requests' => $count,
                ],
            ]);
            
            wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
        }
        
        set_transient($transient_key, $count + 1, MINUTE_IN_SECONDS);
    }
});

// Example 6: Block requests from specific IP ranges
add_action('init', function() {
    $whip = new Whip();
    $ip = $whip->getValidIpAddress();
    
    if (!$ip) {
        return;
    }
    
    // Check if IP is in blocked range
    $blockedRange = new \WpSpaghetti\Deps\Vectorface\Whip\IpRange\Ipv4Range('192.0.2.0/24');
    
    if ($blockedRange->containsIp($ip)) {
        do_action('wonolog.log.critical', [
            'message' => 'Blocked IP range access attempt',
            'context' => [
                'ip' => $ip,
                'requested_url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ],
        ]);
        
        wp_die('Access denied', 'Forbidden', ['response' => 403]);
    }
});

// Example 7: Monitor suspicious activity from flagged IPs
add_filter('wonolog.log-data-context', function(array $context, $log): array {
    $suspiciousIps = ['192.0.2.1', '198.51.100.1'];
    
    if (isset($context['client_ip']) && in_array($context['client_ip'], $suspiciousIps, true)) {
        // Add flag to context
        $context['flagged_ip'] = true;
        
        // Escalate to critical
        do_action('wonolog.log.critical', [
            'message' => 'Activity from flagged IP detected',
            'context' => [
                'original_message' => $log->message(),
                'client_ip' => $context['client_ip'],
                'client_ip_source' => $context['client_ip_source'] ?? 'unknown',
            ],
        ]);
    }
    
    return $context;
}, 10, 2);
```

**Why Whip with Whitelisting?**
- **Security**: Only trusts IPs from your actual infrastructure
- **Spoofing Prevention**: Headers from non-whitelisted sources are ignored
- **Flexibility**: Easy to update when adding/removing proxies
- **Reliability**: Handles edge cases and malformed headers gracefully

**Common Infrastructure Patterns**:

| Setup | Detection Methods | Whitelist |
|-------|------------------|-----------|
| Direct (no proxy) | `REMOTE_ADDR` | Not needed |
| Behind Cloudflare | `CLOUDFLARE_HEADERS \| REMOTE_ADDR` | Cloudflare IP ranges |
| Behind AWS ALB | `PROXY_HEADERS \| REMOTE_ADDR` | ALB subnet IPs |
| Behind Nginx | `CUSTOM_HEADERS \| REMOTE_ADDR` | Nginx server IP |
| CF + ALB | `CLOUDFLARE_HEADERS \| PROXY_HEADERS \| REMOTE_ADDR` | Both CF and ALB IPs |
| Behind Incapsula | `INCAPSULA_HEADERS \| REMOTE_ADDR` | Incapsula IP ranges |

**Getting Proxy IP Lists**:
- **Cloudflare**: [IPv4](https://www.cloudflare.com/ips-v4) / [IPv6](https://www.cloudflare.com/ips-v6)
- **AWS**: Check your VPC subnet configuration
- **Your Infrastructure**: Use `$_SERVER['REMOTE_ADDR']` to see proxy IPs

**Log Output**:
- `client_ip`: Validated client IP address
- `client_ip_source`: Which header was used (e.g., "cf-connecting-ip", "remote-addr")
- `hostbyaddr`: Reverse DNS lookup (when available)

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

**IP Detection**: Uses [Vectorface/whip](https://github.com/Vectorface/whip) for robust and secure client IP detection with proxy header validation. Whip provides battle-tested protection against IP spoofing and supports various proxy configurations (Cloudflare, AWS ALB, Nginx, custom proxies, etc.).

**All dependencies are scoped** via [wpify/scoper](https://github.com/wpify/scoper) to prevent conflicts with other plugins, ensuring the plugin works reliably in any WordPress environment.

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
