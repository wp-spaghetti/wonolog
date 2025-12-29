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

use WpSpaghetti\Deps\Vectorface\Whip\Whip;

use function WpSpaghetti\Deps\Safe\error_log;

/**
 * Service for detecting client IP addresses using Vectorface/whip library.
 *
 * SECURITY: Whip validates that proxy headers come from trusted sources
 * by checking if REMOTE_ADDR is in the configured whitelist.
 */
class IpDetectionService
{
    /**
     * Get client IP address using Vectorface/whip library.
     *
     * @return array{ip: string, source: string} IP address and detection source
     */
    public function getClientIp(): array
    {
        // CLI context
        if (\PHP_SAPI === 'cli') {
            return ['ip' => '0.0.0.0', 'source' => 'CLI'];
        }

        $methods = $this->getDetectionMethods();
        $whitelists = $this->getWhitelists();
        $customHeaders = $this->getCustomHeaders();

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
            $source = $this->detectSource($ip, $methods, $customHeaders);

            return ['ip' => $ip, 'source' => $source];
        } catch (\Exception $exception) {
            error_log('Wonolog: Error detecting IP - '.$exception->getMessage());

            return ['ip' => '0.0.0.0', 'source' => 'error'];
        }
    }

    /**
     * Get IP detection methods from filter.
     *
     * @return int Bitmask of detection methods
     */
    private function getDetectionMethods(): int
    {
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

        return \is_int($methods) ? $methods : Whip::ALL_METHODS;
    }

    /**
     * Get IP whitelists from filter.
     *
     * @return array<int, array<string, array<string>>> IP ranges per method
     */
    private function getWhitelists(): array
    {
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

        return \is_array($whitelists) ? $whitelists : [];
    }

    /**
     * Get custom headers from filter.
     *
     * @return array<string> Custom header names
     */
    private function getCustomHeaders(): array
    {
        /**
         * Filter custom headers for IP detection.
         *
         * These are used when Whip::CUSTOM_HEADERS is enabled.
         * Header names without HTTP_ prefix (Whip adds it automatically).
         *
         * @param array<string> $headers Custom header names
         */
        $customHeaders = apply_filters('wonolog_ip_custom_headers', []);

        return \is_array($customHeaders) ? $customHeaders : [];
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
    private function detectSource(string $detectedIp, int $methods, array $customHeaders): string
    {
        // Check custom headers first (highest priority in Whip's logic)
        if (($methods & Whip::CUSTOM_HEADERS) && [] !== $customHeaders) {
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

        // Check known headers in priority order
        $headerChecks = [
            Whip::INCAPSULA_HEADERS => ['HTTP_INCAP_CLIENT_IP' => 'incap-client-ip'],
            Whip::CLOUDFLARE_HEADERS => ['HTTP_CF_CONNECTING_IP' => 'cf-connecting-ip'],
            Whip::PROXY_HEADERS => [
                'HTTP_CLIENT_IP' => 'client-ip',
                'HTTP_X_FORWARDED_FOR' => 'x-forwarded-for',
                'HTTP_X_FORWARDED' => 'x-forwarded',
                'HTTP_X_CLUSTER_CLIENT_IP' => 'x-cluster-client-ip',
                'HTTP_FORWARDED_FOR' => 'forwarded-for',
                'HTTP_FORWARDED' => 'forwarded',
                'HTTP_X_REAL_IP' => 'x-real-ip',
            ],
        ];

        foreach ($headerChecks as $method => $headers) {
            if (($methods & $method) !== 0) {
                foreach ($headers as $serverKey => $name) {
                    if (!empty($_SERVER[$serverKey])) {
                        $headerIp = $this->extractFirstIp($_SERVER[$serverKey]);
                        if ($headerIp === $detectedIp) {
                            return $name;
                        }
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
