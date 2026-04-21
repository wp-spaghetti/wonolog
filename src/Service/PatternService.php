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

use WpSpaghetti\Deps\Inpsyde\Wonolog\Channels;
use WpSpaghetti\Deps\Inpsyde\Wonolog\Configurator;
use WpSpaghetti\Deps\Inpsyde\Wonolog\LogLevel;
use WpSpaghetti\WpEnv\Environment;

use function WpSpaghetti\Deps\Safe\error_log;
use function WpSpaghetti\Deps\Safe\json_decode;
use function WpSpaghetti\Deps\Safe\preg_match;

/**
 * Service for managing log ignore patterns.
 *
 * Handles pattern validation, normalization, and application to Wonolog configurator.
 */
class PatternService
{
    /**
     * Apply ignore patterns to the configurator.
     */
    public function applyIgnorePatterns(Configurator $configurator): void
    {
        $patterns = $this->getIgnorePatterns();

        foreach ($patterns as $pattern) {
            // levelThreshold is EXCLUSIVE: a log is ignored only if log->level() < levelThreshold.
            // e.g. to ignore INFO (200) pass NOTICE (250); pass null to ignore regardless of level.
            // channels is variadic: empty array means all channels.
            $configurator->withIgnorePattern(
                $pattern['pattern'],
                $pattern['levelThreshold'],
                ...$pattern['channels']
            );
        }
    }

    /**
     * Get ignore patterns from environment or filters.
     *
     * @return array<int, array{pattern: string, level: null|int, channel: null|string}>
     */
    public function getIgnorePatterns(): array
    {
        // Check if patterns are completely replaced via env
        $envPatterns = Environment::get('WONOLOG_IGNORE_PATTERNS');
        if (!empty($envPatterns)) {
            $decoded = json_decode($envPatterns, true);
            if (JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
                return $this->validatePatterns($decoded);
            }
        }

        // Check if additional patterns are provided via env
        $additionalPatterns = Environment::get('WONOLOG_IGNORE_PATTERNS_ADDITIONAL');
        if (!empty($additionalPatterns)) {
            $decoded = json_decode($additionalPatterns, true);
            if (JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
                $defaults = $this->getDefaultIgnorePatterns();
                $additional = $this->validatePatterns($decoded);

                return array_merge($defaults, $additional);
            }
        }

        // Use defaults + WordPress filter
        $defaults = $this->getDefaultIgnorePatterns();
        $filtered = apply_filters('wonolog_ignore_patterns', $defaults);

        return \is_array($filtered) ? $this->validatePatterns($filtered) : $defaults;
    }

    /**
     * Get default ignore patterns for common database errors.
     *
     * @return array<int, array{pattern: string, levelThreshold: null|int, channels: array<string>}>
     */
    private function getDefaultIgnorePatterns(): array
    {
        return [
            [
                'pattern' => "^Can't DROP '.+'; check that column/key exists$",
                'levelThreshold' => null,
                'channels' => [Channels::DB],
            ],
            [
                'pattern' => '^Deadlock found when trying to get lock; try restarting transaction$',
                'levelThreshold' => null,
                'channels' => [Channels::DB],
            ],
            [
                // https://wordpress.org/support/topic/database-error-duplicate-entry-lastnotificationid-for-key-primary/
                'pattern' => "^Duplicate entry '.+' for key",
                'levelThreshold' => null,
                'channels' => [Channels::DB],
            ],
            [
                'pattern' => "^Table '.+' doesn't exist$",
                'levelThreshold' => null,
                'channels' => [Channels::DB],
            ],
        ];
    }

    /**
     * Validate and normalize ignore patterns.
     *
     * @param array<mixed> $patterns Raw patterns from config/filter
     *
     * @return array<int, array{pattern: string, levelThreshold: null|int, channels: array<string>}>
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
            if (!$this->isValidRegex($pattern['pattern'])) {
                error_log('Wonolog: Invalid regex pattern: '.$pattern['pattern']);

                continue;
            }

            // Support both old singular 'channel' and new plural 'channels' key
            $channelsRaw = $pattern['channels'] ?? (isset($pattern['channel']) ? [$pattern['channel']] : []);

            $validated[] = [
                'pattern' => $pattern['pattern'],
                // levelThreshold is EXCLUSIVE: log is ignored only if log->level() < levelThreshold
                'levelThreshold' => $this->convertLevelToConstant($pattern['levelThreshold'] ?? $pattern['level'] ?? null),
                'channels' => $this->convertChannelsToConstants(\is_array($channelsRaw) ? $channelsRaw : [$channelsRaw]),
            ];
        }

        return $validated;
    }

    /**
     * Validate regex pattern.
     */
    private function isValidRegex(string $pattern): bool
    {
        try {
            $delimiter = '~';
            $escapedPattern = str_replace($delimiter, '\\'.$delimiter, $pattern);
            preg_match($delimiter.$escapedPattern.$delimiter, '');

            return true;
        } catch (\Exception) {
            return false;
        }
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
     * Convert channel strings to constants.
     *
     * @param array<mixed> $channels
     *
     * @return array<string>
     */
    private function convertChannelsToConstants(array $channels): array
    {
        $result = [];

        foreach ($channels as $channel) {
            if (\in_array($channel, [null, '', '0'], true)) {
                continue;
            }

            if (!\is_string($channel)) {
                continue;
            }

            $constantName = Channels::class.'::'.strtoupper($channel);

            if (\defined($constantName)) {
                $result[] = \constant($constantName);

                continue;
            }

            // Custom channel names are used as-is
            $result[] = $channel;
        }

        return $result;
    }
}
