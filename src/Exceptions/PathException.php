<?php


namespace Mim\Component\Dotenv\Exceptions;


use Throwable;

/**
 * Class PathException
 * @package Mim\Component\Dotenv\Exceptions
 */
final class PathException extends \RuntimeException implements ExceptionsInterface
{
    /**
     * PathException constructor.
     * @param string $path
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $path, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Unable to read "%s" environment file', $path), $code, $previous);
    }
}