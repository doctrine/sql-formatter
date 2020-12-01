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
    public const HIGHLIGHT_PRE = 'pre';

    /**
     * This flag tells us if queries need to be enclosed in <pre> tags
     *
     * @var bool
     */
    private $usePre;

    /** @var array<string, string> */
    private $htmlAttributes;

    /**
     * @param array<string, string> $htmlAttributes
     */
    public function __construct(array $htmlAttributes = [], bool $usePre = true)
    {
        $this->htmlAttributes = $htmlAttributes + [
            self::HIGHLIGHT_QUOTE => 'style="color: blue;"',
            self::HIGHLIGHT_BACKTICK_QUOTE => 'style="color: purple;"',
            self::HIGHLIGHT_RESERVED => 'style="font-weight:bold;"',
            self::HIGHLIGHT_BOUNDARY => '',
            self::HIGHLIGHT_NUMBER => 'style="color: green;"',
            self::HIGHLIGHT_WORD => 'style="color: #333;"',
            self::HIGHLIGHT_ERROR => 'style="background-color: red;"',
            self::HIGHLIGHT_COMMENT => 'style="color: #aaa;"',
            self::HIGHLIGHT_VARIABLE => 'style="color: orange;"',
            self::HIGHLIGHT_PRE => 'style="color: black; background-color: white;"',
        ];
        $this->usePre         = $usePre;
    }

    public function highlightToken(int $type, string $value): string
    {
        $value = htmlentities($value, ENT_COMPAT | ENT_IGNORE, 'UTF-8');

        if ($type === Token::TOKEN_TYPE_BOUNDARY && ($value === '(' || $value === ')')) {
            return $value;
        }

        $attributes = $this->attributes($type);
        if ($attributes === null) {
            return $value;
        }

        return '<span ' . $attributes . '>' . $value . '</span>';
    }

    public function attributes(int $type): ?string
    {
        if (! isset(self::TOKEN_TYPE_TO_HIGHLIGHT[$type])) {
            return null;
        }

        return $this->htmlAttributes[self::TOKEN_TYPE_TO_HIGHLIGHT[$type]];
    }

    public function highlightError(string $value): string
    {
        return sprintf(
            '%s<span %s>%s</span>',
            PHP_EOL,
            $this->htmlAttributes[self::HIGHLIGHT_ERROR],
            $value
        );
    }

    public function highlightErrorMessage(string $value): string
    {
        return $this->highlightError($value);
    }

    public function output(string $string): string
    {
        $string = trim($string);
        if (! $this->usePre) {
            return $string;
        }

        return '<pre ' . $this->htmlAttributes[self::HIGHLIGHT_PRE] . '>' . $string . '</pre>';
    }
}
