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

    public function value(): string
    {
        return $this->value;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function isOfType(int ...$types): bool
    {
        return in_array($this->type, $types, true);
    }

    public function hasExtraWhitespace(): bool
    {
        return strpos($this->value(), ' ') !== false ||
            strpos($this->value(), "\n") !== false ||
            strpos($this->value(), "\t") !== false;
    }

    public function withValue(string $value): self
    {
        return new self($this->type(), $value);
    }

    public function isBlockStart(): ?Condition
    {
        $condition = new Condition();

        if ($this->value === '(') {
            $condition->values     = [')'];
            $condition->addNewline = true;

            return $condition;
        }

        $joins = [
            'LEFT OUTER JOIN',
            'RIGHT OUTER JOIN',
            'LEFT JOIN',
            'RIGHT JOIN',
            'OUTER JOIN',
            'INNER JOIN',
            'JOIN',
        ];
        if (in_array($this->value, $joins, true)) {
            $condition->values = $joins;
            $condition->types  = [self::TOKEN_TYPE_RESERVED_TOPLEVEL];
            $condition->eof    = true;

            return $condition;
        }

        return null;
    }

    public function isBlockEnd(Condition $condition): bool
    {
        if ($this->isOfType(...$condition->types)) {
            return true;
        }

        return in_array($this->value, $condition->values, true);
    }
}
