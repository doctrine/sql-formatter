<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

/**
 * Conditions that end a block.
 */
final class Condition
{
    /** @var int[] */
    public $types = [];

    /** @var string[] */
    public $values = [];

    /** @var bool */
    public $eof = false;

    /** @var bool */
    public $addNewline = false;
}
