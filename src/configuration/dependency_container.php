<?php

use function App\Helpers\env;
use App\Services\JwtService;
use DI\ContainerBuilder;
use function DI\create;
use Illuminate\Database\Capsule\Manager as Capsule;
use MongoDB\Client as MongoDBClient;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

$containerBuilder = new ContainerBuilder();

$connections = require_once __DIR__.'/../db/connections.php';

$capsule = new Capsule();
$capsule->addConnection($connections[env('DB_DRIVER', 'mysql')]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$smtpTransport = new Swift_SmtpTransport(env('MAILER_HOST'), env('MAILER_PORT'), env('MAILER_ENCRYPTION'));
$smtpTransport->setUsername(env('MAILER_USERNAME'));
$smtpTransport->setPassword(env('MAILER_PASSWORD'));

$containerBuilder->addDefinitions([
    Capsule::class => $capsule,
    Swift_SmtpTransport::class => $smtpTransport,
    Swift_Mailer::class => create(Swift_Mailer::class)->constructor($smtpTransport),
    MongoDBClient::class => create(MongoDBClient::class)->constructor(env('MONGODB_CONNECTION_STRING')),
    Logger::class => static function (ContainerInterface $container) {
        $logger = new Logger(env('MONOLOG_LOGGER_NAME', 'journal-api'));
        $logger->pushHandler(new MongoDBHandler($container->get(MongoDBClient::class), env('MONGODB_DATABASE', 'logs'), env('MONGODB_LOGS_COLLECTION', 'logs')));

        return $logger;
    },
    JwtService::class => function () {
        $privateKey = openssl_pkey_get_private('file://'.env('PRIVATE_KEY_PATH'), env('PRIVATE_KEY_PASSPHRASE', ''));
        $publicKey = openssl_pkey_get_public('file://'.env('PUBLIC_KEY_PATH'));
        if (false === $privateKey || false === $publicKey) {
            throw new Error('Cannot find keys to open.');
        }

        return new JwtService($publicKey, $privateKey);
    },
]);

return $containerBuilder->build();
