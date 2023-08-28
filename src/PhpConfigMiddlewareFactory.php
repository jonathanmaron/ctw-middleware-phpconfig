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
            $config = $container->get('config');
            assert(is_array($config));
            $config = $config[PhpConfigMiddleware::class];
        }

        $middleware = new PhpConfigMiddleware();

        if ((is_countable($config) ? count($config) : 0) > 0) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
