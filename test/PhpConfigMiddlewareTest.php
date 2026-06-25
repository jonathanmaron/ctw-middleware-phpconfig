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

        self::assertSame('On', ini_get('error_prepend_string'));
        self::assertSame('1', ini_get('default_mimetype'));
        self::assertSame('', ini_get('user_agent'));
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
            'default_mimetype' => 1,
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
            'error_prepend_string' => true,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('On', ini_get('error_prepend_string'));
    }

    /**
     * Test that boolean false is normalized to 'Off'
     */
    public function testBooleanFalseIsNormalizedToOff(): void
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
     * Test that integer is normalized to string
     */
    public function testIntegerIsNormalizedToString(): void
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
     * Test that null is normalized to empty string
     */
    public function testNullIsNormalizedToEmptyString(): void
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
            'default_mimetype' => 1,
            'error_prepend_string' => true,
            'date.timezone' => 'UTC',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('1', ini_get('default_mimetype'));
        self::assertSame('On', ini_get('error_prepend_string'));
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
            'default_mimetype' => 0,
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);

        self::assertSame('0', ini_get('default_mimetype'));
    }

    private function getInstance(): PhpConfigMiddleware
    {
        $config    = [
            PhpConfigMiddleware::class => [
                'error_prepend_string'  => true,
                'default_mimetype'   => 1,
                'user_agent' => null,
            ],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new PhpConfigMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
