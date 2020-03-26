<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\SqlFormatter;
use PHPUnit\Framework\TestCase;
use function assert;
use function defined;
use function explode;
use function file_get_contents;
use function implode;
use function pack;
use function trim;

/**
 * @covers \Doctrine\SqlFormatter\SqlFormatter
 */
final class SqlFormatterTest extends TestCase
{
    /** @var string[] */
    private $sqlData;

    /** @var SqlFormatter */
    private static $formatter;

    public static function setUpBeforeClass() : void
    {
        self::$formatter = new SqlFormatter();
        // Force SqlFormatter to run in non-CLI mode for tests
        self::$formatter->cli = false;
    }

    /**
     * @dataProvider formatHighlightData
     */
    public function testFormatHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(self::$formatter->format($sql)));
    }

    /**
     * @dataProvider formatData
     */
    public function testFormat(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(self::$formatter->format($sql, false)));
    }

    /**
     * @dataProvider highlightData
     */
    public function testHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(self::$formatter->highlight($sql)));
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

        $this->assertEquals(trim($html), trim(self::$formatter->highlight($sql)));
    }

    /**
     * @dataProvider highlightCliData
     */
    public function testCliHighlight(string $sql, string $html) : void
    {
        self::$formatter->cli = true;
        $this->assertEquals(trim($html), trim(self::$formatter->format($sql)));
        self::$formatter->cli = false;
    }

    /**
     * @dataProvider compressData
     */
    public function testCompress(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(self::$formatter->compress($sql)));
    }

    public function testUsePre() : void
    {
        self::$formatter->usePre = false;
        $actual                  = self::$formatter->highlight('test');
        $expected                = '<span style="color: #333;">test</span>';
        $this->assertEquals($actual, $expected);

        self::$formatter->usePre = true;
        $actual                  = self::$formatter->highlight('test');
        $expected                = '<pre style="color: black; background-color: white;">' .
            '<span style="color: #333;">test</span></pre>';
        $this->assertEquals($actual, $expected);
    }

    public function testSplitQuery() : void
    {
        $expected = [
            "SELECT 'test' FROM MyTable;",
            'SELECT Column2 FROM SomeOther Table WHERE (test = true);',
        ];

        $actual = self::$formatter->splitQuery(implode(';', $expected));

        $this->assertEquals($expected, $actual);
    }

    public function testSplitQueryEmpty() : void
    {
        $sql      = "SELECT 1;SELECT 2;\n-- This is a comment\n;SELECT 3";
        $expected = ['SELECT 1;','SELECT 2;','SELECT 3'];
        $actual   = self::$formatter->splitQuery($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testRemoveComments() : void
    {
        $expected = self::$formatter->format("SELECT\n * FROM\n MyTable", false);
        $sql      = "/* this is a comment */SELECT#This is another comment\n * FROM-- One final comment\n MyTable";
        $actual   = self::$formatter->removeComments($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testCacheStats() : void
    {
        $stats = self::$formatter->getCacheStats();
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

        /**
        $formatHighlight = array();
        $highlight = array();
        $format = array();
        $compress = array();
        $clihighlight = array();

        foreach($this->sqlData as $sql) {
            $formatHighlight[] = trim(self::$formatter->format($sql));
            $highlight[] = trim(self::$formatter->highlight($sql));
            $format[] = trim(self::$formatter->format($sql, false));
            $compress[] = trim(self::$formatter->compress($sql));

            self::$formatter->cli = true;
            $clihighlight[] = trim(self::$formatter->format($sql));
            self::$formatter->cli = false;
        }

        file_put_contents(__DIR__."/format-highlight.html", implode("\n\n",$formatHighlight));
        file_put_contents(__DIR__."/highlight.html", implode("\n\n",$highlight));
        file_put_contents(__DIR__."/format.html", implode("\n\n",$format));
        file_put_contents(__DIR__."/compress.html", implode("\n\n",$compress));
        file_put_contents(__DIR__."/clihighlight.html", implode("\n\n",$clihighlight));
        /**/

        return $this->sqlData;
    }
}
