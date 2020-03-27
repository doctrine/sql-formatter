<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

final class CliHighlighter implements Highlighter
{
    /** @var string */
    public $cliWord = '';

    /** @var string */
    public $cliQuote = "\x1b[34;1m";

    /** @var string */
    public $cliBacktickQuote = "\x1b[35;1m";

    /** @var string */
    public $cliReserved = "\x1b[37m";

    /** @var string */
    public $cliBoundary = '';

    /** @var string */
    public $cliNumber = "\x1b[32;1m";

    /** @var string */
    public $cliError = "\x1b[31;1;7m";

    /** @var string */
    public $cliComment = "\x1b[30;1m";

    /** @var string */
    public $cliFunctions = "\x1b[37m";

    /** @var string */
    public $cliVariable = "\x1b[36;1m";

    /**
     * {@inheritDoc}
     */
    public function highlightToken(array $token) : string
    {
        [SqlFormatter::TOKEN_TYPE => $type, SqlFormatter::TOKEN_VALUE => $value] = $token;

        if ($type === SqlFormatter::TOKEN_TYPE_BOUNDARY && ($value==='(' || $value===')')) {
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
        switch ($type) {
            case SqlFormatter::TOKEN_TYPE_BOUNDARY:
                return $this->cliBoundary;
            case SqlFormatter::TOKEN_TYPE_WORD:
                return $this->cliWord;
            case SqlFormatter::TOKEN_TYPE_BACKTICK_QUOTE:
                return $this->cliBacktickQuote;
            case SqlFormatter::TOKEN_TYPE_QUOTE:
                return $this->cliQuote;
            case SqlFormatter::TOKEN_TYPE_RESERVED:
            case SqlFormatter::TOKEN_TYPE_RESERVED_TOPLEVEL:
            case SqlFormatter::TOKEN_TYPE_RESERVED_NEWLINE:
                return $this->cliReserved;
            case SqlFormatter::TOKEN_TYPE_NUMBER:
                return $this->cliNumber;
            case SqlFormatter::TOKEN_TYPE_VARIABLE:
                return $this->cliVariable;
            case SqlFormatter::TOKEN_TYPE_COMMENT:
            case SqlFormatter::TOKEN_TYPE_BLOCK_COMMENT:
                return $this->cliComment;
            default:
                return null;
        }
    }

    public function highlightError(string $value) : string
    {
        return $this->cliError . $value . "\x1b[0m";
    }

    public function output(string $string) : string
    {
        return $string . "\n";
    }
}
