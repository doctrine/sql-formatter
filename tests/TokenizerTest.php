<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\Tokenizer;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function sort;

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

    #[DoesNotPerformAssertions]
    public function testThereAreNoRegressions(): void
    {
        (new Tokenizer())->tokenize('*/');
    }
}
