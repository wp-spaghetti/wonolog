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

namespace WpSpaghetti\Wonolog\Support;

/**
 * Helper for handling sensitive data in logs.
 */
class SecurityHelper
{
    /**
     * Get patterns for sensitive data detection.
     *
     * @return array<string>
     */
    public static function getSensitivePatterns(): array
    {
        $default = ['AUTH', 'DB_', 'DSN', 'KEY', 'PASSWORD', 'PRIVATE', 'SALT', 'SECRET', 'TOKEN'];
        $filtered = apply_filters('wonolog_sensitive_patterns', $default);

        return \is_array($filtered) ? $filtered : $default;
    }

    /**
     * Filter $_SERVER array removing sensitive keys.
     *
     * @param array<string, mixed> $server The $_SERVER array
     *
     * @return array<string, mixed> Filtered server data
     */
    public static function filterServerData(array $server): array
    {
        $patterns = self::getSensitivePatterns();

        return array_filter(
            $server,
            static fn ($key): bool => !array_reduce(
                $patterns,
                static fn ($carry, $pattern): bool => $carry || false !== stripos($key, $pattern),
                false
            ),
            ARRAY_FILTER_USE_KEY
        );
    }
}
