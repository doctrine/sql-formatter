<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function htmlentities;
use function sprintf;
use function trim;
use const ENT_COMPAT;
use const ENT_IGNORE;
use const PHP_EOL;

final class HtmlHighlighter implements Highlighter
{
    // Styles applied to different token types
    /** @var string */
    public $quoteAttributes = 'style="color: blue;"';

    /** @var string */
    public $backtickQuoteAttributes = 'style="color: purple;"';

    /** @var string */
    public $reservedAttributes = 'style="font-weight:bold;"';

    /** @var string */
    public $boundaryAttributes = '';

    /** @var string */
    public $numberAttributes = 'style="color: green;"';

    /** @var string */
    public $wordAttributes = 'style="color: #333;"';

    /** @var string */
    public $errorAttributes = 'style="background-color: red;"';

    /** @var string */
    public $commentAttributes = 'style="color: #aaa;"';

    /** @var string */
    public $variableAttributes = 'style="color: orange;"';

    /** @var string */
    public $preAttributes = 'style="color: black; background-color: white;"';

    /**
     * This flag tells us if queries need to be enclosed in <pre> tags
     *
     * @var bool
     */
    public $usePre = true;

    public function highlightToken(int $type, string $value) : string
    {
        $value = htmlentities($value, ENT_COMPAT | ENT_IGNORE, 'UTF-8');

        if ($type === Token::TOKEN_TYPE_BOUNDARY && ($value==='(' || $value===')')) {
            return $value;
        }

        $attributes = $this->attributes($type);
        if ($attributes === null) {
            return $value;
        }

        return '<span ' . $this->attributes($type) . '>' . $value . '</span>';
    }

    public function attributes(int $type) : ?string
    {
        switch ($type) {
            case Token::TOKEN_TYPE_BOUNDARY:
                return $this->boundaryAttributes;
            case Token::TOKEN_TYPE_WORD:
                return $this->wordAttributes;
            case Token::TOKEN_TYPE_BACKTICK_QUOTE:
                return $this->backtickQuoteAttributes;
            case Token::TOKEN_TYPE_QUOTE:
                return $this->quoteAttributes;
            case Token::TOKEN_TYPE_RESERVED:
            case Token::TOKEN_TYPE_RESERVED_TOPLEVEL:
            case Token::TOKEN_TYPE_RESERVED_NEWLINE:
                return $this->reservedAttributes;
            case Token::TOKEN_TYPE_NUMBER:
                return $this->numberAttributes;
            case Token::TOKEN_TYPE_VARIABLE:
                return $this->variableAttributes;
            case Token::TOKEN_TYPE_COMMENT:
            case Token::TOKEN_TYPE_BLOCK_COMMENT:
                return $this->commentAttributes;
            default:
                return null;
        }
    }

    public function highlightError(string $value) : string
    {
        return sprintf('%s<span %s>%s</span>', PHP_EOL, $this->errorAttributes, $value);
    }

    public function output(string $string) : string
    {
        $string =trim($string);
        if (! $this->usePre) {
            return $string;
        }

        return '<pre ' . $this->preAttributes . '>' . $string . '</pre>';
    }
}
