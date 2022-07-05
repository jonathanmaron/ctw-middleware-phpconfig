<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class PhpConfigMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PhpConfigMiddleware
    {
        $config = [];
        if ($container->has('config')) {
            $config = $container->get('config');
            assert(is_array($config));
            $config = $config[PhpConfigMiddleware::class];
        }

        $middleware = new PhpConfigMiddleware();

        if (count($config) > 0) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
