<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\CliHighlighter;
use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Doctrine\SqlFormatter\Tokenizer;
use PHPUnit\Framework\TestCase;
use function assert;
use function defined;
use function explode;
use function file_get_contents;
use function pack;
use function trim;

/**
 * @covers \Doctrine\SqlFormatter\SqlFormatter
 */
final class SqlFormatterTest extends TestCase
{
    /** @var string[] */
    private $sqlData;

    /** @var Tokenizer */
    private static $tokenizer;

    /** @var SqlFormatter */
    private $formatter;

    /** @var HtmlHighlighter */
    private $highlighter;

    public static function setUpBeforeClass() : void
    {
        self::$tokenizer = new Tokenizer();
    }

    protected function setUp() : void
    {
        // Force SqlFormatter to run in non-CLI mode for tests
        $this->highlighter = new HtmlHighlighter();

        $this->formatter = new SqlFormatter(self::$tokenizer, $this->highlighter);
    }

    /**
     * @dataProvider formatHighlightData
     */
    public function testFormatHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim($this->formatter->format($sql)));
    }

    /**
     * @dataProvider formatData
     */
    public function testFormat(string $sql, string $html) : void
    {
        $formatter = new SqlFormatter(new Tokenizer(), new NullHighlighter());
        $this->assertEquals(trim($html), trim($formatter->format($sql)));
    }

    /**
     * @dataProvider highlightData
     */
    public function testHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim($this->formatter->highlight($sql)));
    }

    public function testHighlightBinary() : void
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
    public function testCliHighlight(string $sql, string $html) : void
    {
        $formatter = new SqlFormatter(self::$tokenizer, new CliHighlighter());
        $this->assertEquals(trim($html), trim($formatter->format($sql)));
    }

    /**
     * @dataProvider compressData
     */
    public function testCompress(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim($this->formatter->compress($sql)));
    }

    public function testUsePre() : void
    {
        $this->highlighter->usePre = false;
        $actual                    = $this->formatter->highlight('test');
        $expected                  = '<span style="color: #333;">test</span>';
        $this->assertEquals($actual, $expected);

        $this->highlighter->usePre = true;
        $actual                    = $this->formatter->highlight('test');
        $expected                  = '<pre style="color: black; background-color: white;">' .
            '<span style="color: #333;">test</span></pre>';
        $this->assertEquals($actual, $expected);
    }

    public function testCacheStats() : void
    {
        $stats = self::$tokenizer->getCacheStats();
        $this->assertGreaterThan(1, $stats['hits']);
    }

    /**
     * @return mixed[][]
     */
    public function formatHighlightData() : array
    {
        $contents = file_get_contents(__DIR__ . '/format-highlight.html');
        assert($contents !== false);
        $formatHighlightData = explode("\n\n", $contents);
        $sqlData             = $this->sqlData();

        $return = [];
        foreach ($formatHighlightData as $i => $data) {
            $return[] = [
                $sqlData[$i],
                $data,
            ];
        }

        return $return;
    }

    /**
     * @return mixed[][]
     */
    public function highlightCliData() : array
    {
        $contents = file_get_contents(__DIR__ . '/clihighlight.html');
        assert($contents !== false);
        $clidata = explode("\n\n", $contents);
        $sqlData = $this->sqlData();

        $return = [];
        foreach ($clidata as $i => $data) {
            $return[] = [
                $sqlData[$i],
                $data,
            ];
        }

        return $return;
    }

    /**
     * @return mixed[][]
     */
    public function formatData() : array
    {
        $contents = file_get_contents(__DIR__ . '/format.html');
        assert($contents !== false);
        $formatData = explode("\n\n", $contents);
        $sqlData    = $this->sqlData();

        $return = [];
        foreach ($formatData as $i => $data) {
            $return[] = [
                $sqlData[$i],
                $data,
            ];
        }

        return $return;
    }

    /**
     * @return mixed[][]
     */
    public function compressData() : array
    {
        $contents = file_get_contents(__DIR__ . '/compress.html');
        assert($contents !== false);
        $compressData = explode("\n\n", $contents);
        $sqlData      = $this->sqlData();

        $return = [];
        foreach ($compressData as $i => $data) {
            $return[] = [
                $sqlData[$i],
                $data,
            ];
        }

        return $return;
    }

    /**
     * @return mixed[][]
     */
    public function highlightData() : array
    {
        $contents = file_get_contents(__DIR__ . '/highlight.html');
        assert($contents !== false);
        $highlightData = explode("\n\n", $contents);
        $sqlData       = $this->sqlData();

        $return = [];
        foreach ($highlightData as $i => $data) {
            $return[] = [
                $sqlData[$i],
                $data,
            ];
        }

        return $return;
    }

    /**
     * @return mixed[]
     */
    public function sqlData() : array
    {
        if (! $this->sqlData) {
            $contents = file_get_contents(__DIR__ . '/sql.sql');
            assert($contents !== false);
            $this->sqlData = explode("\n\n", $contents);
        }

        return $this->sqlData;
    }
}
