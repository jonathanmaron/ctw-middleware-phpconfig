<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\ConfigProvider;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddlewareFactory;

class ConfigProviderTest extends AbstractCase
{
    public function testConfigProvider(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'dependencies' => [
                'factories' => [
                    PhpConfigMiddleware::class => PhpConfigMiddlewareFactory::class,
                ],
            ],
        ];

        self::assertSame($expected, $configProvider->__invoke());
    }
}
