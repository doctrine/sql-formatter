<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\CliHighlighter;
use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Generator;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function assert;
use function count;
use function defined;
use function explode;
use function file_get_contents;
use function pack;
use function sprintf;
use function trim;

/**
 * @covers \Doctrine\SqlFormatter\SqlFormatter
 */
final class SqlFormatterTest extends TestCase
{
    /** @var string[] */
    private $sqlData;

    /** @var SqlFormatter */
    private $formatter;

    /** @var HtmlHighlighter */
    private $highlighter;

    protected function setUp(): void
    {
        // Force SqlFormatter to run in non-CLI mode for tests
        $this->highlighter = new HtmlHighlighter();

        $this->formatter = new SqlFormatter($this->highlighter);
    }

    /**
     * @dataProvider formatHighlightData
     */
    public function testFormatHighlight(string $sql, string $html): void
    {
        $this->assertEquals(trim($html), trim($this->formatter->format($sql)));
    }

    /**
     * @dataProvider formatData
     */
    public function testFormat(string $sql, string $html): void
    {
        $formatter = new SqlFormatter(new NullHighlighter());
        $this->assertEquals(trim($html), trim($formatter->format($sql)));
    }

    /**
     * @dataProvider highlightData
     */
    public function testHighlight(string $sql, string $html): void
    {
        $this->assertEquals(trim($html), trim($this->formatter->highlight($sql)));
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
            '</span> <span style="font-weight:bold;">AS</span> <span style="color: #333;">BINARY</span></pre>';

        $this->assertEquals(trim($html), trim($this->formatter->highlight($sql)));
    }

    /**
     * @dataProvider highlightCliData
     */
    public function testCliHighlight(string $sql, string $html): void
    {
        $formatter = new SqlFormatter(new CliHighlighter());
        $this->assertEquals(trim($html), trim($formatter->format($sql)));
    }

    /**
     * @dataProvider compressData
     */
    public function testCompress(string $sql, string $html): void
    {
        $this->assertEquals(trim($html), trim($this->formatter->compress($sql)));
    }

    public function testUsePre(): void
    {
        $formatter = new SqlFormatter(new HtmlHighlighter([], false));
        $actual    = $formatter->highlight('test');
        $expected  = '<span style="color: #333;">test</span>';
        $this->assertEquals($actual, $expected);

        $formatter = new SqlFormatter(new HtmlHighlighter([], true));
        $actual    = $formatter->highlight('test');
        $expected  = '<pre style="color: black; background-color: white;">' .
            '<span style="color: #333;">test</span></pre>';
        $this->assertEquals($actual, $expected);
    }

    /**
     * @return Generator<mixed[]>
     */
    private function fileDataProvider(string $file): Generator
    {
        $contents = file_get_contents(__DIR__ . '/' . $file);
        assert($contents !== false);
        $formatHighlightData = explode("\n---\n", $contents);
        $sqlData             = $this->sqlData();
        if (count($formatHighlightData) !== count($sqlData)) {
            throw new UnexpectedValueException(sprintf(
                '"%s" (%d sections) and sql.sql (%d sections) should have the same number of sections',
                $file,
                count($formatHighlightData),
                count($sqlData)
            ));
        }

        foreach ($formatHighlightData as $i => $data) {
            yield [$sqlData[$i], $data];
        }
    }

    /**
     * @return Generator<mixed[]>
     */
    public function formatHighlightData(): Generator
    {
        return $this->fileDataProvider('format-highlight.html');
    }

    /**
     * @return Generator<mixed[]>
     */
    public function highlightCliData(): Generator
    {
        return $this->fileDataProvider('clihighlight.html');
    }

    /**
     * @return Generator<mixed[]>
     */
    public function formatData(): Generator
    {
        return $this->fileDataProvider('format.html');
    }

    /**
     * @return Generator<mixed[]>
     */
    public function compressData(): Generator
    {
        return $this->fileDataProvider('compress.html');
    }

    /**
     * @return Generator<mixed[]>
     */
    public function highlightData(): Generator
    {
        return $this->fileDataProvider('highlight.html');
    }

    /**
     * @return mixed[]
     */
    public function sqlData(): array
    {
        if (! $this->sqlData) {
            $contents = file_get_contents(__DIR__ . '/sql.sql');
            assert($contents !== false);
            $this->sqlData = explode("\n---\n", $contents);
        }

        return $this->sqlData;
    }
}
