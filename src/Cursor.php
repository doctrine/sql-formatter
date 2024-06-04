<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

final class Cursor
{
    private int $position = -1;

    /** @param list<Token> $tokens */
    public function __construct(
        private readonly array $tokens,
    ) {
    }

    /** @param Token::TOKEN_TYPE_* $exceptTokenType */
    public function next(int|null $exceptTokenType = null): Token|null
    {
        while ($token = $this->tokens[++$this->position] ?? null) {
            if ($exceptTokenType !== null && $token->isOfType($exceptTokenType)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    /** @param Token::TOKEN_TYPE_* $exceptTokenType */
    public function previous(int|null $exceptTokenType = null): Token|null
    {
        while ($token = $this->tokens[--$this->position] ?? null) {
            if ($exceptTokenType !== null && $token->isOfType($exceptTokenType)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    public function subCursor(): self
    {
        $cursor           = new self($this->tokens);
        $cursor->position = $this->position;

        return $cursor;
    }
}
