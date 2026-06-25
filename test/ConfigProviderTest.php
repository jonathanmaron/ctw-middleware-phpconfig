<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\ConfigProvider;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddlewareFactory;

final class ConfigProviderTest extends AbstractCase
{
    /**
     * Test that __invoke returns the complete dependency configuration structure when called.
     */
    public function testInvokeReturnsCompleteConfigurationStructure(): void
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

    /**
     * Test that __invoke returns an array containing the 'dependencies' key when called.
     */
    public function testInvokeReturnsArrayWithDependenciesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();

        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that the dependencies array contains the 'factories' key when produced by __invoke.
     */
    public function testInvokeDependenciesContainFactoriesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that getDependencies returns an array containing the 'factories' key when called.
     */
    public function testGetDependenciesReturnsArrayWithFactoriesKey(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that the middleware class is registered as a factory key when getDependencies is called.
     */
    public function testGetDependenciesRegistersMiddlewareInFactories(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertArrayHasKey(PhpConfigMiddleware::class, $factories);
    }

    /**
     * Test that the middleware class maps to its factory class when getDependencies is called.
     */
    public function testGetDependenciesMapsMiddlewareToFactory(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertSame(PhpConfigMiddlewareFactory::class, $factories[PhpConfigMiddleware::class]);
    }

    /**
     * Test that getDependencies returns the same data as the 'dependencies' entry of __invoke.
     */
    public function testGetDependenciesMatchesInvokeDependencies(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertSame($configProvider->getDependencies(), $dependencies);
    }
}
