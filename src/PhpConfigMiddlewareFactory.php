<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

use Psr\Container\ContainerInterface;

class PhpConfigMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): PhpConfigMiddleware
    {
        $config = [];
        if ($container->has('config')) {
            $containerConfig = $container->get('config');
            assert(is_array($containerConfig));
            $config = $containerConfig[PhpConfigMiddleware::class];
            assert(is_array($config));
        }

        $middleware = new PhpConfigMiddleware();

        if ([] !== $config) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
