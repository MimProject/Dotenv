<?php


namespace Mim\Component\Dotenv\Exceptions;


use Throwable;

/**
 * Class FormatException
 * @package Mim\Component\Dotenv\Exceptions
 */
class FormatException extends \LogicException implements ExceptionsInterface
{
    /**
     * @var FormatExceptionContext
     */
    private $context;

    /**
     * FormatException constructor.
     * @param string $message
     * @param FormatExceptionContext $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, FormatExceptionContext $context, int $code = 0, Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct(sprintf("%s in \"%s\" at line %d.\n%s", $message, $context->getPath(), $context->getLineno(), $context->getDetails()), $code, $previous);
    }

    /**
     * @return FormatExceptionContext
     */
    public function getContext(): FormatExceptionContext
    {
        return $this->context;
    }
}