<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

final class Cursor
{
    private int $position = -1;

    /** @param Token[] $tokens */
    public function __construct(
        private readonly array $tokens,
    ) {
    }

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
