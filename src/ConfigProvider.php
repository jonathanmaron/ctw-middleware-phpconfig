<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                PhpConfigMiddleware::class => PhpConfigMiddlewareFactory::class,
            ],
        ];
    }
}
