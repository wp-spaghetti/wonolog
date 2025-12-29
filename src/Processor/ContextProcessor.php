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

namespace WpSpaghetti\Wonolog\Processor;

use WpSpaghetti\Deps\Monolog\LogRecord;
use WpSpaghetti\Wonolog\Service\IpDetectionService;
use WpSpaghetti\Wonolog\Support\SecurityHelper;

use function WpSpaghetti\Deps\Safe\gethostname;

/**
 * Processor that adds extra context information to log records.
 *
 * Adds: hostname, client IP, request data, server data (filtered).
 */
class ContextProcessor
{
    private IpDetectionService $ipDetectionService;

    public function __construct(?IpDetectionService $ipDetectionService = null)
    {
        $this->ipDetectionService = $ipDetectionService ?? new IpDetectionService();
    }

    /**
     * Add extra context to log records.
     *
     * @param array|LogRecord $record The log record
     *
     * @return array|LogRecord Modified log record with extra context
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $extraData = $this->buildExtraData();

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
     * Build extra data array for log context.
     *
     * @return array<string, mixed>
     */
    private function buildExtraData(): array
    {
        $hostname = $this->getHostname();
        $ipData = $this->ipDetectionService->getClientIp();
        $hostbyaddr = $this->getHostByAddr($ipData['ip']);
        $filteredServer = SecurityHelper::filterServerData($_SERVER);

        return [
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
    }

    /**
     * Get hostname safely.
     */
    private function getHostname(): ?string
    {
        try {
            return gethostname(); // php_uname('n')
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get reverse DNS lookup for IP.
     */
    private function getHostByAddr(string $ip): ?string
    {
        if ('0.0.0.0' === $ip) {
            return null;
        }

        try {
            return gethostbyaddr($ip);
        } catch (\Exception) {
            return null;
        }
    }
}
