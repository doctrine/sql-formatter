<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\Cursor;
use Doctrine\SqlFormatter\Token;
use Doctrine\SqlFormatter\Tokenizer;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_filter;
use function implode;
use function preg_match;
use function serialize;
use function sort;
use function strtoupper;

final class TokenizerTest extends TestCase
{
    /**
     * @param 'reserved'|'reservedToplevel'|'reservedNewline'|'functions' $propertyName
     *
     * @return list<string>
     */
    private function getTokenizerList(string $propertyName): array
    {
        $tokenizerReflClass = new ReflectionClass(Tokenizer::class);
        /** @var list<string> $res */
        $res = $tokenizerReflClass->getProperty($propertyName)->getDefaultValue();

        return $res;
    }

    public function testInternalKeywordListsAreSortedForEasierMaintenance(): void
    {
        foreach (
            [
                $this->getTokenizerList('reserved'),
                $this->getTokenizerList('reservedToplevel'),
                $this->getTokenizerList('reservedNewline'),
                $this->getTokenizerList('functions'),
            ] as $list
        ) {
            $listSorted = $list;
            sort($listSorted);

            self::assertSame($listSorted, $list);
        }
    }

    public function testKeywordsReservedAreSingleUpperWord(): void
    {
        $tokenizerReserved = $this->getTokenizerList('reserved');

        $kwsDiff = array_filter($tokenizerReserved, static function ($v) {
            return $v !== strtoupper($v) || preg_match('~^\w+$~', $v) !== 1;
        });

        self::assertSame([], $kwsDiff);
    }

    /** @param list<Token> $expectedTokens */
    public static function assertEqualsTokens(array $expectedTokens, Cursor $cursor): void
    {
        $tokens = [];

        $cursor = $cursor->subCursor();

        while ($token = $cursor->next()) {
            $tokens[] = $token;
        }

        if (serialize($tokens) === serialize($expectedTokens)) { // optimize self::assertEquals() for large inputs
            self::assertTrue(true);
        } else {
            self::assertEquals($expectedTokens, $tokens);
        }
    }

    /** @param list<Token> $expectedTokens */
    #[DataProvider('tokenizeData')]
    #[DataProvider('tokenizeLongConcatData')]
    public function testTokenize(array $expectedTokens, string $sql): void
    {
        self::assertEqualsTokens($expectedTokens, (new Tokenizer())->tokenize($sql));
    }

    /** @return Generator<mixed[]> */
    public static function tokenizeData(): Generator
    {
        yield 'empty' => [
            [],
            '',
        ];

        yield 'basic' => [
            [
                new Token(Token::TOKEN_TYPE_RESERVED_TOPLEVEL, 'select'),
                new Token(Token::TOKEN_TYPE_WHITESPACE, ' '),
                new Token(Token::TOKEN_TYPE_NUMBER, '1'),
            ],
            'select 1',
        ];

        yield 'there are no regressions' => [
            [
                new Token(Token::TOKEN_TYPE_BOUNDARY, '*'),
                new Token(Token::TOKEN_TYPE_BOUNDARY, '/'),
            ],
            '*/',
        ];

        yield 'unclosed quoted string' => [
            [
                new Token(Token::TOKEN_TYPE_QUOTE, '\'foo...'),
            ],
            '\'foo...',
        ];

        yield 'unclosed block comment' => [
            [
                new Token(Token::TOKEN_TYPE_BLOCK_COMMENT, '/* foo...'),
            ],
            '/* foo...',
        ];
    }

    /** @return Generator<mixed[]> */
    public static function tokenizeLongConcatData(): Generator
    {
        $count = 2_000;

        $sqlParts = [];
        for ($i = 0; $i < $count; $i++) {
            $sqlParts[] = 'cast(\'foo' . $i . '\' as blob)';
        }

        $concat = 'concat(' . implode(', ', $sqlParts) . ')';
        $sql    = 'select iif(' . $concat . ' = ' . $concat . ', 10, 20) x';

        $expectedTokens = [
            new Token(Token::TOKEN_TYPE_RESERVED_TOPLEVEL, 'select'),
            new Token(Token::TOKEN_TYPE_WHITESPACE, ' '),
            new Token(Token::TOKEN_TYPE_WORD, 'iif'),
            new Token(Token::TOKEN_TYPE_BOUNDARY, '('),
        ];

        for ($j = 0; $j < 2; $j++) {
            if ($j !== 0) {
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, '=');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
            }

            $expectedTokens[] = new Token(Token::TOKEN_TYPE_RESERVED, 'concat');
            $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, '(');

            for ($i = 0; $i < $count; $i++) {
                if ($i !== 0) {
                    $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ',');
                    $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
                }

                $expectedTokens[] = new Token(Token::TOKEN_TYPE_RESERVED, 'cast');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, '(');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_QUOTE, '\'foo' . $i . '\'');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_RESERVED, 'as');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_WORD, 'blob');
                $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ')');
            }

            $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ')');
        }

        $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ',');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_NUMBER, '10');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ',');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_NUMBER, '20');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_BOUNDARY, ')');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_WHITESPACE, ' ');
        $expectedTokens[] = new Token(Token::TOKEN_TYPE_WORD, 'x');

        yield 'long concat' => [$expectedTokens, $sql];
    }
}
