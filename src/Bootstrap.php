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

use WpSpaghetti\Deps\Inpsyde\Wonolog\Configurator;
use WpSpaghetti\Deps\Inpsyde\Wonolog\HookListener\HttpApiListener;
use WpSpaghetti\Deps\Inpsyde\Wonolog\LogLevel;
use WpSpaghetti\Deps\Monolog\Processor\PsrLogMessageProcessor;
use WpSpaghetti\Wonolog\Handler\HandlerFactory;
use WpSpaghetti\Wonolog\Processor\ContextProcessor;
use WpSpaghetti\Wonolog\Service\ConfigurationService;
use WpSpaghetti\Wonolog\Service\PatternService;

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
    private readonly HandlerFactory $handlerFactory;

    private readonly ContextProcessor $contextProcessor;

    private readonly PatternService $patternService;

    private readonly ConfigurationService $configurationService;

    public function __construct(
        ?HandlerFactory $handlerFactory = null,
        ?ContextProcessor $contextProcessor = null,
        ?PatternService $patternService = null,
        ?ConfigurationService $configurationService = null
    ) {
        $this->handlerFactory = $handlerFactory ?? new HandlerFactory();
        $this->contextProcessor = $contextProcessor ?? new ContextProcessor();
        $this->patternService = $patternService ?? new PatternService();
        $this->configurationService = $configurationService ?? new ConfigurationService();
    }

    /**
     * Configure Wonolog with opinionated defaults.
     *
     * @param Configurator $configurator The Wonolog configurator instance
     */
    public function configure(Configurator $configurator): void
    {
        $defaultHandler = $this->handlerFactory->createDefaultHandler();
        $emailHandler = $this->handlerFactory->createEmailHandler();

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
            ->pushProcessor('extra-processor', $this->contextProcessor)
        ;

        $this->patternService->applyIgnorePatterns($configurator);
    }

    /**
     * Configure error reporting based on environment.
     */
    private function configureErrorReporting(Configurator $configurator): void
    {
        if ($this->configurationService->shouldLogSilencedErrors()) {
            $configurator->logSilencedPhpErrors();
        }

        $errorTypes = $this->configurationService->getErrorTypes();

        if (null !== $errorTypes && 0 !== $errorTypes) {
            // In production mode, set PHP error reporting level
            if (!$this->configurationService->shouldLogSilencedErrors()) {
                // https://maximivanov.github.io/php-error-reporting-calculator/
                // https://kau-boys.com/2619/wordpress/set-the-debug-level-using-error_reporting
                // https://discourse.roots.io/t/bedrock-cant-disable-php-notices-warnings/20511
                error_reporting($errorTypes);
            }

            $configurator->logPhpErrorsTypes($errorTypes);
        }
    }
}
