<?php

declare(strict_types=1);

/**
 * SQL Formatter is a collection of utilities for debugging SQL queries.
 * It includes methods for formatting, syntax highlighting, removing comments, etc.
 *
 * @link       http://github.com/jdorn/sql-formatter
 */

namespace Doctrine\SqlFormatter;

use function array_pop;
use function count;
use function end;
use function in_array;
use function prev;
use function rtrim;
use function str_contains;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;

use const PHP_SAPI;

final class SqlFormatter
{
    private const INDENT_BLOCK   = 1;
    private const INDENT_SPECIAL = 2;

    /** @var Highlighter */
    private $highlighter;

    /** @var Tokenizer */
    private $tokenizer;

    public function __construct(?Highlighter $highlighter = null)
    {
        $this->tokenizer   = new Tokenizer();
        $this->highlighter = $highlighter ?? (PHP_SAPI === 'cli' ? new CliHighlighter() : new HtmlHighlighter());
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public function format(string $string, string $indentString = '  '): string
    {
        // This variable will be populated with formatted html
        $return = '';

        // Use an actual tab while formatting and then switch out with $indentString at the end
        $tab = "\t";

        /** @var Condition[] $expectedBlockEnds */
        $expectedBlockEnds = [];

        $indentLevel           = 0;
        $newline               = false;
        $inlineBlock           = false;
        $increaseSpecialIndent = false;
        $increaseBlockIndent   = false;
        $indentTypes           = [];
        $inlineCount           = 0;
        $inlineIndented        = false;
        $clauseLimit           = false;

        // Tokenize String
        $cursor = $this->tokenizer->tokenize($string);

        // Format token by token
        while ($token = $cursor->next(Token::TOKEN_TYPE_WHITESPACE)) {
            $highlighted = $this->highlighter->highlightToken(
                $token->type(),
                $token->value()
            );

            // If we are increasing the special indent level now
            if ($increaseSpecialIndent) {
                $indentLevel++;
                $increaseSpecialIndent = false;
                $indentTypes[]         = self::INDENT_SPECIAL;
            }

            // If we are increasing the block indent level now
            if ($increaseBlockIndent) {
                $indentLevel++;
                $increaseBlockIndent = false;
            }

            // If we need a new line before the token
            $addedNewline = false;
            if ($newline) {
                $return      .= "\n" . str_repeat($tab, $indentLevel);
                $newline      = false;
                $addedNewline = true;
            }

            // Display comments directly where they appear in the source
            if ($token->isOfType(Token::TOKEN_TYPE_COMMENT, Token::TOKEN_TYPE_BLOCK_COMMENT)) {
                // Always add newline after
                $newline        = true;
                $isBlockComment = $token->isOfType(Token::TOKEN_TYPE_BLOCK_COMMENT);
                $indent         = str_repeat($tab, $indentLevel);

                if ($isBlockComment) {
                    // Remove trailing indent from previous $newline
                    $return = rtrim($return, $tab) . "\n" . $indent;
                } else {
                    $prev = $cursor->subCursor()->previous();
                    // Single line comment wants to have a newline before
                    if ($prev && str_contains($prev->value(), "\n") && ! $addedNewline) {
                        $return .= "\n" . $indent;
                    } elseif (! $addedNewline) {
                        $return .= ' ';
                    }
                }

                if ($isBlockComment) {
                    $return .= str_replace("\n", "\n" . $indent, $highlighted);
                    continue;
                }

                $return .= $highlighted;
                continue;
            }

            // Allow another block to finish an EOF type block
            if (count($expectedBlockEnds) > 1) {
                $last = end($expectedBlockEnds);
                $prev = prev($expectedBlockEnds);

                if ($prev && $last->eof && ! $prev->eof && $token->isBlockEnd($prev)) {
                    array_pop($expectedBlockEnds);
                    array_pop($indentTypes);
                    $indentLevel--;
                }
            }

            $blockEndCondition = end($expectedBlockEnds);

            if ($inlineBlock) {
                // End of inline block
                if ($blockEndCondition && $token->isBlockEnd($blockEndCondition)) {
                    array_pop($expectedBlockEnds);

                    if ($inlineIndented) {
                        array_pop($indentTypes);
                        $indentLevel--;
                        $return .= "\n" . str_repeat($tab, $indentLevel);
                    }

                    $inlineBlock = false;

                    $return .= $highlighted;

                    $nextNotWhitespace = $cursor->subCursor()->next(Token::TOKEN_TYPE_WHITESPACE);
                    if ($nextNotWhitespace && $nextNotWhitespace->wantsSpaceBefore()) {
                        $return .= ' ';
                    }

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

            $newBlockEndCondition = $token->isBlockStart();

            // Start of new block, increase the indent level and start a new line
            if ($newBlockEndCondition !== null) {
                // First check if this should be an inline block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline block
                $length    = 0;
                $subCursor = $cursor->subCursor();
                for ($j = 1; $j <= 250; $j++) {
                    // Reached end of string
                    $next = $subCursor->next(Token::TOKEN_TYPE_WHITESPACE);
                    if (! $next) {
                        break;
                    }

                    // Reached an invalid token for inline block
                    if ($next->value() === ';' || $next->value() === '(') {
                        break;
                    }

                    // Reached an invalid token type for inline block
                    if (
                        $next->isOfType(
                            Token::TOKEN_TYPE_RESERVED_TOPLEVEL,
                            Token::TOKEN_TYPE_RESERVED_NEWLINE,
                            Token::TOKEN_TYPE_COMMENT,
                            Token::TOKEN_TYPE_BLOCK_COMMENT
                        )
                    ) {
                        break;
                    }

                    // Reached closing condition, able to inline it
                    if ($next->isBlockEnd($newBlockEndCondition)) {
                        $inlineBlock    = true;
                        $inlineCount    = 0;
                        $inlineIndented = false;
                        break;
                    }

                    $length += strlen($next->value());
                }

                if ($inlineBlock && $length > 30) {
                    $increaseBlockIndent = true;
                    $inlineIndented      = true;
                    $newline             = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                $prevToken = $cursor->subCursor()->previous();
                if ($prevToken && ! $prevToken->isOfType(Token::TOKEN_TYPE_WHITESPACE)) {
                    $return = rtrim($return, ' ');
                }

                if (! $inlineBlock) {
                    $increaseBlockIndent = true;
                    // Add a newline after the block
                    if ($newBlockEndCondition->addNewline) {
                        $newline = true;
                    }
                }

                if ($increaseBlockIndent) {
                    $indentTypes[] = self::INDENT_BLOCK;
                }

                $expectedBlockEnds[] = $newBlockEndCondition;
            }

            if ($blockEndCondition && $token->isBlockEnd($blockEndCondition)) {
                // Closing block decrease the block indent level
                array_pop($expectedBlockEnds);
                $indentLevel--;

                // Reset indent level
                while ($lastIndentType = array_pop($indentTypes)) {
                    if ($lastIndentType !== self::INDENT_SPECIAL) {
                        break;
                    }

                    $indentLevel--;
                }

                // Add a newline before the closing block (if not already added)
                if (! $addedNewline && $blockEndCondition->addNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }
            }

            if ($token->isOfType(Token::TOKEN_TYPE_RESERVED_TOPLEVEL)) {
                // VALUES() is also a function
                if ($token->value() === 'VALUES') {
                    $prevNotWhitespace = $cursor->subCursor()->previous(Token::TOKEN_TYPE_WHITESPACE);
                    if ($prevNotWhitespace && $prevNotWhitespace->value() === '=') {
                        $return .= ' ' . $highlighted;
                        continue;
                    }
                }

                // Top level reserved words start a new line and increase the special indent level
                $increaseSpecialIndent = true;

                // If the last indent type was 'special', decrease the special indent for this round
                if (end($indentTypes) === self::INDENT_SPECIAL) {
                    $indentLevel--;
                    array_pop($indentTypes);
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

                // if SQL 'LIMIT' clause, start variable to reset newline
                if ($token->value() === 'LIMIT' && ! $inlineBlock) {
                    $clauseLimit = true;
                }
            } elseif (
                $clauseLimit &&
                $token->value() !== ',' &&
                ! $token->isOfType(Token::TOKEN_TYPE_NUMBER, Token::TOKEN_TYPE_WHITESPACE)
            ) {
                // Checks if we are out of the limit clause
                $clauseLimit = false;
            } elseif ($token->value() === ',' && ! $inlineBlock) {
                // Commas start a new line (unless within inline block or SQL 'LIMIT' clause)
                // If the previous TOKEN_VALUE is 'LIMIT', resets new line
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
            }

            $return .= $highlighted;

            // If the token shouldn't have a space after it
            if ($newline || $token->value() === '(' || $token->value() === '.') {
                continue;
            }

            $nextNotWhitespace = $cursor->subCursor()->next(Token::TOKEN_TYPE_WHITESPACE);
            if (! $nextNotWhitespace) {
                continue;
            }

            if (! $nextNotWhitespace->wantsSpaceBefore()) {
                continue;
            }

            // If this is the "-" of a negative number, it shouldn't have a space after it
            if ($token->value() === '-') {
                $prevNotWhitespace = $cursor->subCursor()->previous(Token::TOKEN_TYPE_WHITESPACE);
                if (! $prevNotWhitespace) {
                    continue;
                }

                if (
                    $nextNotWhitespace->isOfType(Token::TOKEN_TYPE_NUMBER)
                    && ! $prevNotWhitespace->isOfType(
                        Token::TOKEN_TYPE_QUOTE,
                        Token::TOKEN_TYPE_BACKTICK_QUOTE,
                        Token::TOKEN_TYPE_WORD,
                        Token::TOKEN_TYPE_NUMBER
                    )
                ) {
                    continue;
                }
            }

            // Don't add whitespace between operators like != <> >= := && etc.
            if (
                $token->isOfType(Token::TOKEN_TYPE_BOUNDARY)
                && $token->value() !== ')'
                && $nextNotWhitespace->isOfType(Token::TOKEN_TYPE_BOUNDARY)
                && ! in_array($nextNotWhitespace->value(), ['(', '-'], true)
            ) {
                continue;
            }

            $return .= ' ';
        }

        $blockEndCondition = end($expectedBlockEnds);
        if ($blockEndCondition && $blockEndCondition->eof) {
            array_pop($expectedBlockEnds);
        }

        // If there are unmatched blocks
        if (count($expectedBlockEnds)) {
            $return .= $this->highlighter->highlightErrorMessage(
                'WARNING: unclosed block'
            );
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t", $indentString, $return));

        return $this->highlighter->output($return);
    }

    /**
     * Add syntax highlighting to a SQL string
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string with HTML styles applied
     */
    public function highlight(string $string): string
    {
        $cursor = $this->tokenizer->tokenize($string);

        $return = '';

        while ($token = $cursor->next()) {
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
    public function compress(string $string): string
    {
        $result = '';
        $cursor = $this->tokenizer->tokenize($string);

        $whitespace = true;
        while ($token = $cursor->next()) {
            // Skip comment tokens
            if ($token->isOfType(Token::TOKEN_TYPE_COMMENT, Token::TOKEN_TYPE_BLOCK_COMMENT)) {
                continue;
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
