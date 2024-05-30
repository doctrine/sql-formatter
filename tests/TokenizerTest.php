<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\Tokenizer;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_filter;
use function preg_match;
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

    #[DoesNotPerformAssertions]
    public function testThereAreNoRegressions(): void
    {
        (new Tokenizer())->tokenize('*/');
    }
}
