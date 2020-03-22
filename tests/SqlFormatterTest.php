<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter\Tests;

use Doctrine\SqlFormatter\SqlFormatter;
use PHPUnit\Framework\TestCase;
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

    public static function setUpBeforeClass() : void
    {
        // Force SqlFormatter to run in non-CLI mode for tests
        SqlFormatter::$cli = false;
    }

    /**
     * @dataProvider formatHighlightData
     */
    public function testFormatHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(SqlFormatter::format($sql)));
    }

    /**
     * @dataProvider formatData
     */
    public function testFormat(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(SqlFormatter::format($sql, false)));
    }

    /**
     * @dataProvider highlightData
     */
    public function testHighlight(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(SqlFormatter::highlight($sql)));
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

        $this->assertEquals(trim($html), trim(SqlFormatter::highlight($sql)));
    }

    /**
     * @dataProvider highlightCliData
     */
    public function testCliHighlight(string $sql, string $html) : void
    {
        SqlFormatter::$cli = true;
        $this->assertEquals(trim($html), trim(SqlFormatter::format($sql)));
        SqlFormatter::$cli = false;
    }

    /**
     * @dataProvider compressData
     */
    public function testCompress(string $sql, string $html) : void
    {
        $this->assertEquals(trim($html), trim(SqlFormatter::compress($sql)));
    }

    public function testUsePre() : void
    {
        SqlFormatter::$usePre = false;
        $actual               = SqlFormatter::highlight('test');
        $expected             = '<span style="color: #333;">test</span>';
        $this->assertEquals($actual, $expected);

        SqlFormatter::$usePre = true;
        $actual               = SqlFormatter::highlight('test');
        $expected             = '<pre style="color: black; background-color: white;">' .
            '<span style="color: #333;">test</span></pre>';
        $this->assertEquals($actual, $expected);
    }

    public function testSplitQuery() : void
    {
        $expected = [
            "SELECT 'test' FROM MyTable;",
            'SELECT Column2 FROM SomeOther Table WHERE (test = true);',
        ];

        $actual = SqlFormatter::splitQuery(implode(';', $expected));

        $this->assertEquals($expected, $actual);
    }

    public function testSplitQueryEmpty() : void
    {
        $sql      = "SELECT 1;SELECT 2;\n-- This is a comment\n;SELECT 3";
        $expected = ['SELECT 1;','SELECT 2;','SELECT 3'];
        $actual   = SqlFormatter::splitQuery($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testRemoveComments() : void
    {
        $expected = SqlFormatter::format("SELECT\n * FROM\n MyTable", false);
        $sql      = "/* this is a comment */SELECT#This is another comment\n * FROM-- One final comment\n MyTable";
        $actual   = SqlFormatter::removeComments($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testCacheStats() : void
    {
        $stats = SqlFormatter::getCacheStats();
        $this->assertGreaterThan(1, $stats['hits']);
    }

    /**
     * @return mixed[][]
     */
    public function formatHighlightData() : array
    {
        $formatHighlightData = explode("\n\n", file_get_contents(__DIR__ . '/format-highlight.html'));
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
        $clidata = explode("\n\n", file_get_contents(__DIR__ . '/clihighlight.html'));
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
        $formatData = explode("\n\n", file_get_contents(__DIR__ . '/format.html'));
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
        $compressData = explode("\n\n", file_get_contents(__DIR__ . '/compress.html'));
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
        $highlightData = explode("\n\n", file_get_contents(__DIR__ . '/highlight.html'));
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
            $this->sqlData = explode("\n\n", file_get_contents(__DIR__ . '/sql.sql'));
        }

        /**
        $formatHighlight = array();
        $highlight = array();
        $format = array();
        $compress = array();
        $clihighlight = array();

        foreach($this->sqlData as $sql) {
            $formatHighlight[] = trim(SqlFormatter::format($sql));
            $highlight[] = trim(SqlFormatter::highlight($sql));
            $format[] = trim(SqlFormatter::format($sql, false));
            $compress[] = trim(SqlFormatter::compress($sql));

            SqlFormatter::$cli = true;
            $clihighlight[] = trim(SqlFormatter::format($sql));
            SqlFormatter::$cli = false;
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
