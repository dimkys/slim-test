<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Odan\Twig\TwigAssetsExtension;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        \Twig\Environment::class => function (ContainerInterface $c): Environment {
            $loader = new FilesystemLoader([
                __DIR__ . '/../view'
            ]);
            $twig = new Environment($loader, [
                'cache' => false,
                'debug' => true
            ]);
            $twig->enableDebug();
            return $twig;
        },
        Twig::class => function (ContainerInterface $container) {
            // Twig settings
            $settings['twig'] = [
                'path' => __DIR__ . '/../var/cache',
                // Should be set to true in production
                'cache_enabled' => false,
                'cache_path' => __DIR__ . '/../tmp/twig-cache',
            ];

            // Twig assets cache
            $settings['assets'] = [
                // Public assets cache directory
                'path' => __DIR__ . '/../compilation_cache',
                // Public url base path
                'url_base_path' => 'cache/',
                // Internal cache directory for the assets
                'cache_path' => __DIR__ . '/tmp/twig-assets',
                'cache_name' => 'assets-cache',
                //  Should be set to 1 (enabled) in production
                'minify' => 1,
            ];

            $twigSettings = $settings['twig'];

            $twig = Twig::create($twigSettings['path'], [
                'cache' => $twigSettings['cache_enabled'] ? $twigSettings['cache_path'] : false,
            ]);

            $environment = $twig->getEnvironment();

            // Add Twig extensions
            $twig->addExtension(new TwigAssetsExtension($environment, (array)$settings['assets']));
            return $twig;
        },
        PDO::class => function (ContainerInterface $container) {
            $dbConn = new PDO("sqlite:" . __DIR__ . "/../db/database.sqlite");
            $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            return $dbConn;
        }
    ]);
};
