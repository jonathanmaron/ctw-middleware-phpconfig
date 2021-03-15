<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Middlewares\Utils\Dispatcher;

class PhpConfigMiddlewareTest extends AbstractCase
{
    public function testPhpConfigMiddleware(): void
    {
        $config = [
            'opcache.validate_timestamps' => true,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        $this->assertEquals('On', ini_get('opcache.validate_timestamps'));
    }
}
