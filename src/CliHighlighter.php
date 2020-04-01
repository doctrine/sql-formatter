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
        switch ($type) {
            case Token::TOKEN_TYPE_BOUNDARY:
                return $this->cliBoundary;
            case Token::TOKEN_TYPE_WORD:
                return $this->cliWord;
            case Token::TOKEN_TYPE_BACKTICK_QUOTE:
                return $this->cliBacktickQuote;
            case Token::TOKEN_TYPE_QUOTE:
                return $this->cliQuote;
            case Token::TOKEN_TYPE_RESERVED:
            case Token::TOKEN_TYPE_RESERVED_TOPLEVEL:
            case Token::TOKEN_TYPE_RESERVED_NEWLINE:
                return $this->cliReserved;
            case Token::TOKEN_TYPE_NUMBER:
                return $this->cliNumber;
            case Token::TOKEN_TYPE_VARIABLE:
                return $this->cliVariable;
            case Token::TOKEN_TYPE_COMMENT:
            case Token::TOKEN_TYPE_BLOCK_COMMENT:
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
