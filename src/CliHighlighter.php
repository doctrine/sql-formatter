<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function sprintf;
use const PHP_EOL;

final class CliHighlighter implements Highlighter
{
    public const HIGHLIGHT_FUNCTIONS = 'functions';

    /** @var array<string, string> */
    private $escapeSequences;

    /**
     * @param array<string, string> $escapeSequences
     */
    public function __construct(array $escapeSequences = [])
    {
        $this->escapeSequences = $escapeSequences + [
            self::HIGHLIGHT_QUOTE => "\x1b[34;1m",
            self::HIGHLIGHT_BACKTICK_QUOTE =>  "\x1b[35;1m",
            self::HIGHLIGHT_RESERVED =>  "\x1b[37m",
            self::HIGHLIGHT_BOUNDARY => '',
            self::HIGHLIGHT_NUMBER =>  "\x1b[32;1m",
            self::HIGHLIGHT_WORD => '',
            self::HIGHLIGHT_ERROR => "\x1b[31;1;7m",
            self::HIGHLIGHT_COMMENT =>  "\x1b[30;1m",
            self::HIGHLIGHT_VARIABLE =>  "\x1b[36;1m",
            self::HIGHLIGHT_FUNCTIONS => "\x1b[37m",
        ];
    }

    public function highlightToken(int $type, string $value) : string
    {
        if ($type === Token::TOKEN_TYPE_BOUNDARY && ($value==='(' || $value===')')) {
            return $value;
        }

        $prefix = $this->prefix($type);
        if ($prefix === null) {
            return $value;
        }

        return $prefix . $value . "\x1b[0m";
    }

    private function prefix(int $type) : ?string
    {
        if (! isset(self::TOKEN_TYPE_TO_HIGHLIGHT[$type])) {
            return null;
        }

        return $this->escapeSequences[self::TOKEN_TYPE_TO_HIGHLIGHT[$type]];
    }

    public function highlightError(string $value) : string
    {
        return sprintf(
            '%s%s%s%s',
            PHP_EOL,
            $this->escapeSequences[self::HIGHLIGHT_ERROR],
            $value,
            "\x1b[0m"
        );
    }

    public function highlightErrorMessage(string $value) : string
    {
        return $this->highlightError($value);
    }

    public function output(string $string) : string
    {
        return $string . "\n";
    }
}
