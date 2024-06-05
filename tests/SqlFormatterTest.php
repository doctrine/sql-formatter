<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\CliHighlighter;
use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function assert;
use function count;
use function defined;
use function explode;
use function file_get_contents;
use function implode;
use function pack;
use function rtrim;
use function sprintf;

final class SqlFormatterTest extends TestCase
{
    private SqlFormatter $formatter;

    protected function setUp(): void
    {
        // Force SqlFormatter to run in non-CLI mode for tests
        $highlighter = new HtmlHighlighter();

        $this->formatter = new SqlFormatter($highlighter);
    }

    #[DataProvider('formatHighlightData')]
    public function testFormatHighlight(string $sql, string $html): void
    {
        $this->assertSame($html, $this->formatter->format($sql));
    }

    #[DataProvider('formatData')]
    public function testFormat(string $sql, string $html): void
    {
        $formatter = new SqlFormatter(new NullHighlighter());
        $this->assertSame($html, $formatter->format($sql));
    }

    #[DataProvider('highlightData')]
    public function testHighlight(string $sql, string $html): void
    {
        $this->assertSame($html, $this->formatter->highlight($sql));
    }

    public function testHighlightBinary(): void
    {
        $sql = 'SELECT "' . pack('H*', 'ed180e98a47a45b3bdd304b798bc5797') . '" AS BINARY';

        if (defined('ENT_IGNORE')) {
            // this is what gets written as string
            $binaryData = '&quot;' . pack('H*', '180e7a450457') . '&quot;';
        } else {
            $binaryData = '';
        }

        $html = '<pre style="color: black; background-color: white;">' .
            '<span style="font-weight:bold;">SELECT</span> <span style="color: blue;">' .
            $binaryData .
            '</span> <span style="font-weight:bold;">AS</span> <span style="font-weight:bold;">BINARY</span></pre>';

        $this->assertSame($html, $this->formatter->highlight($sql));
    }

    #[DataProvider('highlightCliData')]
    public function testCliHighlight(string $sql, string $html): void
    {
        $formatter = new SqlFormatter(new CliHighlighter());
        $this->assertSame($html . "\n", $formatter->format($sql));
    }

    #[DataProvider('compressData')]
    public function testCompress(string $sql, string $html): void
    {
        $this->assertSame($html, $this->formatter->compress($sql));
    }

    public function testUsePre(): void
    {
        $formatter = new SqlFormatter(new HtmlHighlighter([], false));
        $actual    = $formatter->highlight('test');
        $expected  = '<span style="color: #333;">test</span>';
        $this->assertSame($actual, $expected);

        $formatter = new SqlFormatter(new HtmlHighlighter([], true));
        $actual    = $formatter->highlight('test');
        $expected  = '<pre style="color: black; background-color: white;">' .
            '<span style="color: #333;">test</span></pre>';
        $this->assertSame($actual, $expected);
    }

    /** @return string[] */
    private static function fileSqlData(): array
    {
        $contents = file_get_contents(__DIR__ . '/sql.sql');
        assert($contents !== false);

        return explode("\n---\n", rtrim($contents, "\n"));
    }

    /** @return Generator<mixed[]> */
    private static function fileDataProvider(string $file): Generator
    {
        $contents = file_get_contents(__DIR__ . '/' . $file);
        assert($contents !== false);
        $formatHighlightData = explode("\n---\n", rtrim($contents, "\n"));
        $sqlData             = self::fileSqlData();
        if (count($formatHighlightData) !== count($sqlData)) {
            throw new UnexpectedValueException(sprintf(
                '"%s" (%d sections) and sql.sql (%d sections) should have the same number of sections',
                $file,
                count($formatHighlightData),
                count($sqlData),
            ));
        }

        foreach ($formatHighlightData as $i => $data) {
            yield [$sqlData[$i], $data];
        }
    }

    /** @return Generator<mixed[]> */
    public static function formatHighlightData(): Generator
    {
        return self::fileDataProvider('format-highlight.html');
    }

    /** @return Generator<mixed[]> */
    public static function highlightCliData(): Generator
    {
        return self::fileDataProvider('clihighlight.txt');
    }

    /** @return Generator<mixed[]> */
    public static function formatData(): Generator
    {
        return self::fileDataProvider('format.txt');
    }

    public function testFormatLongConcat(): void
    {
        $sqlParts = [];
        for ($i = 0; $i < 20_000; $i++) {
            $sqlParts[] = 'cast(\'foo' . $i . '\' as blob)';
        }

        $inConcat  = 'concat(' . implode(', ', $sqlParts) . ')';
        $outConcat = "concat(\n      " . implode(",\n      ", $sqlParts) . "\n    )";

        $this->testFormat(
            'select iif(' . $inConcat . ' = ' . $inConcat . ', 10, 20) x',
            "select\n  iif(\n    " . $outConcat . ' = ' . $outConcat . ",\n    10,\n    20\n  ) x",
        );
    }

    /** @return Generator<mixed[]> */
    public static function compressData(): Generator
    {
        return self::fileDataProvider('compress.txt');
    }

    /** @return Generator<mixed[]> */
    public static function highlightData(): Generator
    {
        return self::fileDataProvider('highlight.html');
    }
}
