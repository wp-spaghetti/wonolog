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
        $default = ['AUTH', 'COOKIE', 'DB_', 'DSN', 'KEY', 'PASS', 'PRIVATE', 'PWD', 'SALT', 'SECRET', 'TOKEN'];
        $filtered = apply_filters('wonolog_sensitive_patterns', $default);

        return \is_array($filtered) ? $filtered : $default;
    }

    /**
     * Recursively remove sensitive keys (nested form fields, session data).
     *
     * @param array<int|string, mixed> $data Data to filter
     *
     * @return array<int|string, mixed> Filtered data
     */
    public static function filterSensitiveData(array $data): array
    {
        $patterns = self::getSensitivePatterns();

        $isSensitive = static fn (int|string $key): bool => array_reduce(
            $patterns,
            static fn ($carry, $pattern): bool => $carry || false !== stripos((string) $key, (string) $pattern),
            false
        );

        $filter = static function (array $data) use (&$filter, $isSensitive): array {
            $data = array_filter($data, static fn ($key): bool => !$isSensitive($key), ARRAY_FILTER_USE_KEY);

            foreach ($data as $key => $value) {
                if (\is_array($value)) {
                    $data[$key] = $filter($value);
                }
            }

            return $data;
        };

        return $filter($data);
    }
}
