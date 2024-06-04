<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

interface Highlighter
{
    public const TOKEN_TYPE_TO_HIGHLIGHT = [
        Token::TOKEN_TYPE_BOUNDARY => self::HIGHLIGHT_BOUNDARY,
        Token::TOKEN_TYPE_WORD => self::HIGHLIGHT_WORD,
        Token::TOKEN_TYPE_BACKTICK_QUOTE => self::HIGHLIGHT_BACKTICK_QUOTE,
        Token::TOKEN_TYPE_QUOTE => self::HIGHLIGHT_QUOTE,
        Token::TOKEN_TYPE_RESERVED => self::HIGHLIGHT_RESERVED,
        Token::TOKEN_TYPE_RESERVED_TOPLEVEL => self::HIGHLIGHT_RESERVED,
        Token::TOKEN_TYPE_RESERVED_NEWLINE => self::HIGHLIGHT_RESERVED,
        Token::TOKEN_TYPE_NUMBER => self::HIGHLIGHT_NUMBER,
        Token::TOKEN_TYPE_VARIABLE => self::HIGHLIGHT_VARIABLE,
        Token::TOKEN_TYPE_COMMENT => self::HIGHLIGHT_COMMENT,
        Token::TOKEN_TYPE_BLOCK_COMMENT => self::HIGHLIGHT_COMMENT,
    ];

    public const HIGHLIGHT_BOUNDARY       = 'boundary';
    public const HIGHLIGHT_WORD           = 'word';
    public const HIGHLIGHT_BACKTICK_QUOTE = 'backtickQuote';
    public const HIGHLIGHT_QUOTE          = 'quote';
    public const HIGHLIGHT_RESERVED       = 'reserved';
    public const HIGHLIGHT_NUMBER         = 'number';
    public const HIGHLIGHT_VARIABLE       = 'variable';
    public const HIGHLIGHT_COMMENT        = 'comment';
    public const HIGHLIGHT_ERROR          = 'error';

    /**
     * Highlights a token depending on its type.
     *
     * @param Token::TOKEN_TYPE_* $type
     */
    public function highlightToken(int $type, string $value): string;

    /**
     * Highlights a token which causes an issue
     */
    public function highlightError(string $value): string;

    /**
     * Highlights an error message
     */
    public function highlightErrorMessage(string $value): string;

    /**
     * Helper function for building string output
     *
     * @param string $string The string to be quoted
     *
     * @return string The quoted string
     */
    public function output(string $string): string;
}
