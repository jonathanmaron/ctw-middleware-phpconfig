<?php
declare(strict_types=1);

namespace Ctw\Middleware\PhpConfigMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;

class PhpConfigMiddleware extends AbstractPhpConfigMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->getConfig() as $option => $value) {
            if (false === ini_set($option, $value = $this->normalize($value))) {
                $format  = 'Cannot set the value of a php.ini configuration option ("%s" => "%s").';
                $message = sprintf($format, $option, $value);
                throw new UnexpectedValueException($message);
            }
        }

        return $handler->handle($request);
    }
}
