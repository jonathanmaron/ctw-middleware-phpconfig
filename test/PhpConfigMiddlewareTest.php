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
     * Test that process applies every configured php.ini option when the middleware is dispatched.
     */
    public function testProcessAppliesConfiguredIniOptions(): void
    {
        $stack = [$this->getInstance()];

        Dispatcher::run($stack);

        self::assertSame('On', ini_get('error_prepend_string'));
        self::assertSame('1', ini_get('default_mimetype'));
        self::assertSame('', ini_get('user_agent'));
    }

    /**
     * Test that process throws an UnexpectedValueException when a php.ini option cannot be set.
     */
    public function testProcessThrowsExceptionWhenIniOptionCannotBeSet(): void
    {
        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessageIsOrContains('Cannot set the value of a php.ini configuration option');

        $config = [
            'invalid.invalid' => 'invalid',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);
    }

    /**
     * Test that the middleware implements MiddlewareInterface when instantiated through the factory.
     */
    public function testMiddlewareImplementsMiddlewareInterface(): void
    {
        $middleware = $this->getInstance();

        // @phpstan-ignore-next-line
        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * Test that getConfig returns the exact configuration previously passed to setConfig.
     */
    public function testGetConfigReturnsConfigProvidedToSetConfig(): void
    {
        $config = [
            'default_mimetype' => 1,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        self::assertSame($config, $middleware->getConfig());
    }

    /**
     * Test that setConfig returns the same middleware instance to support a fluent interface.
     */
    public function testSetConfigReturnsSameMiddlewareInstance(): void
    {
        $middleware = new PhpConfigMiddleware();

        $result = $middleware->setConfig([]);

        self::assertSame($middleware, $result);
    }

    /**
     * Test that a boolean true configuration value is normalized to the ini string 'On'.
     */
    public function testProcessNormalizesBooleanTrueToOn(): void
    {
        $config = [
            'error_prepend_string' => true,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('On', ini_get('error_prepend_string'));
    }

    /**
     * Test that a boolean false configuration value is normalized to the ini string 'Off'.
     */
    public function testProcessNormalizesBooleanFalseToOff(): void
    {
        $config = [
            'error_prepend_string' => false,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('Off', ini_get('error_prepend_string'));
    }

    /**
     * Test that a positive integer configuration value is normalized to its string representation.
     */
    public function testProcessNormalizesPositiveIntegerToString(): void
    {
        $config = [
            'default_mimetype' => 1,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('1', ini_get('default_mimetype'));
    }

    /**
     * Test that the integer zero is normalized to the string '0' rather than an empty string.
     */
    public function testProcessNormalizesIntegerZeroToString(): void
    {
        $config = [
            'default_mimetype' => 0,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('0', ini_get('default_mimetype'));
    }

    /**
     * Test that a negative integer configuration value is normalized to its signed string representation.
     */
    public function testProcessNormalizesNegativeIntegerToString(): void
    {
        $config = [
            'default_mimetype' => -1,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('-1', ini_get('default_mimetype'));
    }

    /**
     * Test that a null configuration value is normalized to an empty string.
     */
    public function testProcessNormalizesNullToEmptyString(): void
    {
        $config = [
            'user_agent' => null,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('', ini_get('user_agent'));
    }

    /**
     * Test that a string configuration value is applied unchanged to the php.ini option.
     */
    public function testProcessPassesStringValueThroughUnchanged(): void
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
     * Test that an empty configuration array yields a successful 200 response without altering ini.
     */
    public function testProcessWithEmptyConfigReturnsSuccessfulResponse(): void
    {
        $config = [];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        $response = Dispatcher::run([$middleware]);

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Test that the middleware delegates to the next request handler in the stack.
     */
    public function testProcessDelegatesRequestToNextHandler(): void
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
     * Test that the response produced by the next handler is returned unmodified by the middleware.
     */
    public function testProcessPreservesResponseFromNextHandler(): void
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
     * Provides representative HTTP methods exercised by the middleware.
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
     * Test that the middleware returns a 200 response regardless of the request HTTP method.
     */
    #[DataProvider('httpMethodProvider')]
    public function testProcessReturnsSuccessForAnyHttpMethod(string $method): void
    {
        $request  = Factory::createServerRequest($method, '/');
        $response = Dispatcher::run([$this->getInstance()], $request);

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Test that multiple php.ini options are applied together when configured in a single array.
     */
    public function testProcessAppliesMultipleConfigOptions(): void
    {
        $config = [
            'default_mimetype'     => 1,
            'error_prepend_string' => true,
            'date.timezone'        => 'UTC',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('1', ini_get('default_mimetype'));
        self::assertSame('On', ini_get('error_prepend_string'));
        self::assertSame('UTC', ini_get('date.timezone'));
    }

    /**
     * Test that the factory produces a PhpConfigMiddleware instance from the container configuration.
     */
    public function testFactoryCreatesPhpConfigMiddlewareInstance(): void
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

    /**
     * Test that the factory applies the container's non-empty middleware config to the produced instance.
     */
    public function testFactoryAppliesContainerConfigToMiddleware(): void
    {
        $middlewareConfig = [
            'default_mimetype' => 1,
            'user_agent'       => null,
        ];
        $config           = [
            PhpConfigMiddleware::class => $middlewareConfig,
        ];
        $container        = new ServiceManager();
        $container->setService('config', $config);

        $factory    = new PhpConfigMiddlewareFactory();
        $middleware = $factory($container);

        self::assertSame($middlewareConfig, $middleware->getConfig());
    }

    /**
     * Test that the factory returns a middleware instance when the container has no config service.
     */
    public function testFactoryCreatesMiddlewareWhenContainerHasNoConfigService(): void
    {
        $container = new ServiceManager();

        self::assertFalse($container->has('config'));

        $factory    = new PhpConfigMiddlewareFactory();
        $middleware = $factory($container);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(PhpConfigMiddleware::class, $middleware);
    }

    private function getInstance(): PhpConfigMiddleware
    {
        $config    = [
            PhpConfigMiddleware::class => [
                'error_prepend_string' => true,
                'default_mimetype'     => 1,
                'user_agent'           => null,
            ],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new PhpConfigMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
