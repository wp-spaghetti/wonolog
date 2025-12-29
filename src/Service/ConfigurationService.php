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

namespace WpSpaghetti\Wonolog\Service;

use WpSpaghetti\Deps\Inpsyde\Wonolog\LogLevel;
use WpSpaghetti\WpEnv\Environment;

use function WpSpaghetti\Deps\Safe\parse_url;

/**
 * Service for managing plugin configuration.
 *
 * Provides centralized access to configuration values from environment variables
 * and WordPress options, with sensible defaults.
 */
class ConfigurationService
{
    /**
     * Default configuration values.
     */
    private const DEFAULT_EMAIL_LEVEL = LogLevel::ERROR;

    private const DEFAULT_DEDUP_TIME = 86400; // 24 hours

    private const DEFAULT_MAX_FILES = 10;

    private const DEFAULT_FILE_PERMISSION = 0777;

    /**
     * Get email notification level.
     */
    public function getEmailLevel(): int
    {
        $level = Environment::get('WONOLOG_EMAIL_LEVEL');

        if (empty($level)) {
            return self::DEFAULT_EMAIL_LEVEL;
        }

        // Try to convert string to constant
        $constantName = LogLevel::class.'::'.strtoupper($level);

        if (\defined($constantName)) {
            return \constant($constantName);
        }

        return self::DEFAULT_EMAIL_LEVEL;
    }

    /**
     * Get deduplication time in seconds.
     */
    public function getDedupTime(): int
    {
        return Environment::getInt('WONOLOG_DEDUP_TIME', self::DEFAULT_DEDUP_TIME);
    }

    /**
     * Get maximum number of rotating log files.
     */
    public function getMaxFiles(): int
    {
        return Environment::getInt('WONOLOG_MAX_FILES', self::DEFAULT_MAX_FILES);
    }

    /**
     * Get file permission for log files.
     */
    public function getFilePermission(): int
    {
        return Environment::getInt('WONOLOG_FILE_PERMISSION', self::DEFAULT_FILE_PERMISSION);
    }

    /**
     * Get site domain for email configuration.
     */
    public function getSiteDomain(): string
    {
        $siteDomain = parse_url(home_url(), PHP_URL_HOST);

        return str_replace('www.', '', $siteDomain ?? 'localhost');
    }

    /**
     * Get email recipients for error notifications.
     *
     * @return array<string>|string
     */
    public function getEmailRecipients()
    {
        $emailTo = Environment::getArray('WONOLOG_MAIL_TO');

        if ([] !== $emailTo) {
            return $emailTo;
        }

        return get_option('admin_email');
    }

    /**
     * Get email from address.
     */
    public function getEmailFrom(): string
    {
        $from = Environment::get('WONOLOG_MAIL_FROM');

        if (!empty($from)) {
            return $from;
        }

        return 'wordpress@'.$this->getSiteDomain();
    }

    /**
     * Get email subject.
     */
    public function getEmailSubject(): string
    {
        return \sprintf(
            __('Error reporting from %1$s - %2$s', 'wonolog'),
            $this->getSiteDomain(),
            Environment::get('WP_ENV', 'production')
        );
    }

    /**
     * Get error types to log based on environment.
     */
    public function getErrorTypes(): ?int
    {
        if (Environment::isDebug()) {
            if (\is_string(WP_DEBUG_LOG) || WP_DEBUG_LOG) {
                return E_ALL & ~E_WARNING & ~E_NOTICE & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;
            }

            return null;
        }

        return E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;
    }

    /**
     * Check if silenced errors should be logged.
     */
    public function shouldLogSilencedErrors(): bool
    {
        return Environment::isDebug();
    }
}
