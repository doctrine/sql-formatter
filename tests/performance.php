<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\SqlFormatter\SqlFormatter;
use Doctrine\SqlFormatter\Tokenizer;

$tokenizer = new Tokenizer($maxCachekeySize = 15);
//15 is the default value
//set to '0' to disable caching
//a value between 10 and 20 seems to give the best result
$formatter = new SqlFormatter($tokenizer);

//the sample query file is filled with install scripts for PrestaShop
//and some sample catalog data from Magento
$contents = file_get_contents(__DIR__ . '/sql.sql');

assert($contents !== false);
//queries are separated by 2 new lines
$queries = explode("\n\n", $contents);

//track time and memory usage
$start  = microtime(true);
$ustart = memory_get_usage(true);

//track number of queries and size of queries
$num   = 0;
$chars = 0;

foreach ($queries as $query) {
    //do formatting and highlighting
    $formatter->format($query);

    $num++;
    $chars += mb_strlen($query);
}

$uend = memory_get_usage(true);
$end  = microtime(true);
?>

    <p>Formatted <?= $num ?> queries using a maxCachekeySize of <?= $maxCachekeySize ?></p>
    <p>Average query length of <?= number_format($chars/$num, 5) ?> characters</p>
    <p>
        Took <?= number_format($end-$start, 5) ?> seconds total,
        <?= number_format(($end-$start)/$num, 5) ?> seconds per query,
        <?= number_format(1000*($end-$start)/$chars, 5) ?> seconds per 1000 characters
    </p>
    <p>Used <?= number_format($uend-$ustart) ?> bytes of memory</p>

    <h3>Cache Stats</h3><pre><?= print_r($tokenizer->getCacheStats(), true) ?></pre>
