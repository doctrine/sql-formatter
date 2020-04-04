<?php

declare(strict_types=1);

/**
 * SQL Formatter is a collection of utilities for debugging SQL queries.
 * It includes methods for formatting, syntax highlighting, removing comments, etc.
 *
 * @link       http://github.com/jdorn/sql-formatter
 */

namespace Doctrine\SqlFormatter;

use function array_search;
use function array_shift;
use function array_unshift;
use function assert;
use function current;
use function preg_replace;
use function reset;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;
use const PHP_SAPI;

final class SqlFormatter
{
    /**
     * The tab character to use when formatting SQL
     *
     * @var string
     */
    public $tab = '  ';

    /** @var Tokenizer */
    private $tokenizer;

    /** @var Highlighter */
    private $highlighter;

    public function __construct(
        ?Tokenizer $tokenizer = null,
        ?Highlighter $highlighter = null
    ) {
        $this->tokenizer   = $tokenizer ?? new Tokenizer();
        $this->highlighter = $highlighter ?? (PHP_SAPI === 'cli' ? new CliHighlighter() : new HtmlHighlighter());
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public function format(string $string) : string
    {
        // This variable will be populated with formatted html
        $return = '';

        // Use an actual tab while formatting and then switch out with $this->tab at the end
        $tab = "\t";

        $indentLevel           = 0;
        $newline               = false;
        $inlineParentheses     = false;
        $increaseSpecialIndent = false;
        $increaseBlockIndent   = false;
        $indentTypes           = [];
        $addedNewline          = false;
        $inlineCount           = 0;
        $inlineIndented        = false;
        $clauseLimit           = false;

        // Tokenize String
        $originalTokens = $this->tokenizer->tokenize($string);

        // Remove existing whitespace
        $indexedTokens = [];
        foreach ($originalTokens as $i => $token) {
            if ($token->type() === Token::TOKEN_TYPE_WHITESPACE) {
                continue;
            }

            $indexedTokens[] = ['originalIndex' => $i, 'token' => $token];
        }

        // Format token by token
        foreach ($indexedTokens as $i => $indexedToken) {
            $token       = $indexedToken['token'];
            $highlighted = $this->highlighter->highlightToken(
                $token->type(),
                $token->value()
            );

            // If we are increasing the special indent level now
            if ($increaseSpecialIndent) {
                $indentLevel++;
                $increaseSpecialIndent = false;
                array_unshift($indentTypes, 'special');
            }

            // If we are increasing the block indent level now
            if ($increaseBlockIndent) {
                $indentLevel++;
                $increaseBlockIndent = false;
                array_unshift($indentTypes, 'block');
            }

            // If we need a new line before the token
            if ($newline) {
                $return      .= "\n" . str_repeat($tab, $indentLevel);
                $newline      = false;
                $addedNewline = true;
            } else {
                $addedNewline = false;
            }

            // Display comments directly where they appear in the source
            if ($token->isOfType(Token::TOKEN_TYPE_COMMENT, Token::TOKEN_TYPE_BLOCK_COMMENT)) {
                if ($token->isOfType(Token::TOKEN_TYPE_BLOCK_COMMENT)) {
                    $indent      = str_repeat($tab, $indentLevel);
                    $return     .= "\n" . $indent;
                    $highlighted = str_replace("\n", "\n" . $indent, $highlighted);
                }

                $return .= $highlighted;
                $newline = true;
                continue;
            }

            if ($inlineParentheses) {
                // End of inline parentheses
                if ($token->value() === ')') {
                    $return = rtrim($return, ' ');

                    if ($inlineIndented) {
                        array_shift($indentTypes);
                        $indentLevel--;
                        $return .= "\n" . str_repeat($tab, $indentLevel);
                    }

                    $inlineParentheses = false;

                    $return .= $highlighted . ' ';
                    continue;
                }

                if ($token->value() === ',') {
                    if ($inlineCount >= 30) {
                        $inlineCount = 0;
                        $newline     = true;
                    }
                }

                $inlineCount += strlen($token->value());
            }

            // Opening parentheses increase the block indent level and start a new line
            if ($token->value() === '(') {
                // First check if this should be an inline parentheses block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline parentheses
                $length = 0;
                for ($j=1; $j<=250; $j++) {
                    // Reached end of string
                    if (! isset($indexedTokens[$i+$j])) {
                        break;
                    }

                    $next = $indexedTokens[$i+$j]['token'];

                    // Reached closing parentheses, able to inline it
                    if ($next->value() === ')') {
                        $inlineParentheses = true;
                        $inlineCount       = 0;
                        $inlineIndented    = false;
                        break;
                    }

                    // Reached an invalid token for inline parentheses
                    if ($next->value()===';' || $next->value()==='(') {
                        break;
                    }

                    // Reached an invalid token type for inline parentheses
                    if ($next->isOfType(
                        Token::TOKEN_TYPE_RESERVED_TOPLEVEL,
                        Token::TOKEN_TYPE_RESERVED_NEWLINE,
                        Token::TOKEN_TYPE_COMMENT,
                        Token::TOKEN_TYPE_BLOCK_COMMENT
                    )) {
                        break;
                    }

                    $length += strlen($next->value());
                }

                if ($inlineParentheses && $length > 30) {
                    $increaseBlockIndent = true;
                    $inlineIndented      = true;
                    $newline             = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                if (isset($originalTokens[$indexedToken['originalIndex']-1]) &&
                    ! $originalTokens[$indexedToken['originalIndex']-1]->isOfType(Token::TOKEN_TYPE_WHITESPACE)) {
                    $return = rtrim($return, ' ');
                }

                if (! $inlineParentheses) {
                    $increaseBlockIndent = true;
                    // Add a newline after the parentheses
                    $newline = true;
                }
            } elseif ($token->value() === ')') {
                // Closing parentheses decrease the block indent level
                // Remove whitespace before the closing parentheses
                $return = rtrim($return, ' ');

                $indentLevel--;

                // Reset indent level
                while ($j=array_shift($indentTypes)) {
                    if ($j!=='special') {
                        break;
                    }

                    $indentLevel--;
                }

                if ($indentLevel < 0) {
                    // This is an error
                    $indentLevel = 0;

                    $return .= $this->highlighter->highlightError($token->value());
                    continue;
                }

                // Add a newline before the closing parentheses (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }
            } elseif ($token->isOfType(Token::TOKEN_TYPE_RESERVED_TOPLEVEL)) {
                // Top level reserved words start a new line and increase the special indent level
                $increaseSpecialIndent = true;

                // If the last indent type was 'special', decrease the special indent for this round
                reset($indentTypes);
                if (current($indentTypes)==='special') {
                    $indentLevel--;
                    array_shift($indentTypes);
                }

                // Add a newline after the top level reserved word
                $newline = true;
                // Add a newline before the top level reserved word (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                } else {
                    // If we already added a newline, redo the indentation since it may be different now
                    $return = rtrim($return, $tab) . str_repeat($tab, $indentLevel);
                }

                if ($token->hasExtraWhitespace()) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }

                //if SQL 'LIMIT' clause, start variable to reset newline
                if ($token->value() === 'LIMIT' && ! $inlineParentheses) {
                    $clauseLimit = true;
                }
            } elseif ($clauseLimit &&
                $token->value() !== ',' &&
                ! $token->isOfType(Token::TOKEN_TYPE_NUMBER, Token::TOKEN_TYPE_WHITESPACE)) {
                // Checks if we are out of the limit clause
                $clauseLimit = false;
            } elseif ($token->value() === ',' && ! $inlineParentheses) {
                // Commas start a new line (unless within inline parentheses or SQL 'LIMIT' clause)
                //If the previous TOKEN_VALUE is 'LIMIT', resets new line
                if ($clauseLimit === true) {
                    $newline     = false;
                    $clauseLimit = false;
                } else {
                    // All other cases of commas
                    $newline = true;
                }
            } elseif ($token->isOfType(Token::TOKEN_TYPE_RESERVED_NEWLINE)) {
            // Newline reserved words start a new line
                // Add a newline before the reserved word (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }

                if ($token->hasExtraWhitespace()) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }
            } elseif ($token->isOfType(Token::TOKEN_TYPE_BOUNDARY)) {
                // Multiple boundary characters in a row should not have spaces between them (not including parentheses)
                if (isset($indexedTokens[$i-1]) &&
                    $indexedTokens[$i-1]['token']->isOfType(Token::TOKEN_TYPE_BOUNDARY)) {
                    if (isset($originalTokens[$indexedToken['originalIndex']-1]) &&
                        ! $originalTokens[$indexedToken['originalIndex']-1]->isOfType(Token::TOKEN_TYPE_WHITESPACE)) {
                        $return = rtrim($return, ' ');
                    }
                }
            }

            // If the token shouldn't have a space before it
            if ($token->value() === '.' ||
                $token->value() === ',' ||
                $token->value() === ';') {
                $return = rtrim($return, ' ');
            }

            $return .= $highlighted . ' ';

            // If the token shouldn't have a space after it
            if ($token->value() === '(' || $token->value() === '.') {
                $return = rtrim($return, ' ');
            }

            // If this is the "-" of a negative number, it shouldn't have a space after it
            if ($token->value() !== '-' ||
                ! isset($indexedTokens[$i+1]) ||
                ! $indexedTokens[$i+1]['token']->isOfType(Token::TOKEN_TYPE_NUMBER) ||
                ! isset($indexedTokens[$i-1])) {
                continue;
            }

            $prev = $indexedTokens[$i-1]['token'];
            if ($prev->isOfType(
                Token::TOKEN_TYPE_QUOTE,
                Token::TOKEN_TYPE_BACKTICK_QUOTE,
                Token::TOKEN_TYPE_WORD,
                Token::TOKEN_TYPE_NUMBER
            )) {
                continue;
            }

            $return = rtrim($return, ' ');
        }

        // If there are unmatched parentheses
        if (array_search('block', $indentTypes) !== false) {
            $return .= $this->highlighter->highlightError('WARNING: unclosed parentheses or section');
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t", $this->tab, $return));

        return $this->highlighter->output($return);
    }

    /**
     * Add syntax highlighting to a SQL string
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string with HTML styles applied
     */
    public function highlight(string $string) : string
    {
        $tokens = $this->tokenizer->tokenize($string);

        $return = '';

        foreach ($tokens as $token) {
            $return .= $this->highlighter->highlightToken(
                $token->type(),
                $token->value()
            );
        }

        return $this->highlighter->output($return);
    }

    /**
     * Compress a query by collapsing white space and removing comments
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string without comments
     */
    public function compress(string $string) : string
    {
        $result = '';

        $tokens = $this->tokenizer->tokenize($string);

        $whitespace = true;
        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token->isOfType(Token::TOKEN_TYPE_COMMENT, Token::TOKEN_TYPE_BLOCK_COMMENT)) {
                continue;
            }

            // Remove extra whitespace in reserved words (e.g "OUTER     JOIN" becomes "OUTER JOIN")

            if ($token->isOfType(
                Token::TOKEN_TYPE_RESERVED,
                Token::TOKEN_TYPE_RESERVED_NEWLINE,
                Token::TOKEN_TYPE_RESERVED_TOPLEVEL
            )) {
                $newValue = preg_replace('/\s+/', ' ', $token->value());
                assert($newValue !== null);
                $token = $token->withValue($newValue);
            }

            if ($token->isOfType(Token::TOKEN_TYPE_WHITESPACE)) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                }

                $whitespace = true;
                // Convert all whitespace to a single space
                $token = $token->withValue(' ');
            } else {
                $whitespace = false;
            }

            $result .= $token->value();
        }

        return rtrim($result);
    }
}
