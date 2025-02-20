<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Services\{AuthService, TaskService, ProjectService};
use App\Repository\{UserRepository, TaskRepository, ProjectRepository};
use App\Middleware\{AuthMiddleware, CorsMiddleware, JsonBodyParserMiddleware};
use function DI\autowire;
use function DI\get;

return function(ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // ロガーの設定
        LoggerInterface::class => function(ContainerInterface $c) {
            $logger = new Logger('app');
            $logPath = __DIR__ . '/../../logs/app.log';
            $logger->pushHandler(new StreamHandler(
                $logPath,
                $_ENV['APP_DEBUG'] ? Logger::DEBUG : Logger::INFO
            ));
            return $logger;
        },

        // PDOインスタンスの設定
        PDO::class => function(ContainerInterface $c) {
            $config = require __DIR__ . '/database.php';
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            return new PDO($dsn, $config['username'], $config['password'], $config['options']);
        },

        // リポジトリの登録
        UserRepository::class => autowire()
            ->constructor(get(PDO::class)),
        TaskRepository::class => autowire()
            ->constructor(get(PDO::class)),
        ProjectRepository::class => autowire()
            ->constructor(get(PDO::class)),

        // サービスの登録
        AuthService::class => autowire()
            ->constructor(
                get(UserRepository::class),
                get(LoggerInterface::class)
            ),
        TaskService::class => autowire()
            ->constructor(
                get(TaskRepository::class),
                get(LoggerInterface::class)
            ),
        ProjectService::class => autowire()
            ->constructor(
                get(ProjectRepository::class),
                get(LoggerInterface::class)
            ),

        // ミドルウェアの登録
        AuthMiddleware::class => autowire()
            ->constructor(
                get(AuthService::class),
                get(LoggerInterface::class),
                [
                    '#^/auth/login$#',
                    '#^/auth/register$#',
                    '#^/auth/password/reset$#'
                ]
            ),
        CorsMiddleware::class => autowire()
            ->constructor(
                $_ENV['CORS_ALLOWED_ORIGINS'] ? explode(',', $_ENV['CORS_ALLOWED_ORIGINS']) : ['*'],
                ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                ['Content-Type', 'Authorization', 'X-Requested-With'],
                3600
            ),
        JsonBodyParserMiddleware::class => autowire()
            ->constructor(get(LoggerInterface::class)),

        // 設定値の登録
        'settings' => [
            'displayErrorDetails' => $_ENV['APP_DEBUG'] ?? false,
            'logErrors' => true,
            'logErrorDetails' => true,
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
                'algorithm' => 'HS256',
                'expires' => 3600,
                'issuer' => 'task-management-api',
                'audience' => 'task-management-client'
            ],
            'upload' => [
                'directory' => __DIR__ . '/../../storage/uploads',
                'maxSize' => 5 * 1024 * 1024, // 5MB
                'allowedTypes' => ['image/jpeg', 'image/png', 'application/pdf']
            ]
        ]
    ]);

    // コンパイル時の最適化設定
    if ($_ENV['APP_ENV'] === 'production') {
        $containerBuilder->enableCompilation(__DIR__ . '/../../var/cache');
        $containerBuilder->writeProxiesToFile(true, __DIR__ . '/../../var/cache/proxies');
    }
};
