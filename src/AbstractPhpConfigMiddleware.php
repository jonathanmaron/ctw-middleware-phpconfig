<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\AbstractMiddleware;

abstract class AbstractPhpConfigMiddleware extends AbstractMiddleware
{
    private array $config;

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param null|bool|int|string $value
     */
    protected function normalize($value): string
    {
        if (is_bool($value)) {
            return $value ? 'On' : 'Off';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (null === $value) {
            return '';
        }

        return $value;
    }
}
