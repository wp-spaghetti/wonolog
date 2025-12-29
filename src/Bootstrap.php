<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog WordPress plugin.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 or later license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WpSpaghetti\Wonolog;

use WpSpaghetti\Deps\Inpsyde\Wonolog\Channels;
use WpSpaghetti\Deps\Inpsyde\Wonolog\Configurator;
use WpSpaghetti\Deps\Inpsyde\Wonolog\DefaultHandler\LogsFolder;
use WpSpaghetti\Deps\Inpsyde\Wonolog\HookListener\HttpApiListener;
use WpSpaghetti\Deps\Inpsyde\Wonolog\LogLevel;
use WpSpaghetti\Deps\Monolog\Formatter\HtmlFormatter;
use WpSpaghetti\Deps\Monolog\Formatter\LineFormatter;
use WpSpaghetti\Deps\Monolog\Handler\DeduplicationHandler;
use WpSpaghetti\Deps\Monolog\Handler\ErrorLogHandler;
use WpSpaghetti\Deps\Monolog\Handler\NativeMailerHandler;
use WpSpaghetti\Deps\Monolog\Handler\RotatingFileHandler;
use WpSpaghetti\Deps\Monolog\LogRecord;
use WpSpaghetti\Deps\Monolog\Processor\PsrLogMessageProcessor;
use WpSpaghetti\Deps\Vectorface\Whip\Whip;
use WpSpaghetti\WpEnv\Environment;

use function WpSpaghetti\Deps\Safe\error_log;
use function WpSpaghetti\Deps\Safe\gethostname;
use function WpSpaghetti\Deps\Safe\json_decode;
use function WpSpaghetti\Deps\Safe\parse_url;
use function WpSpaghetti\Deps\Safe\preg_match;

/**
 * Opinionated Wonolog configuration for WordPress.
 *
 * This class provides a pre-configured setup for Wonolog with:
 * - Environment-aware logging (debug vs production)
 * - Email notifications for errors
 * - Rotating file handlers
 * - Custom processors for request tracking
 * - Sensitive data protection
 * - Customizable ignore patterns
 */
class Bootstrap
{
    /**
     * Default configuration values.
     */
    private const DEFAULT_EMAIL_LEVEL = LogLevel::ERROR;

    private const DEFAULT_DEDUP_TIME = 86400;

    // 24 hours
    private const DEFAULT_MAX_FILES = 10;

    private const DEFAULT_FILE_PERMISSION = 0777;

    /**
     * Configure Wonolog with opinionated defaults.
     *
     * @param Configurator $configurator The Wonolog configurator instance
     */
    public function configure(Configurator $configurator): void
    {
        $defaultHandler = $this->createDefaultHandler();
        $emailHandler = $this->createEmailHandler();

        $this->configureErrorReporting($configurator);

        $configurator->disableFallbackHandler()
            // Disable default HttpApiListener (logs at ERROR level)
            // @see: https://github.com/inpsyde/Wonolog/issues/83
            ->disableDefaultHookListeners(HttpApiListener::class)
            // Add custom HttpApiListener with WARNING level instead of ERROR
            // Since HttpApiListener is final, we need to create a new instance
            // with the desired log level in the constructor
            ->addActionListener(new HttpApiListener(LogLevel::WARNING))
            ->pushHandler($defaultHandler)
            ->pushHandler($emailHandler)
            // for placeholder substitution
            ->pushProcessor('psr-log-message-processor', new PsrLogMessageProcessor())
            ->pushProcessor('extra-processor', [$this, 'addExtraContext'])
        ;

        $this->applyIgnorePatterns($configurator);
    }

    /**
     * Add extra context to log records.
     *
     * @param array|LogRecord $record The log record
     *
     * @return array|LogRecord Modified log record with extra context
     */
    public function addExtraContext(array|LogRecord $record): array|LogRecord
    {
        try {
            $hostname = gethostname(); // php_uname('n')
        } catch (\Exception) {
            $hostname = null;
        }

        $ipData = $this->getClientIp();
        $hostbyaddr = null;
        if ('0.0.0.0' !== $ipData['ip']) {
            try {
                $hostbyaddr = gethostbyaddr($ipData['ip']);
            } catch (\Exception) {
                $hostbyaddr = null;
            }
        }

        $sensitivePatterns = $this->getSensitivePatterns();
        $filteredServer = array_filter(
            $_SERVER,
            static fn ($key): bool => !array_reduce(
                $sensitivePatterns,
                static fn ($carry, $pattern): bool => $carry || false !== stripos($key, (string) $pattern),
                false
            ),
            ARRAY_FILTER_USE_KEY
        );

        $extraData = [
            'client_ip' => $ipData['ip'],
            'client_ip_source' => $ipData['source'],
            'hostname' => $hostname,
            'hostbyaddr' => $hostbyaddr,
            '_REQUEST' => $_REQUEST,
            '_POST' => $_POST,
            '_FILES' => $_FILES,
            '_SESSION' => $_SESSION ?? null,
            '_SERVER' => $filteredServer,
        ];

        // Handle Monolog 3.x (LogRecord object)
        if ($record instanceof LogRecord) {
            foreach ($extraData as $key => $value) {
                $record->extra[$key] = $value;
            }

            return $record;
        }

        // Handle Monolog 2.x (array)
        if (!isset($record['extra']) || !\is_array($record['extra'])) {
            $record['extra'] = [];
        }

        $record['extra'] = array_merge($record['extra'], $extraData);

        return $record;
    }

    /**
     * Create the default log handler based on environment.
     */
    private function createDefaultHandler(): ErrorLogHandler|RotatingFileHandler
    {
        if (Environment::isDebug()) {
            $handler = new ErrorLogHandler(ErrorLogHandler::SAPI, LogLevel::defaultMinLevel());
        } else {
            $maxFiles = Environment::getInt('WONOLOG_MAX_FILES', self::DEFAULT_MAX_FILES);
            $filePermission = Environment::getInt('WONOLOG_FILE_PERMISSION', self::DEFAULT_FILE_PERMISSION);

            $handler = new RotatingFileHandler(
                LogsFolder::determineFolder().'app.log',
                $maxFiles,
                LogLevel::defaultMinLevel(),
                true,
                $filePermission
            );
        }

        // The last "true" tells monolog to remove empty []'s
        $handler->setFormatter(new LineFormatter(null, null, false, true));

        return $handler;
    }

    /**
     * Create email notification handler for errors.
     */
    private function createEmailHandler(): DeduplicationHandler|NativeMailerHandler
    {
        $siteDomain = $this->getSiteDomain();
        $emailTo = $this->getEmailRecipients();

        $emailLevel = $this->convertLevelToConstant(
            Environment::get('WONOLOG_EMAIL_LEVEL')
        ) ?? self::DEFAULT_EMAIL_LEVEL;

        $nativeMailerHandler = new NativeMailerHandler(
            $emailTo,
            \sprintf(
                __('Error reporting from %1$s - %2$s', 'wonolog'),
                $siteDomain,
                Environment::get('WP_ENV', 'production')
            ),
            Environment::get('WONOLOG_MAIL_FROM', 'wordpress@'.$siteDomain),
            $emailLevel
        );
        $nativeMailerHandler->setContentType('text/html');
        $nativeMailerHandler->setFormatter(new HtmlFormatter());

        // Wrap with deduplication handler in production
        if (!Environment::isDebug()) {
            $dedupTime = Environment::getInt('WONOLOG_DEDUP_TIME', self::DEFAULT_DEDUP_TIME);

            return new DeduplicationHandler(
                $nativeMailerHandler,
                \sprintf(
                    LogsFolder::determineFolder().'dedup-%s.log',
                    Environment::get('WP_ENV', 'production')
                ),
                $emailLevel,
                $dedupTime
            );
        }

        return $nativeMailerHandler;
    }

    /**
     * Configure error reporting based on environment.
     */
    private function configureErrorReporting(Configurator $configurator): void
    {
        if (Environment::isDebug()) {
            $configurator->logSilencedPhpErrors();

            if (\is_string(WP_DEBUG_LOG) || WP_DEBUG_LOG) {
                $errorTypes = E_ALL & ~E_WARNING & ~E_NOTICE & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;
            } else {
                $errorTypes = null;
            }
        } else {
            $errorTypes = E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;

            // https://maximivanov.github.io/php-error-reporting-calculator/
            // https://kau-boys.com/2619/wordpress/set-the-debug-level-using-error_reporting
            // https://discourse.roots.io/t/bedrock-cant-disable-php-notices-warnings/20511
            error_reporting($errorTypes);
        }

        if (null !== $errorTypes && 0 !== $errorTypes) {
            $configurator->logPhpErrorsTypes($errorTypes);
        }
    }

    /**
     * Apply ignore patterns to the configurator.
     */
    private function applyIgnorePatterns(Configurator $configurator): void
    {
        $patterns = $this->getIgnorePatterns();

        foreach ($patterns as $pattern) {
            // If channel is null, don't pass it (let it default to ALL_CHANNELS)
            if (null === $pattern['channel']) {
                $configurator->withIgnorePattern(
                    $pattern['pattern'],
                    $pattern['level']
                );
            } else {
                $configurator->withIgnorePattern(
                    $pattern['pattern'],
                    $pattern['level'],
                    $pattern['channel']
                );
            }
        }
    }

    /**
     * Get site domain for email configuration.
     */
    private function getSiteDomain(): string
    {
        $siteDomain = parse_url(home_url(), PHP_URL_HOST);

        return str_replace('www.', '', $siteDomain ?? 'localhost');
    }

    /**
     * Get email recipients for error notifications.
     *
     * @return array<string>|string
     */
    private function getEmailRecipients()
    {
        $emailTo = Environment::getArray('WONOLOG_MAIL_TO');

        if ([] !== $emailTo) {
            return $emailTo;
        }

        return get_option('admin_email');
    }

    /**
     * Get patterns for sensitive data detection.
     */
    private function getSensitivePatterns(): array
    {
        $default = ['AUTH', 'DB_', 'DSN', 'KEY', 'PASSWORD', 'PRIVATE', 'SALT', 'SECRET', 'TOKEN'];
        $filtered = apply_filters('wonolog_sensitive_patterns', $default);

        return \is_array($filtered) ? $filtered : $default;
    }

    /**
     * Get ignore patterns from environment or filters.
     */
    private function getIgnorePatterns(): array
    {
        $envPatterns = Environment::get('WONOLOG_IGNORE_PATTERNS');
        if (!empty($envPatterns)) {
            $decoded = json_decode($envPatterns, true);
            if (JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
                return $this->validatePatterns($decoded);
            }
        }

        $additionalPatterns = Environment::get('WONOLOG_IGNORE_PATTERNS_ADDITIONAL');
        if (!empty($additionalPatterns)) {
            $decoded = json_decode($additionalPatterns, true);
            if (JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
                $defaults = $this->getDefaultIgnorePatterns();
                $additional = $this->validatePatterns($decoded);

                return array_merge($defaults, $additional);
            }
        }

        $defaults = $this->getDefaultIgnorePatterns();
        $filtered = apply_filters('wonolog_ignore_patterns', $defaults);

        return \is_array($filtered) ? $this->validatePatterns($filtered) : $defaults;
    }

    /**
     * Get default ignore patterns for common database errors.
     */
    private function getDefaultIgnorePatterns(): array
    {
        return [
            [
                'pattern' => "^Can't DROP '.+'; check that column/key exists$",
                'level' => null,
                'channel' => Channels::DB,
            ],
            [
                'pattern' => '^Deadlock found when trying to get lock; try restarting transaction$',
                'level' => null,
                'channel' => Channels::DB,
            ],
            [
                // https://wordpress.org/support/topic/database-error-duplicate-entry-lastnotificationid-for-key-primary/
                'pattern' => "^Duplicate entry '.+' for key",
                'level' => null,
                'channel' => Channels::DB,
            ],
            [
                'pattern' => "^Table '.+' doesn't exist$",
                'level' => null,
                'channel' => Channels::DB,
            ],
        ];
    }

    /**
     * Validate and normalize ignore patterns.
     */
    private function validatePatterns(array $patterns): array
    {
        $validated = [];

        foreach ($patterns as $pattern) {
            if (!\is_array($pattern)) {
                continue;
            }

            if (empty($pattern['pattern'])) {
                continue;
            }

            // Validate regex - escape only the delimiter to allow any valid regex pattern
            try {
                $delimiter = '~';
                $escapedPattern = str_replace($delimiter, '\\'.$delimiter, $pattern['pattern']);
                preg_match($delimiter.$escapedPattern.$delimiter, '');
            } catch (\Exception $e) {
                error_log('Wonolog: Invalid regex pattern: '.$pattern['pattern'].' - '.$e->getMessage());

                continue;
            }

            $validated[] = [
                'pattern' => $pattern['pattern'],
                'level' => $this->convertLevelToConstant($pattern['level'] ?? null),
                'channel' => $this->convertChannelToConstant($pattern['channel'] ?? null),
            ];
        }

        return $validated;
    }

    /**
     * Convert log level string to constant.
     */
    private function convertLevelToConstant(mixed $level): ?int
    {
        if (null === $level || \is_int($level)) {
            return $level;
        }

        if (!\is_string($level) || '' === trim($level)) {
            return null;
        }

        $constantName = LogLevel::class.'::'.strtoupper($level);

        if (\defined($constantName)) {
            return \constant($constantName);
        }

        // Use error_log, NOT do_action to avoid loops
        error_log(\sprintf(
            'Wonolog: Unknown level "%s". Available: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY.',
            $level
        ));

        return null;
    }

    /**
     * Convert channel string to constant.
     */
    private function convertChannelToConstant(?string $channel): ?string
    {
        if (\in_array($channel, [null, '', '0'], true)) {
            return null;
        }

        $constantName = Channels::class.'::'.strtoupper($channel);

        if (\defined($constantName)) {
            return \constant($constantName);
        }

        // Use error_log, NOT do_action to avoid loops
        error_log(\sprintf(
            'Wonolog: Unknown channel "%s". Available channels in Wonolog\Channels class.',
            $channel
        ));

        return null;
    }

    /**
     * Get client IP address using Vectorface/whip library.
     *
     * SECURITY: Whip validates that proxy headers come from trusted sources
     * by checking if REMOTE_ADDR is in the configured whitelist.
     *
     * @return array{ip: string, source: string} IP address and detection source
     */
    private function getClientIp(): array
    {
        // CLI context
        if (\PHP_SAPI === 'cli') {
            return ['ip' => '0.0.0.0', 'source' => 'CLI'];
        }

        /**
         * Filter IP detection methods using Whip bitmask.
         *
         * Default: ALL_METHODS (tries all methods in priority order)
         * This is Whip's default and suitable for most cases.
         *
         * Available constants (combine with | operator):
         * - Whip::REMOTE_ADDR: Direct connection IP
         * - Whip::CLOUDFLARE_HEADERS: CF-Connecting-IP
         * - Whip::INCAPSULA_HEADERS: Incap-Client-IP
         * - Whip::PROXY_HEADERS: X-Forwarded-For, X-Real-IP, etc.
         * - Whip::CUSTOM_HEADERS: Headers added via wonolog_ip_custom_headers
         * - Whip::ALL_METHODS: All of the above (default)
         *
         * @param int $methods Bitmask of detection methods
         */
        $methods = apply_filters('wonolog_ip_detection_methods', Whip::ALL_METHODS);

        if (!\is_int($methods)) {
            $methods = Whip::ALL_METHODS;
        }

        /**
         * Filter IP whitelists to validate proxy sources.
         *
         * This validates that REMOTE_ADDR (the connecting proxy) is trusted
         * before accepting headers from that method.
         *
         * Example: Only accept CF-Connecting-IP if request comes from CloudFlare IPs
         *
         * Format:
         * [
         *     Whip::CLOUDFLARE_HEADERS => [
         *         Whip::IPV4 => ['199.27.128.0/21', '173.245.48.0/20', ...],
         *         Whip::IPV6 => ['2400:cb00::/32', '2606:4700::/32', ...]
         *     ],
         *     Whip::PROXY_HEADERS => [
         *         Whip::IPV4 => ['10.0.1.1']  // Your load balancer IP
         *     ]
         * ]
         *
         * @param array<int, array<string, array<string>>> $whitelists IP ranges per method
         */
        $whitelists = apply_filters('wonolog_ip_whitelists', []);

        if (!\is_array($whitelists)) {
            $whitelists = [];
        }

        /**
         * Filter custom headers for IP detection.
         *
         * These are used when Whip::CUSTOM_HEADERS is enabled.
         * Header names without HTTP_ prefix (Whip adds it automatically).
         *
         * @param array<string> $headers Custom header names
         */
        $customHeaders = apply_filters('wonolog_ip_custom_headers', []);

        if (!\is_array($customHeaders)) {
            $customHeaders = [];
        }

        try {
            $whip = new Whip($methods, $whitelists);

            // Add custom headers if CUSTOM_HEADERS method is enabled
            if (($methods & Whip::CUSTOM_HEADERS) && [] !== $customHeaders) {
                foreach ($customHeaders as $customHeader) {
                    if (\is_string($customHeader) && '' !== $customHeader) {
                        $whip->addCustomHeader($customHeader);
                    }
                }
            }

            $ip = $whip->getValidIpAddress();

            if (false === $ip) {
                return ['ip' => '0.0.0.0', 'source' => 'unknown'];
            }

            // Whip doesn't expose which method was used, so we detect it manually
            $source = $this->detectIpSource($ip, $methods, $customHeaders);

            return [
                'ip' => $ip,
                'source' => $source,
            ];
        } catch (\Exception $exception) {
            error_log('Wonolog: Error detecting IP - '.$exception->getMessage());

            return ['ip' => '0.0.0.0', 'source' => 'error'];
        }
    }

    /**
     * Detect which source was used for IP detection.
     *
     * Since Whip doesn't expose this information, we check headers manually.
     *
     * @param string        $detectedIp    The IP address detected by Whip
     * @param int           $methods       The enabled methods bitmask
     * @param array<string> $customHeaders Custom headers list
     *
     * @return string The source identifier
     */
    private function detectIpSource(string $detectedIp, int $methods, array $customHeaders): string
    {
        // Check custom headers first (highest priority in Whip's logic)
        if (($methods & Whip::CUSTOM_HEADERS) && $customHeaders !== []) {
            foreach ($customHeaders as $customHeader) {
                $normalized = $this->normalizeHeaderName($customHeader);
                if (!empty($_SERVER[$normalized])) {
                    $headerIp = $this->extractFirstIp($_SERVER[$normalized]);
                    if ($headerIp === $detectedIp) {
                        return strtolower(str_replace('HTTP_', '', $normalized));
                    }
                }
            }
        }

        // Check Incapsula headers
        if (($methods & Whip::INCAPSULA_HEADERS) && !empty($_SERVER['HTTP_INCAP_CLIENT_IP'])) {
            $headerIp = $this->extractFirstIp($_SERVER['HTTP_INCAP_CLIENT_IP']);
            if ($headerIp === $detectedIp) {
                return 'incap-client-ip';
            }
        }

        // Check CloudFlare headers
        if (($methods & Whip::CLOUDFLARE_HEADERS) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $headerIp = $this->extractFirstIp($_SERVER['HTTP_CF_CONNECTING_IP']);
            if ($headerIp === $detectedIp) {
                return 'cf-connecting-ip';
            }
        }

        // Check proxy headers
        if (($methods & Whip::PROXY_HEADERS) !== 0) {
            $proxyHeaders = [
                'HTTP_CLIENT_IP' => 'client-ip',
                'HTTP_X_FORWARDED_FOR' => 'x-forwarded-for',
                'HTTP_X_FORWARDED' => 'x-forwarded',
                'HTTP_X_CLUSTER_CLIENT_IP' => 'x-cluster-client-ip',
                'HTTP_FORWARDED_FOR' => 'forwarded-for',
                'HTTP_FORWARDED' => 'forwarded',
                'HTTP_X_REAL_IP' => 'x-real-ip',
            ];

            foreach ($proxyHeaders as $serverKey => $name) {
                if (!empty($_SERVER[$serverKey])) {
                    $headerIp = $this->extractFirstIp($_SERVER[$serverKey]);
                    if ($headerIp === $detectedIp) {
                        return $name;
                    }
                }
            }
        }

        // Check REMOTE_ADDR
        if ($methods & Whip::REMOTE_ADDR && !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === $detectedIp) {
            return 'remote-addr';
        }

        return 'detected';
    }

    /**
     * Normalize header name to match $_SERVER format.
     *
     * @param string $header Header name
     *
     * @return string Normalized header name
     */
    private function normalizeHeaderName(string $header): string
    {
        // If already in HTTP_ format, return as-is
        if (str_starts_with($header, 'HTTP_')) {
            return strtoupper($header);
        }

        // Convert header name to HTTP_ format
        return 'HTTP_'.str_replace('-', '_', strtoupper($header));
    }

    /**
     * Extract first IP from a comma-separated list.
     *
     * @param string $value Header value
     *
     * @return string First IP address
     */
    private function extractFirstIp(string $value): string
    {
        $list = explode(',', $value);

        return trim($list[0]);
    }
}
