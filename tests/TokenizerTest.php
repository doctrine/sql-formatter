<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\Tokenizer;
use PHPUnit\Framework\TestCase;

final class TokenizerTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testThereAreNoRegressions(): void
    {
        (new Tokenizer())->tokenize('*/');
    }
}
