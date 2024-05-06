<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') :?>
    <p>
        Run this php script from the command line to see CLI syntax highlighting and
        formatting.  It support Unix pipes or command line argument style.
    </p>
    <pre><code>php examples/cli.php \
"SELECT * FROM MyTable WHERE (id>5 AND \`name\` LIKE \&quot;testing\&quot;);"</code></pre>
    <pre><code>echo "SELECT * FROM MyTable WHERE (id>5 AND \`name\` LIKE \&quot;testing\&quot;);"\
 | php examples/cli.php</code></pre>
    <?php
    exit;
endif;

if (isset($argv[1])) {
    $sql = $argv[1];
} else {
    $fp = fopen('php://stdin', 'r');
    assert($fp !== false);
    $sql = stream_get_contents($fp);
}

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\SqlFormatter\SqlFormatter;
use Doctrine\SqlFormatter\Tokenizer;

assert($sql !== false);

echo (new SqlFormatter())->format($sql);
