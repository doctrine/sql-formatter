<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

final class NullHighlighter implements Highlighter
{
    public function highlightToken(int $type, string $value) : string
    {
        return $value;
    }

    public function highlightError(string $value) : string
    {
        return $value;
    }

    public function highlightErrorMessage(string $value) : string
    {
        return ' ' . $value;
    }

    public function output(string $string) : string
    {
        return $string;
    }
}
