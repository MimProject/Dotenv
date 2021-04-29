<?php


namespace Mim\Component\Dotenv\Exceptions;


/**
 * Class FormatExceptionContext
 * @package Mim\Component\Dotenv\Exceptions
 */
final class FormatExceptionContext
{
    /**
     * @var string
     */
    private $data;
    /**
     * @var string
     */
    private $path;
    /**
     * @var int
     */
    private $lineno;
    /**
     * @var int
     */
    private $cursor;

    /**
     * FormatExceptionContext constructor.
     * @param string $data
     * @param string $path
     * @param int $lineno
     * @param int $cursor
     */
    public function __construct(string $data, string $path, int $lineno, int $cursor)
    {
        $this->data = $data;
        $this->path = $path;
        $this->lineno = $lineno;
        $this->cursor = $cursor;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getLineno(): int
    {
        return $this->lineno;
    }

    /**
     * @return string
     */
    public function getDetails(): string
    {
        $before = str_replace("\n", '\n', substr($this->data, max(0, $this->cursor - 20), min(20, $this->cursor)));
        $after = str_replace("\n", '\n', substr($this->data, $this->cursor, 20));

        return '...' . $before . $after . "...\n" . str_repeat(' ', strlen($before) + 2) . '^ line ' . $this->lineno . ' offset ' . $this->cursor;
    }
}