<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

interface Highlighter
{
    /**
     * Highlights a token depending on its type.
     */
    public function highlightToken(int $type, string $value) : string;

    public function highlightError(string $value) : string;

    /**
     * Helper function for building string output
     *
     * @param string $string The string to be quoted
     *
     * @return string The quoted string
     */
    public function output(string $string) : string;
}
