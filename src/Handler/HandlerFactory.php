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

namespace WpSpaghetti\Wonolog\Handler;

use WpSpaghetti\Deps\Inpsyde\Wonolog\DefaultHandler\LogsFolder;
use WpSpaghetti\Deps\Inpsyde\Wonolog\LogLevel;
use WpSpaghetti\Deps\Monolog\Formatter\HtmlFormatter;
use WpSpaghetti\Deps\Monolog\Formatter\LineFormatter;
use WpSpaghetti\Deps\Monolog\Handler\DeduplicationHandler;
use WpSpaghetti\Deps\Monolog\Handler\ErrorLogHandler;
use WpSpaghetti\Deps\Monolog\Handler\NativeMailerHandler;
use WpSpaghetti\Deps\Monolog\Handler\RotatingFileHandler;
use WpSpaghetti\Wonolog\Service\ConfigurationService;
use WpSpaghetti\WpEnv\Environment;

/**
 * Factory for creating Monolog handlers.
 *
 * Creates different handlers based on environment (development vs production).
 */
class HandlerFactory
{
    private readonly ConfigurationService $configurationService;

    public function __construct(?ConfigurationService $configurationService = null)
    {
        $this->configurationService = $configurationService ?? new ConfigurationService();
    }

    /**
     * Create the default log handler based on environment.
     */
    public function createDefaultHandler(): ErrorLogHandler|RotatingFileHandler
    {
        if (Environment::isDebug()) {
            return $this->createErrorLogHandler();
        }

        return $this->createRotatingFileHandler();
    }

    /**
     * Create email notification handler for errors.
     */
    public function createEmailHandler(): DeduplicationHandler|NativeMailerHandler
    {
        $nativeMailerHandler = $this->createNativeMailerHandler();

        // Wrap with deduplication handler in production
        if (!Environment::isDebug()) {
            return $this->createDeduplicationHandler($nativeMailerHandler);
        }

        return $nativeMailerHandler;
    }

    /**
     * Create ErrorLogHandler for development.
     */
    private function createErrorLogHandler(): ErrorLogHandler
    {
        $errorLogHandler = new ErrorLogHandler(ErrorLogHandler::SAPI, LogLevel::defaultMinLevel());

        // The last "true" tells monolog to remove empty []'s
        $errorLogHandler->setFormatter(new LineFormatter(null, null, false, true));

        return $errorLogHandler;
    }

    /**
     * Create RotatingFileHandler for production.
     */
    private function createRotatingFileHandler(): RotatingFileHandler
    {
        $maxFiles = $this->configurationService->getMaxFiles();
        $filePermission = $this->configurationService->getFilePermission();

        $rotatingFileHandler = new RotatingFileHandler(
            LogsFolder::determineFolder().'app.log',
            $maxFiles,
            LogLevel::defaultMinLevel(),
            true,
            $filePermission
        );

        // The last "true" tells monolog to remove empty []'s
        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));

        return $rotatingFileHandler;
    }

    /**
     * Create NativeMailerHandler for email notifications.
     */
    private function createNativeMailerHandler(): NativeMailerHandler
    {
        $emailTo = $this->configurationService->getEmailRecipients();
        $emailFrom = $this->configurationService->getEmailFrom();
        $emailSubject = $this->configurationService->getEmailSubject();
        $emailLevel = $this->configurationService->getEmailLevel();

        $nativeMailerHandler = new NativeMailerHandler(
            $emailTo,
            $emailSubject,
            $emailFrom,
            $emailLevel
        );

        $nativeMailerHandler->setContentType('text/html');
        $nativeMailerHandler->setFormatter(new HtmlFormatter());

        return $nativeMailerHandler;
    }

    /**
     * Create DeduplicationHandler wrapping email handler.
     */
    private function createDeduplicationHandler(NativeMailerHandler $nativeMailerHandler): DeduplicationHandler
    {
        $dedupTime = $this->configurationService->getDedupTime();
        $emailLevel = $this->configurationService->getEmailLevel();

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
}
