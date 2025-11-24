<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PhpConfigMiddleware extends AbstractPhpConfigMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->getConfig();

        foreach ($config as $option => $value) {
            assert(is_bool($value) || is_int($value) || is_string($value) || null === $value);
            $value = $this->normalize($value);
            if (false === ini_set($option, $value)) {
                $format  = 'Cannot set the value of a php.ini configuration option ("%s" => "%s").';
                $message = sprintf($format, $option, $value);
                throw new UnexpectedValueException($message);
            }
        }

        return $handler->handle($request);
    }
}
