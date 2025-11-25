<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Server\MiddlewareInterface;

final class PhpConfigMiddlewareTest extends AbstractCase
{
    /**
     * Test that middleware applies PHP configuration
     */
    public function testPhpConfigMiddleware(): void
    {
        $stack = [$this->getInstance()];

        Dispatcher::run($stack);

        self::assertSame('On', ini_get('assert.warning'));
        self::assertSame('1', ini_get('assert.active'));
        self::assertSame('', ini_get('assert.callback'));
    }

    /**
     * Test that invalid configuration option throws exception
     */
    public function testPhpConfigMiddlewareException(): void
    {
        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage('Cannot set the value of a php.ini configuration option');

        $config = [
            'invalid.invalid' => 'invalid',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);
    }

    /**
     * Test that middleware implements MiddlewareInterface
     */
    public function testMiddlewareImplementsMiddlewareInterface(): void
    {
        $middleware = $this->getInstance();

        // @phpstan-ignore-next-line
        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * Test that getConfig returns config set by setConfig
     */
    public function testGetConfigReturnsSetConfig(): void
    {
        $config = [
            'assert.active' => 1,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        self::assertSame($config, $middleware->getConfig());
    }

    /**
     * Test that setConfig returns self for fluent interface
     */
    public function testSetConfigReturnsMiddlewareForFluentInterface(): void
    {
        $middleware = new PhpConfigMiddleware();

        $result = $middleware->setConfig([]);

        self::assertSame($middleware, $result);
    }

    /**
     * Test that boolean true is normalized to 'On'
     */
    public function testBooleanTrueIsNormalizedToOn(): void
    {
        $config = [
            'assert.warning' => true,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('On', ini_get('assert.warning'));
    }

    /**
     * Test that boolean false is normalized to 'Off'
     */
    public function testBooleanFalseIsNormalizedToOff(): void
    {
        $config = [
            'assert.warning' => false,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        // PHP's ini_set with 'Off' results in 'Off' being stored, but ini_get may return empty string
        $value = ini_get('assert.warning');
        self::assertTrue('' === $value || 'Off' === $value);
    }

    /**
     * Test that integer is normalized to string
     */
    public function testIntegerIsNormalizedToString(): void
    {
        $config = [
            'assert.active' => 1,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('1', ini_get('assert.active'));
    }

    /**
     * Test that null is normalized to empty string
     */
    public function testNullIsNormalizedToEmptyString(): void
    {
        $config = [
            'assert.callback' => null,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('', ini_get('assert.callback'));
    }

    /**
     * Test that string value is passed through unchanged
     */
    public function testStringValueIsPassedThrough(): void
    {
        $config = [
            'date.timezone' => 'UTC',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('UTC', ini_get('date.timezone'));
    }

    /**
     * Test that empty config array does not cause issues
     */
    public function testEmptyConfigArray(): void
    {
        $config = [];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        $response = Dispatcher::run([$middleware]);

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Test that middleware passes request to handler
     */
    public function testMiddlewarePassesRequestToHandler(): void
    {
        $handlerCalled = false;
        $stack         = [
            $this->getInstance(),
            /**
             * @param mixed $request
             * @param mixed $next
             * @return \Psr\Http\Message\ResponseInterface
             */
            static function ($request, $next) use (&$handlerCalled) {
                /** @var \Psr\Http\Server\RequestHandlerInterface $next */
                /** @var \Psr\Http\Message\ServerRequestInterface $request */
                $handlerCalled = true;

                return $next->handle($request);
            },
        ];
        Dispatcher::run($stack);

        self::assertTrue($handlerCalled);
    }

    /**
     * Test that handler response is preserved
     */
    public function testHandlerResponseIsPreserved(): void
    {
        $stack = [
            $this->getInstance(),
            /**
             * @param mixed $request
             * @param mixed $next
             * @return \Psr\Http\Message\ResponseInterface
             */
            static function ($request, $next) {
                /** @var \Psr\Http\Server\RequestHandlerInterface $next */
                /** @var \Psr\Http\Message\ServerRequestInterface $request */
                $response = $next->handle($request);

                return $response->withHeader('X-Custom', 'value');
            },
        ];
        $response = Dispatcher::run($stack);

        self::assertTrue($response->hasHeader('X-Custom'));
        self::assertSame('value', $response->getHeaderLine('X-Custom'));
    }

    /**
     * Test various HTTP methods
     *
     * @return array<string, array{method: string}>
     */
    public static function httpMethodProvider(): array
    {
        return [
            'GET request'    => [
                'method' => 'GET',
            ],
            'POST request'   => [
                'method' => 'POST',
            ],
            'PUT request'    => [
                'method' => 'PUT',
            ],
            'DELETE request' => [
                'method' => 'DELETE',
            ],
        ];
    }

    /**
     * Test that middleware works with various HTTP methods
     */
    #[DataProvider('httpMethodProvider')]
    public function testMiddlewareWorksWithVariousHttpMethods(string $method): void
    {
        $request  = Factory::createServerRequest($method, '/');
        $response = Dispatcher::run([$this->getInstance()], $request);

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Test that multiple config options can be set
     */
    public function testMultipleConfigOptionsCanBeSet(): void
    {
        $config = [
            'assert.active' => 1,
            'assert.warning' => true,
            'date.timezone' => 'UTC',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('1', ini_get('assert.active'));
        self::assertSame('On', ini_get('assert.warning'));
        self::assertSame('UTC', ini_get('date.timezone'));
    }

    /**
     * Test that factory creates middleware instance
     */
    public function testFactoryCreatesMiddlewareInstance(): void
    {
        $config    = [
            PhpConfigMiddleware::class => [],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory    = new PhpConfigMiddlewareFactory();
        $middleware = $factory($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(PhpConfigMiddleware::class, $middleware);
    }


    public function testIntegerZeroIsNormalizedToString(): void
    {
        $config = [
            'assert.active' => 0,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('0', ini_get('assert.active'));
    }

    private function getInstance(): PhpConfigMiddleware
    {
        $config    = [
            PhpConfigMiddleware::class => [
                'assert.warning'  => true,
                'assert.active'   => 1,
                'assert.callback' => null,
            ],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new PhpConfigMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
