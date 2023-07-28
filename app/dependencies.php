<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Infrastructure\Persistence\SensorData\SqliteSensorDataRepository;

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

        PDO::class => function (ContainerInterface $c) {
            $dsn = "sqlite:./sensor.db";
            try {
                return new PDO($dsn);
            } catch (PDOException $e) {
                echo "Connection to database failed: " . $e->getMessage();
                exit();
            }
        }
        ,        

        SqliteSensorDataRepository::class => function (ContainerInterface $c) {
            return new SqliteSensorDataRepository($c->get(PDO::class));
        },
    ]);
};
