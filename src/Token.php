<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function in_array;
use function strpos;

final class Token
{
    // Constants for token types
    public const TOKEN_TYPE_WHITESPACE        = 0;
    public const TOKEN_TYPE_WORD              = 1;
    public const TOKEN_TYPE_QUOTE             = 2;
    public const TOKEN_TYPE_BACKTICK_QUOTE    = 3;
    public const TOKEN_TYPE_RESERVED          = 4;
    public const TOKEN_TYPE_RESERVED_TOPLEVEL = 5;
    public const TOKEN_TYPE_RESERVED_NEWLINE  = 6;
    public const TOKEN_TYPE_BOUNDARY          = 7;
    public const TOKEN_TYPE_COMMENT           = 8;
    public const TOKEN_TYPE_BLOCK_COMMENT     = 9;
    public const TOKEN_TYPE_NUMBER            = 10;
    public const TOKEN_TYPE_ERROR             = 11;
    public const TOKEN_TYPE_VARIABLE          = 12;

    // Constants for different components of a token
    public const TOKEN_TYPE  = 0;
    public const TOKEN_VALUE = 1;

    /** @var int */
    private $type;

    /** @var string */
    private $value;

    public function __construct(int $type, string $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }

    public function value() : string
    {
        return $this->value;
    }

    public function type() : int
    {
        return $this->type;
    }

    public function isOfType(int ...$types) : bool
    {
        return in_array($this->type, $types, true);
    }

    public function hasExtraWhitespace() : bool
    {
        return strpos($this->value(), ' ')!== false ||
            strpos($this->value(), "\n") !== false ||
            strpos($this->value(), "\t") !== false;
    }

    public function withValue(string $value) : self
    {
        return new self($this->type(), $value);
    }
}
