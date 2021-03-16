<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use  Psr\Http\Message\ResponseInterface;

class PhpConfigMiddlewareTest extends AbstractCase
{
    public function testPhpConfigMiddleware(): void
    {
        $stack = [
            $this->getInstance(),
        ];

        Dispatcher::run($stack);

        $this->assertEquals('On', ini_get('opcache.validate_timestamps'));
    }

    public function testPhpConfigMiddlewareException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $config = [
            'invalid.invalid' => 'invalid',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);
    }

    private function getInstance(): PhpConfigMiddleware
    {
        $config    = [
            PhpConfigMiddleware::class => [
                'opcache.validate_timestamps' => true,
            ],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new PhpConfigMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
