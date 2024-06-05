<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function assert;
use function in_array;
use function str_contains;

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
    public const TOKEN_TYPE_VARIABLE          = 11;

    /** @param self::TOKEN_TYPE_* $type */
    public function __construct(
        private readonly int $type,
        private readonly string $value,
    ) {
        assert($value !== '');
    }

    public function value(): string
    {
        return $this->value;
    }

    /** @return self::TOKEN_TYPE_* */
    public function type(): int
    {
        return $this->type;
    }

    /** @param self::TOKEN_TYPE_* ...$types */
    public function isOfType(int ...$types): bool
    {
        return in_array($this->type, $types, true);
    }

    public function hasExtraWhitespace(): bool
    {
        return str_contains($this->value(), ' ') ||
            str_contains($this->value(), "\n") ||
            str_contains($this->value(), "\t");
    }

    public function withValue(string $value): self
    {
        return new self($this->type(), $value);
    }
}
