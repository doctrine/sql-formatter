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
use function current;
use function preg_replace;
use function reset;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function trim;
use const PHP_SAPI;

final class SqlFormatter
{
    /**
     * Whether or not the current environment is the CLI
     * This affects the type of syntax highlighting
     * If not defined, it will be determined automatically
     *
     * @var bool
     */
    public $cli;

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
        $this->highlighter = $highlighter ?? ($this->isCli() ? new CliHighlighter() : new HtmlHighlighter());
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param string $string    The SQL string
     * @param bool   $highlight If true, syntax highlighting will also be performed
     *
     * @return string The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public function format(string $string, bool $highlight = true) : string
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
        $tokens = [];
        foreach ($originalTokens as $i => $token) {
            if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_WHITESPACE) {
                continue;
            }

            $token['i'] = $i;
            $tokens[]   = $token;
        }

        // Format token by token
        foreach ($tokens as $i => $token) {
            // Get highlighted token if doing syntax highlighting
            if ($highlight) {
                $highlighted = $this->highlighter->highlightToken(
                    $token[Token::TOKEN_TYPE],
                    $token[Token::TOKEN_VALUE]
                );
            } else { // If returning raw text
                $highlighted = $token[Token::TOKEN_VALUE];
            }

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
            if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_COMMENT ||
                $token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_BLOCK_COMMENT) {
                if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_BLOCK_COMMENT) {
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
                if ($token[Token::TOKEN_VALUE] === ')') {
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

                if ($token[Token::TOKEN_VALUE] === ',') {
                    if ($inlineCount >= 30) {
                        $inlineCount = 0;
                        $newline     = true;
                    }
                }

                $inlineCount += strlen($token[Token::TOKEN_VALUE]);
            }

            // Opening parentheses increase the block indent level and start a new line
            if ($token[Token::TOKEN_VALUE] === '(') {
                // First check if this should be an inline parentheses block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline parentheses
                $length = 0;
                for ($j=1; $j<=250; $j++) {
                    // Reached end of string
                    if (! isset($tokens[$i+$j])) {
                        break;
                    }

                    $next = $tokens[$i+$j];

                    // Reached closing parentheses, able to inline it
                    if ($next[Token::TOKEN_VALUE] === ')') {
                        $inlineParentheses = true;
                        $inlineCount       = 0;
                        $inlineIndented    = false;
                        break;
                    }

                    // Reached an invalid token for inline parentheses
                    if ($next[Token::TOKEN_VALUE]===';' || $next[Token::TOKEN_VALUE]==='(') {
                        break;
                    }

                    // Reached an invalid token type for inline parentheses
                    if ($next[Token::TOKEN_TYPE]===Token::TOKEN_TYPE_RESERVED_TOPLEVEL ||
                        $next[Token::TOKEN_TYPE]===Token::TOKEN_TYPE_RESERVED_NEWLINE ||
                        $next[Token::TOKEN_TYPE]===Token::TOKEN_TYPE_COMMENT ||
                        $next[Token::TOKEN_TYPE]===Token::TOKEN_TYPE_BLOCK_COMMENT) {
                        break;
                    }

                    $length += strlen($next[Token::TOKEN_VALUE]);
                }

                if ($inlineParentheses && $length > 30) {
                    $increaseBlockIndent = true;
                    $inlineIndented      = true;
                    $newline             = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                if (isset($originalTokens[$token['i']-1]) &&
                    $originalTokens[$token['i']-1][Token::TOKEN_TYPE] !== Token::TOKEN_TYPE_WHITESPACE) {
                    $return = rtrim($return, ' ');
                }

                if (! $inlineParentheses) {
                    $increaseBlockIndent = true;
                    // Add a newline after the parentheses
                    $newline = true;
                }
            } elseif ($token[Token::TOKEN_VALUE] === ')') {
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

                    if ($highlight) {
                        $return .= "\n" . $this->highlighter->highlightError(
                            $token[Token::TOKEN_VALUE]
                        );
                        continue;
                    }
                }

                // Add a newline before the closing parentheses (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }
            } elseif ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_RESERVED_TOPLEVEL) {
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

                // If the token may have extra whitespace
                if (strpos($token[Token::TOKEN_VALUE], ' ')!==false ||
                    strpos($token[Token::TOKEN_VALUE], "\n")!==false ||
                    strpos($token[Token::TOKEN_VALUE], "\t")!==false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }

                //if SQL 'LIMIT' clause, start variable to reset newline
                if ($token[Token::TOKEN_VALUE] === 'LIMIT' && ! $inlineParentheses) {
                    $clauseLimit = true;
                }
            } elseif ($clauseLimit &&
                $token[Token::TOKEN_VALUE] !== ',' &&
                $token[Token::TOKEN_TYPE] !== Token::TOKEN_TYPE_NUMBER &&
                $token[Token::TOKEN_TYPE] !== Token::TOKEN_TYPE_WHITESPACE) {
                // Checks if we are out of the limit clause
                $clauseLimit = false;
            } elseif ($token[Token::TOKEN_VALUE] === ',' && ! $inlineParentheses) {
                // Commas start a new line (unless within inline parentheses or SQL 'LIMIT' clause)
                //If the previous TOKEN_VALUE is 'LIMIT', resets new line
                if ($clauseLimit === true) {
                    $newline     = false;
                    $clauseLimit = false;
                } else {
                    // All other cases of commas
                    $newline = true;
                }
            } elseif ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_RESERVED_NEWLINE) {
            // Newline reserved words start a new line
                // Add a newline before the reserved word (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }

                // If the token may have extra whitespace
                if (strpos($token[Token::TOKEN_VALUE], ' ')!==false ||
                    strpos($token[Token::TOKEN_VALUE], "\n")!==false ||
                    strpos($token[Token::TOKEN_VALUE], "\t")!==false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }
            } elseif ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_BOUNDARY) {
                // Multiple boundary characters in a row should not have spaces between them (not including parentheses)
                if (isset($tokens[$i-1]) && $tokens[$i-1][Token::TOKEN_TYPE] === Token::TOKEN_TYPE_BOUNDARY) {
                    if (isset($originalTokens[$token['i']-1]) &&
                        $originalTokens[$token['i']-1][Token::TOKEN_TYPE] !== Token::TOKEN_TYPE_WHITESPACE) {
                        $return = rtrim($return, ' ');
                    }
                }
            }

            // If the token shouldn't have a space before it
            if ($token[Token::TOKEN_VALUE] === '.' ||
                $token[Token::TOKEN_VALUE] === ',' ||
                $token[Token::TOKEN_VALUE] === ';') {
                $return = rtrim($return, ' ');
            }

            $return .= $highlighted . ' ';

            // If the token shouldn't have a space after it
            if ($token[Token::TOKEN_VALUE] === '(' || $token[Token::TOKEN_VALUE] === '.') {
                $return = rtrim($return, ' ');
            }

            // If this is the "-" of a negative number, it shouldn't have a space after it
            if ($token[Token::TOKEN_VALUE] !== '-' ||
                ! isset($tokens[$i+1]) ||
                $tokens[$i+1][Token::TOKEN_TYPE] !== Token::TOKEN_TYPE_NUMBER ||
                ! isset($tokens[$i-1])) {
                continue;
            }

            $prev = $tokens[$i-1][Token::TOKEN_TYPE];
            if ($prev === Token::TOKEN_TYPE_QUOTE ||
                $prev === Token::TOKEN_TYPE_BACKTICK_QUOTE ||
                $prev === Token::TOKEN_TYPE_WORD ||
                $prev === Token::TOKEN_TYPE_NUMBER) {
                continue;
            }

            $return = rtrim($return, ' ');
        }

        // If there are unmatched parentheses
        if ($highlight && array_search('block', $indentTypes) !== false) {
            $return .= "\n" . $this->highlighter->highlightError('WARNING: unclosed parentheses or section');
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t", $this->tab, $return));

        if ($highlight) {
            $return = $this->highlighter->output($return);
        }

        return $return;
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
                $token[Token::TOKEN_TYPE],
                $token[Token::TOKEN_VALUE]
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
            if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_COMMENT ||
                $token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }

            // Remove extra whitespace in reserved words (e.g "OUTER     JOIN" becomes "OUTER JOIN")

            if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_RESERVED ||
                $token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_RESERVED_NEWLINE ||
                $token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $token[Token::TOKEN_VALUE] = preg_replace('/\s+/', ' ', $token[Token::TOKEN_VALUE]);
            }

            if ($token[Token::TOKEN_TYPE] === Token::TOKEN_TYPE_WHITESPACE) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                }

                $whitespace = true;
                // Convert all whitespace to a single space
                $token[Token::TOKEN_VALUE] = ' ';
            } else {
                $whitespace = false;
            }

            $result .= $token[Token::TOKEN_VALUE];
        }

        return rtrim($result);
    }

    private function isCli() : bool
    {
        if (isset($this->cli)) {
            return $this->cli;
        }

        return PHP_SAPI === 'cli';
    }
}
