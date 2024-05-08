<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\Tokenizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tokenizer::class)]
final class TokenizerTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testThereAreNoRegressions(): void
    {
        (new Tokenizer())->tokenize('*/');
    }
}
