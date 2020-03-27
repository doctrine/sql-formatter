<?php

declare(strict_types=1);

/**
 * SQL Formatter is a collection of utilities for debugging SQL queries.
 * It includes methods for formatting, syntax highlighting, removing comments, etc.
 *
 * @link       http://github.com/jdorn/sql-formatter
 */

namespace Doctrine\SqlFormatter;

use function array_combine;
use function array_keys;
use function array_map;
use function array_search;
use function array_shift;
use function array_unshift;
use function arsort;
use function assert;
use function count;
use function current;
use function defined;
use function htmlentities;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function reset;
use function rtrim;
use function serialize;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function trim;
use const ENT_COMPAT;
use const ENT_IGNORE;
use const PHP_SAPI;

final class SqlFormatter
{
    // Constants for token types
    public const TOKEN_TYPE_WHITESPACE        = 0;
    public const TOKEN_TYPE_WORD              = 1;
    public const TOKEN_TYPE_QUOTE             = 2;
    public const TOKEN_TYPE_BACKTICK_QUOTE    = 3;
    public const TOKEN_TYPE_RESERVED          = 4;
    public const TOKEN_TYPE_RESERVED_TOPLEVEL = 5;
    public const TOKEN_TYPE_RESERVED_NEWLINE  = 6;
    public const TOKEN_TYPE_BOUNDARY          = 7;
    public const TOKEN_TYPE_COMMENT           = 8;
    public const TOKEN_TYPE_BLOCK_COMMENT     = 9;
    public const TOKEN_TYPE_NUMBER            = 10;
    public const TOKEN_TYPE_ERROR             = 11;
    public const TOKEN_TYPE_VARIABLE          = 12;

    // Constants for different components of a token
    public const TOKEN_TYPE  = 0;
    public const TOKEN_VALUE = 1;

    /**
     * Reserved words (for syntax highlighting)
     *
     * @var string[]
     */
    private $reserved = [
        'ACCESSIBLE',
        'ACTION',
        'AGAINST',
        'AGGREGATE',
        'ALGORITHM',
        'ALL',
        'ALTER',
        'ANALYSE',
        'ANALYZE',
        'AS',
        'ASC',
        'AUTOCOMMIT',
        'AUTO_INCREMENT',
        'BACKUP',
        'BEGIN',
        'BETWEEN',
        'BINLOG',
        'BOTH',
        'CASCADE',
        'CASE',
        'CHANGE',
        'CHANGED',
        'CHARACTER SET',
        'CHARSET',
        'CHECK',
        'CHECKSUM',
        'COLLATE',
        'COLLATION',
        'COLUMN',
        'COLUMNS',
        'COMMENT',
        'COMMIT',
        'COMMITTED',
        'COMPRESSED',
        'CONCURRENT',
        'CONSTRAINT',
        'CONTAINS',
        'CONVERT',
        'CREATE',
        'CROSS',
        'CURRENT_TIMESTAMP',
        'DATABASE',
        'DATABASES',
        'DAY',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DEFAULT',
        'DEFINER',
        'DELAYED',
        'DELETE',
        'DESC',
        'DESCRIBE',
        'DETERMINISTIC',
        'DISTINCT',
        'DISTINCTROW',
        'DIV',
        'DO',
        'DUMPFILE',
        'DUPLICATE',
        'DYNAMIC',
        'ELSE',
        'ENCLOSED',
        'END',
        'ENGINE',
        'ENGINE_TYPE',
        'ENGINES',
        'ESCAPE',
        'ESCAPED',
        'EVENTS',
        'EXEC',
        'EXECUTE',
        'EXISTS',
        'EXPLAIN',
        'EXTENDED',
        'FAST',
        'FIELDS',
        'FILE',
        'FIRST',
        'FIXED',
        'FLUSH',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GLOBAL',
        'GRANT',
        'GRANTS',
        'GROUP_CONCAT',
        'HEAP',
        'HIGH_PRIORITY',
        'HOSTS',
        'HOUR',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'IDENTIFIED',
        'IF',
        'IFNULL',
        'IGNORE',
        'IN',
        'INDEX',
        'INDEXES',
        'INFILE',
        'INSERT',
        'INSERT_ID',
        'INSERT_METHOD',
        'INTERVAL',
        'INTO',
        'INVOKER',
        'IS',
        'ISOLATION',
        'KEY',
        'KEYS',
        'KILL',
        'LAST_INSERT_ID',
        'LEADING',
        'LEVEL',
        'LIKE',
        'LINEAR',
        'LINES',
        'LOAD',
        'LOCAL',
        'LOCK',
        'LOCKS',
        'LOGS',
        'LOW_PRIORITY',
        'MARIA',
        'MASTER',
        'MASTER_CONNECT_RETRY',
        'MASTER_HOST',
        'MASTER_LOG_FILE',
        'MATCH',
        'MAX_CONNECTIONS_PER_HOUR',
        'MAX_QUERIES_PER_HOUR',
        'MAX_ROWS',
        'MAX_UPDATES_PER_HOUR',
        'MAX_USER_CONNECTIONS',
        'MEDIUM',
        'MERGE',
        'MINUTE',
        'MINUTE_SECOND',
        'MIN_ROWS',
        'MODE',
        'MODIFY',
        'MONTH',
        'MRG_MYISAM',
        'MYISAM',
        'NAMES',
        'NATURAL',
        'NOT',
        'NOW()',
        'NULL',
        'OFFSET',
        'ON',
        'OPEN',
        'OPTIMIZE',
        'OPTION',
        'OPTIONALLY',
        'ON UPDATE',
        'ON DELETE',
        'OUTFILE',
        'PACK_KEYS',
        'PAGE',
        'PARTIAL',
        'PARTITION',
        'PARTITIONS',
        'PASSWORD',
        'PRIMARY',
        'PRIVILEGES',
        'PROCEDURE',
        'PROCESS',
        'PROCESSLIST',
        'PURGE',
        'QUICK',
        'RANGE',
        'RAID0',
        'RAID_CHUNKS',
        'RAID_CHUNKSIZE',
        'RAID_TYPE',
        'READ',
        'READ_ONLY',
        'READ_WRITE',
        'REFERENCES',
        'REGEXP',
        'RELOAD',
        'RENAME',
        'REPAIR',
        'REPEATABLE',
        'REPLACE',
        'REPLICATION',
        'RESET',
        'RESTORE',
        'RESTRICT',
        'RETURN',
        'RETURNS',
        'REVOKE',
        'RLIKE',
        'ROLLBACK',
        'ROW',
        'ROWS',
        'ROW_FORMAT',
        'SECOND',
        'SECURITY',
        'SEPARATOR',
        'SERIALIZABLE',
        'SESSION',
        'SHARE',
        'SHOW',
        'SHUTDOWN',
        'SLAVE',
        'SONAME',
        'SOUNDS',
        'SQL',
        'SQL_AUTO_IS_NULL',
        'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS',
        'SQL_BIG_TABLES',
        'SQL_BUFFER_RESULT',
        'SQL_CALC_FOUND_ROWS',
        'SQL_LOG_BIN',
        'SQL_LOG_OFF',
        'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES',
        'SQL_MAX_JOIN_SIZE',
        'SQL_QUOTE_SHOW_CREATE',
        'SQL_SAFE_UPDATES',
        'SQL_SELECT_LIMIT',
        'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT',
        'SQL_WARNINGS',
        'SQL_CACHE',
        'SQL_NO_CACHE',
        'START',
        'STARTING',
        'STATUS',
        'STOP',
        'STORAGE',
        'STRAIGHT_JOIN',
        'STRING',
        'STRIPED',
        'SUPER',
        'TABLE',
        'TABLES',
        'TEMPORARY',
        'TERMINATED',
        'THEN',
        'TO',
        'TRAILING',
        'TRANSACTIONAL',
        'TRUE',
        'TRUNCATE',
        'TYPE',
        'TYPES',
        'UNCOMMITTED',
        'UNIQUE',
        'UNLOCK',
        'UNSIGNED',
        'USAGE',
        'USE',
        'USING',
        'VARIABLES',
        'VIEW',
        'WHEN',
        'WITH',
        'WORK',
        'WRITE',
        'YEAR_MONTH',
    ];

    /**
     * For SQL formatting
     * These keywords will all be on their own line
     *
     * @var string[]
     */
    private $reservedToplevel = [
        'SELECT',
        'FROM',
        'WHERE',
        'SET',
        'ORDER BY',
        'GROUP BY',
        'LIMIT',
        'DROP',
        'VALUES',
        'UPDATE',
        'HAVING',
        'ADD',
        'AFTER',
        'ALTER TABLE',
        'DELETE FROM',
        'UNION ALL',
        'UNION',
        'EXCEPT',
        'INTERSECT',
    ];

    /** @var string[] */
    private $reservedNewline = [
        'LEFT OUTER JOIN',
        'RIGHT OUTER JOIN',
        'LEFT JOIN',
        'RIGHT JOIN',
        'OUTER JOIN',
        'INNER JOIN',
        'JOIN',
        'XOR',
        'OR',
        'AND',
    ];

    /** @var string[] */
    private $functions = [
        'ABS',
        'ACOS',
        'ADDDATE',
        'ADDTIME',
        'AES_DECRYPT',
        'AES_ENCRYPT',
        'AREA',
        'ASBINARY',
        'ASCII',
        'ASIN',
        'ASTEXT',
        'ATAN',
        'ATAN2',
        'AVG',
        'BDMPOLYFROMTEXT',
        'BDMPOLYFROMWKB',
        'BDPOLYFROMTEXT',
        'BDPOLYFROMWKB',
        'BENCHMARK',
        'BIN',
        'BIT_AND',
        'BIT_COUNT',
        'BIT_LENGTH',
        'BIT_OR',
        'BIT_XOR',
        'BOUNDARY',
        'BUFFER',
        'CAST',
        'CEIL',
        'CEILING',
        'CENTROID',
        'CHAR',
        'CHARACTER_LENGTH',
        'CHARSET',
        'CHAR_LENGTH',
        'COALESCE',
        'COERCIBILITY',
        'COLLATION',
        'COMPRESS',
        'CONCAT',
        'CONCAT_WS',
        'CONNECTION_ID',
        'CONTAINS',
        'CONV',
        'CONVERT',
        'CONVERT_TZ',
        'CONVEXHULL',
        'COS',
        'COT',
        'COUNT',
        'CRC32',
        'CROSSES',
        'CURDATE',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'CURRENT_TIMESTAMP',
        'CURRENT_USER',
        'CURTIME',
        'DATABASE',
        'DATE',
        'DATEDIFF',
        'DATE_ADD',
        'DATE_DIFF',
        'DATE_FORMAT',
        'DATE_SUB',
        'DAY',
        'DAYNAME',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFYEAR',
        'DECODE',
        'DEFAULT',
        'DEGREES',
        'DES_DECRYPT',
        'DES_ENCRYPT',
        'DIFFERENCE',
        'DIMENSION',
        'DISJOINT',
        'DISTANCE',
        'ELT',
        'ENCODE',
        'ENCRYPT',
        'ENDPOINT',
        'ENVELOPE',
        'EQUALS',
        'EXP',
        'EXPORT_SET',
        'EXTERIORRING',
        'EXTRACT',
        'EXTRACTVALUE',
        'FIELD',
        'FIND_IN_SET',
        'FLOOR',
        'FORMAT',
        'FOUND_ROWS',
        'FROM_DAYS',
        'FROM_UNIXTIME',
        'GEOMCOLLFROMTEXT',
        'GEOMCOLLFROMWKB',
        'GEOMETRYCOLLECTION',
        'GEOMETRYCOLLECTIONFROMTEXT',
        'GEOMETRYCOLLECTIONFROMWKB',
        'GEOMETRYFROMTEXT',
        'GEOMETRYFROMWKB',
        'GEOMETRYN',
        'GEOMETRYTYPE',
        'GEOMFROMTEXT',
        'GEOMFROMWKB',
        'GET_FORMAT',
        'GET_LOCK',
        'GLENGTH',
        'GREATEST',
        'GROUP_CONCAT',
        'GROUP_UNIQUE_USERS',
        'HEX',
        'HOUR',
        'IF',
        'IFNULL',
        'INET_ATON',
        'INET_NTOA',
        'INSERT',
        'INSTR',
        'INTERIORRINGN',
        'INTERSECTION',
        'INTERSECTS',
        'INTERVAL',
        'ISCLOSED',
        'ISEMPTY',
        'ISNULL',
        'ISRING',
        'ISSIMPLE',
        'IS_FREE_LOCK',
        'IS_USED_LOCK',
        'LAST_DAY',
        'LAST_INSERT_ID',
        'LCASE',
        'LEAST',
        'LEFT',
        'LENGTH',
        'LINEFROMTEXT',
        'LINEFROMWKB',
        'LINESTRING',
        'LINESTRINGFROMTEXT',
        'LINESTRINGFROMWKB',
        'LN',
        'LOAD_FILE',
        'LOCALTIME',
        'LOCALTIMESTAMP',
        'LOCATE',
        'LOG',
        'LOG10',
        'LOG2',
        'LOWER',
        'LPAD',
        'LTRIM',
        'MAKEDATE',
        'MAKETIME',
        'MAKE_SET',
        'MASTER_POS_WAIT',
        'MAX',
        'MBRCONTAINS',
        'MBRDISJOINT',
        'MBREQUAL',
        'MBRINTERSECTS',
        'MBROVERLAPS',
        'MBRTOUCHES',
        'MBRWITHIN',
        'MD5',
        'MICROSECOND',
        'MID',
        'MIN',
        'MINUTE',
        'MLINEFROMTEXT',
        'MLINEFROMWKB',
        'MOD',
        'MONTH',
        'MONTHNAME',
        'MPOINTFROMTEXT',
        'MPOINTFROMWKB',
        'MPOLYFROMTEXT',
        'MPOLYFROMWKB',
        'MULTILINESTRING',
        'MULTILINESTRINGFROMTEXT',
        'MULTILINESTRINGFROMWKB',
        'MULTIPOINT',
        'MULTIPOINTFROMTEXT',
        'MULTIPOINTFROMWKB',
        'MULTIPOLYGON',
        'MULTIPOLYGONFROMTEXT',
        'MULTIPOLYGONFROMWKB',
        'NAME_CONST',
        'NULLIF',
        'NUMGEOMETRIES',
        'NUMINTERIORRINGS',
        'NUMPOINTS',
        'OCT',
        'OCTET_LENGTH',
        'OLD_PASSWORD',
        'ORD',
        'OVERLAPS',
        'PASSWORD',
        'PERIOD_ADD',
        'PERIOD_DIFF',
        'PI',
        'POINT',
        'POINTFROMTEXT',
        'POINTFROMWKB',
        'POINTN',
        'POINTONSURFACE',
        'POLYFROMTEXT',
        'POLYFROMWKB',
        'POLYGON',
        'POLYGONFROMTEXT',
        'POLYGONFROMWKB',
        'POSITION',
        'POW',
        'POWER',
        'QUARTER',
        'QUOTE',
        'RADIANS',
        'RAND',
        'RELATED',
        'RELEASE_LOCK',
        'REPEAT',
        'REPLACE',
        'REVERSE',
        'RIGHT',
        'ROUND',
        'ROW_COUNT',
        'RPAD',
        'RTRIM',
        'SCHEMA',
        'SECOND',
        'SEC_TO_TIME',
        'SESSION_USER',
        'SHA',
        'SHA1',
        'SIGN',
        'SIN',
        'SLEEP',
        'SOUNDEX',
        'SPACE',
        'SQRT',
        'SRID',
        'STARTPOINT',
        'STD',
        'STDDEV',
        'STDDEV_POP',
        'STDDEV_SAMP',
        'STRCMP',
        'STR_TO_DATE',
        'SUBDATE',
        'SUBSTR',
        'SUBSTRING',
        'SUBSTRING_INDEX',
        'SUBTIME',
        'SUM',
        'SYMDIFFERENCE',
        'SYSDATE',
        'SYSTEM_USER',
        'TAN',
        'TIME',
        'TIMEDIFF',
        'TIMESTAMP',
        'TIMESTAMPADD',
        'TIMESTAMPDIFF',
        'TIME_FORMAT',
        'TIME_TO_SEC',
        'TOUCHES',
        'TO_DAYS',
        'TRIM',
        'TRUNCATE',
        'UCASE',
        'UNCOMPRESS',
        'UNCOMPRESSED_LENGTH',
        'UNHEX',
        'UNIQUE_USERS',
        'UNIX_TIMESTAMP',
        'UPDATEXML',
        'UPPER',
        'USER',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'UUID',
        'VARIANCE',
        'VAR_POP',
        'VAR_SAMP',
        'VERSION',
        'WEEK',
        'WEEKDAY',
        'WEEKOFYEAR',
        'WITHIN',
        'X',
        'Y',
        'YEAR',
        'YEARWEEK',
    ];

    /**
     * Punctuation that can be used as a boundary between other tokens
     *
     * @var string[]
     */
    private $boundaries = [
        ',',
        ';',
        ':',
        ')',
        '(',
        '.',
        '=',
        '<',
        '>',
        '+',
        '-',
        '*',
        '/',
        '!',
        '^',
        '%',
        '|',
        '&',
        '#',
    ];

    // For HTML syntax highlighting
    // Styles applied to different token types
    /** @var string */
    public $quoteAttributes = 'style="color: blue;"';

    /** @var string */
    public $backtickQuoteAttributes = 'style="color: purple;"';

    /** @var string */
    public $reservedAttributes = 'style="font-weight:bold;"';

    /** @var string */
    public $boundaryAttributes = '';

    /** @var string */
    public $numberAttributes = 'style="color: green;"';

    /** @var string */
    public $wordAttributes = 'style="color: #333;"';

    /** @var string */
    public $errorAttributes = 'style="background-color: red;"';

    /** @var string */
    public $commentAttributes = 'style="color: #aaa;"';

    /** @var string */
    public $variableAttributes = 'style="color: orange;"';

    /** @var string */
    public $preAttributes = 'style="color: black; background-color: white;"';

    /**
     * Whether or not the current environment is the CLI
     * This affects the type of syntax highlighting
     * If not defined, it will be determined automatically
     *
     * @var bool
     */
    public $cli;

    // For CLI syntax highlighting

    /** @var string */
    public $cliQuote = "\x1b[34;1m";

    /** @var string */
    public $cliBacktickQuote = "\x1b[35;1m";

    /** @var string */
    public $cliReserved = "\x1b[37m";

    /** @var string */
    public $cliBoundary = '';

    /** @var string */
    public $cliNumber = "\x1b[32;1m";

    /** @var string */
    public $cliWord = '';

    /** @var string */
    public $cliError = "\x1b[31;1;7m";

    /** @var string */
    public $cliComment = "\x1b[30;1m";

    /** @var string */
    public $cliFunctions = "\x1b[37m";

    /** @var string */
    public $cliVariable = "\x1b[36;1m";

    /**
     * The tab character to use when formatting SQL
     *
     * @var string
     */
    public $tab = '  ';

    /**
     * This flag tells us if queries need to be enclosed in <pre> tags
     *
     * @var bool
     */
    public $usePre = true;

    /**
     * This flag tells us if SqlFormatted has been initialized
     *
     * @var bool
     */
    private $init;

    // Regular expressions for tokenizing

    /** @var string */
    private $regexBoundaries;

    /** @var string */
    private $regexReserved;

    /** @var string */
    private $regexReservedNewline;

    /** @var string */
    private $regexReservedToplevel;

    /** @var string */
    private $regexFunction;

    // Cache variables
    // Only tokens shorter than this size will be cached.  Somewhere between 10
    // and 20 seems to work well for most cases.
    /** @var int */
    public $maxCachekeySize = 15;

    /** @var mixed[][] */
    private $tokenCache = [];

    /** @var int */
    private $cacheHits = 0;

    /** @var int */
    private $cacheMisses = 0;

    /**
     * Get stats about the token cache
     *
     * @return mixed[] An array containing the keys 'hits', 'misses', 'entries', and 'size' in bytes
     */
    public function getCacheStats() : array
    {
        return [
            'hits'=>$this->cacheHits,
            'misses'=>$this->cacheMisses,
            'entries'=>count($this->tokenCache),
            'size'=>strlen(serialize($this->tokenCache)),
        ];
    }

    /**
     * Stuff that only needs to be done once.  Builds regular expressions and sorts the reserved words.
     */
    private function init() : void
    {
        if ($this->init) {
            return;
        }

        // Sort reserved word list from longest word to shortest, 3x faster than usort
        $reservedMap = array_combine($this->reserved, array_map('strlen', $this->reserved));
        assert($reservedMap !== false);
        arsort($reservedMap);
        $this->reserved = array_keys($reservedMap);

        // Set up regular expressions
        $this->regexBoundaries       = '(' . implode(
            '|',
            $this->quoteRegex($this->boundaries)
        ) . ')';
        $this->regexReserved         = '(' . implode(
            '|',
            $this->quoteRegex($this->reserved)
        ) . ')';
        $this->regexReservedToplevel = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedToplevel)
        ) . ')');
        $this->regexReservedNewline  = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedNewline)
        ) . ')');

        $this->regexFunction = '(' . implode('|', $this->quoteRegex($this->functions)) . ')';

        $this->init = true;
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation are all their own tokens.
     *
     * @param string       $string   The SQL string
     * @param mixed[]|null $previous The result of the previous getNextToken() call
     *
     * @return mixed[] An associative array containing the type and value of the token.
     */
    private function getNextToken(string $string, ?array $previous = null) : array
    {
        // Whitespace
        if (preg_match('/^\s+/', $string, $matches)) {
            return [
                self::TOKEN_VALUE => $matches[0],
                self::TOKEN_TYPE=>self::TOKEN_TYPE_WHITESPACE,
            ];
        }

        // Comment
        if ($string[0] === '#' ||
            (isset($string[1]) && ($string[0]==='-' && $string[1]==='-') ||
            ($string[0]==='/' && $string[1]==='*'))) {
            // Comment until end of line
            if ($string[0] === '-' || $string[0] === '#') {
                $last = strpos($string, "\n");
                $type = self::TOKEN_TYPE_COMMENT;
            } else { // Comment until closing comment tag
                $last = strpos($string, '*/', 2) + 2;
                $type = self::TOKEN_TYPE_BLOCK_COMMENT;
            }

            if ($last === false) {
                $last = strlen($string);
            }

            return [
                self::TOKEN_VALUE => substr($string, 0, $last),
                self::TOKEN_TYPE  => $type,
            ];
        }

        // Quoted String
        if ($string[0]==='"' || $string[0]==='\'' || $string[0]==='`' || $string[0]==='[') {
            return [
                self::TOKEN_TYPE => (
                    $string[0]==='`' || $string[0]==='['
                    ? self::TOKEN_TYPE_BACKTICK_QUOTE
                    : self::TOKEN_TYPE_QUOTE),
                self::TOKEN_VALUE => $this->getQuotedString($string),
            ];
        }

        // User-defined Variable
        if (($string[0] === '@' || $string[0] === ':') && isset($string[1])) {
            $ret = [
                self::TOKEN_VALUE => null,
                self::TOKEN_TYPE => self::TOKEN_TYPE_VARIABLE,
            ];

            // If the variable name is quoted
            if ($string[1]==='"' || $string[1]==='\'' || $string[1]==='`') {
                $ret[self::TOKEN_VALUE] = $string[0] . $this->getQuotedString(substr($string, 1));
            } else {
                // Non-quoted variable name
                preg_match('/^(' . $string[0] . '[a-zA-Z0-9\._\$]+)/', $string, $matches);
                if ($matches) {
                    $ret[self::TOKEN_VALUE] = $matches[1];
                }
            }

            if ($ret[self::TOKEN_VALUE] !== null) {
                return $ret;
            }
        }

        // Number (decimal, binary, or hex)
        if (preg_match(
            '/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|' . $this->regexBoundaries . ')/',
            $string,
            $matches
        )) {
            return [
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE=>self::TOKEN_TYPE_NUMBER,
            ];
        }

        // Boundary Character (punctuation and symbols)
        if (preg_match('/^(' . $this->regexBoundaries . ')/', $string, $matches)) {
            return [
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE  => self::TOKEN_TYPE_BOUNDARY,
            ];
        }

        // A reserved word cannot be preceded by a '.'
        // this makes it so in "mytable.from", "from" is not considered a reserved word
        if (! $previous || ! isset($previous[self::TOKEN_VALUE]) || $previous[self::TOKEN_VALUE] !== '.') {
            $upper = strtoupper($string);
            // Top Level Reserved Word
            if (preg_match(
                '/^(' . $this->regexReservedToplevel . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return [
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED_TOPLEVEL,
                    self::TOKEN_VALUE=>substr($string, 0, strlen($matches[1])),
                ];
            }

            // Newline Reserved Word
            if (preg_match(
                '/^(' . $this->regexReservedNewline . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return [
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED_NEWLINE,
                    self::TOKEN_VALUE=>substr($string, 0, strlen($matches[1])),
                ];
            }

            // Other Reserved Word
            if (preg_match(
                '/^(' . $this->regexReserved . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return [
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED,
                    self::TOKEN_VALUE=>substr($string, 0, strlen($matches[1])),
                ];
            }
        }

        // A function must be suceeded by '('
        // this makes it so "count(" is considered a function, but "count" alone is not
        $upper = strtoupper($string);
        // function
        if (preg_match('/^(' . $this->regexFunction . '[(]|\s|[)])/', $upper, $matches)) {
            return [
                self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED,
                self::TOKEN_VALUE=>substr($string, 0, strlen($matches[1])-1),
            ];
        }

        // Non reserved word
        preg_match('/^(.*?)($|\s|["\'`]|' . $this->regexBoundaries . ')/', $string, $matches);

        return [
            self::TOKEN_VALUE => $matches[1],
            self::TOKEN_TYPE  => self::TOKEN_TYPE_WORD,
        ];
    }

    private function getQuotedString(string $string) : string
    {
        $ret = '';

        // This checks for the following patterns:
        // 1. backtick quoted string using `` to escape
        // 2. square bracket quoted string (SQL Server) using ]] to escape
        // 3. double quoted string using "" or \" to escape
        // 4. single quoted string using '' or \' to escape
        if (preg_match(
            '/^(((`[^`]*($|`))+)|
            ((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|
            (("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|
            ((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/sx',
            $string,
            $matches
        )) {
            $ret = $matches[1];
        }

        return $ret;
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param string $string The SQL string
     *
     * @return mixed[][] An array of tokens.
     */
    private function tokenize(string $string) : array
    {
        $this->init();

        $tokens = [];

        // Used for debugging if there is an error while tokenizing the string
        $originalLength = strlen($string);

        // Used to make sure the string keeps shrinking on each iteration
        $oldStringLen = strlen($string) + 1;

        $token = null;

        $currentLength = strlen($string);

        // Keep processing the string until it is empty
        while ($currentLength) {
            // If the string stopped shrinking, there was a problem
            if ($oldStringLen <= $currentLength) {
                $tokens[] = [
                    self::TOKEN_VALUE=>$string,
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_ERROR,
                ];

                return $tokens;
            }

            $oldStringLen =  $currentLength;

            // Determine if we can use caching
            if ($currentLength >= $this->maxCachekeySize) {
                $cacheKey = substr($string, 0, $this->maxCachekeySize);
            } else {
                $cacheKey = false;
            }

            // See if the token is already cached
            if ($cacheKey && isset($this->tokenCache[$cacheKey])) {
                // Retrieve from cache
                $token       = $this->tokenCache[$cacheKey];
                $tokenLength = strlen($token[self::TOKEN_VALUE]);
                $this->cacheHits++;
            } else {
                // Get the next token and the token type
                $token       = $this->getNextToken($string, $token);
                $tokenLength = strlen($token[self::TOKEN_VALUE]);
                $this->cacheMisses++;

                // If the token is shorter than the max length, store it in cache
                if ($cacheKey && $tokenLength < $this->maxCachekeySize) {
                    $this->tokenCache[$cacheKey] = $token;
                }
            }

            $tokens[] = $token;

            // Advance the string
            $string = substr($string, $tokenLength);

            $currentLength -= $tokenLength;
        }

        return $tokens;
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param string $string    The SQL string
     * @param bool   $highlight If true, syntax highlighting will also be performed
     *
     * @return string The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public function format(string $string, bool $highlight = true) : string
    {
        // This variable will be populated with formatted html
        $return = '';

        // Use an actual tab while formatting and then switch out with $this->tab at the end
        $tab = "\t";

        $indentLevel           = 0;
        $newline               = false;
        $inlineParentheses     = false;
        $increaseSpecialIndent = false;
        $increaseBlockIndent   = false;
        $indentTypes           = [];
        $addedNewline          = false;
        $inlineCount           = 0;
        $inlineIndented        = false;
        $clauseLimit           = false;

        // Tokenize String
        $originalTokens = $this->tokenize($string);

        // Remove existing whitespace
        $tokens = [];
        foreach ($originalTokens as $i => $token) {
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_WHITESPACE) {
                continue;
            }

            $token['i'] = $i;
            $tokens[]   = $token;
        }

        // Format token by token
        foreach ($tokens as $i => $token) {
            // Get highlighted token if doing syntax highlighting
            if ($highlight) {
                $highlighted = $this->highlightToken($token);
            } else { // If returning raw text
                $highlighted = $token[self::TOKEN_VALUE];
            }

            // If we are increasing the special indent level now
            if ($increaseSpecialIndent) {
                $indentLevel++;
                $increaseSpecialIndent = false;
                array_unshift($indentTypes, 'special');
            }

            // If we are increasing the block indent level now
            if ($increaseBlockIndent) {
                $indentLevel++;
                $increaseBlockIndent = false;
                array_unshift($indentTypes, 'block');
            }

            // If we need a new line before the token
            if ($newline) {
                $return      .= "\n" . str_repeat($tab, $indentLevel);
                $newline      = false;
                $addedNewline = true;
            } else {
                $addedNewline = false;
            }

            // Display comments directly where they appear in the source
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT ||
                $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                    $indent      = str_repeat($tab, $indentLevel);
                    $return     .= "\n" . $indent;
                    $highlighted = str_replace("\n", "\n" . $indent, $highlighted);
                }

                $return .= $highlighted;
                $newline = true;
                continue;
            }

            if ($inlineParentheses) {
                // End of inline parentheses
                if ($token[self::TOKEN_VALUE] === ')') {
                    $return = rtrim($return, ' ');

                    if ($inlineIndented) {
                        array_shift($indentTypes);
                        $indentLevel--;
                        $return .= "\n" . str_repeat($tab, $indentLevel);
                    }

                    $inlineParentheses = false;

                    $return .= $highlighted . ' ';
                    continue;
                }

                if ($token[self::TOKEN_VALUE] === ',') {
                    if ($inlineCount >= 30) {
                        $inlineCount = 0;
                        $newline     = true;
                    }
                }

                $inlineCount += strlen($token[self::TOKEN_VALUE]);
            }

            // Opening parentheses increase the block indent level and start a new line
            if ($token[self::TOKEN_VALUE] === '(') {
                // First check if this should be an inline parentheses block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline parentheses
                $length = 0;
                for ($j=1; $j<=250; $j++) {
                    // Reached end of string
                    if (! isset($tokens[$i+$j])) {
                        break;
                    }

                    $next = $tokens[$i+$j];

                    // Reached closing parentheses, able to inline it
                    if ($next[self::TOKEN_VALUE] === ')') {
                        $inlineParentheses = true;
                        $inlineCount       = 0;
                        $inlineIndented    = false;
                        break;
                    }

                    // Reached an invalid token for inline parentheses
                    if ($next[self::TOKEN_VALUE]===';' || $next[self::TOKEN_VALUE]==='(') {
                        break;
                    }

                    // Reached an invalid token type for inline parentheses
                    if ($next[self::TOKEN_TYPE]===self::TOKEN_TYPE_RESERVED_TOPLEVEL ||
                        $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_RESERVED_NEWLINE ||
                        $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_COMMENT ||
                        $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_BLOCK_COMMENT) {
                        break;
                    }

                    $length += strlen($next[self::TOKEN_VALUE]);
                }

                if ($inlineParentheses && $length > 30) {
                    $increaseBlockIndent = true;
                    $inlineIndented      = true;
                    $newline             = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                if (isset($originalTokens[$token['i']-1]) &&
                    $originalTokens[$token['i']-1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                    $return = rtrim($return, ' ');
                }

                if (! $inlineParentheses) {
                    $increaseBlockIndent = true;
                    // Add a newline after the parentheses
                    $newline = true;
                }
            } elseif ($token[self::TOKEN_VALUE] === ')') {
                // Closing parentheses decrease the block indent level
                // Remove whitespace before the closing parentheses
                $return = rtrim($return, ' ');

                $indentLevel--;

                // Reset indent level
                while ($j=array_shift($indentTypes)) {
                    if ($j!=='special') {
                        break;
                    }

                    $indentLevel--;
                }

                if ($indentLevel < 0) {
                    // This is an error
                    $indentLevel = 0;

                    if ($highlight) {
                        $return .= "\n" . $this->highlightError($token[self::TOKEN_VALUE]);
                        continue;
                    }
                }

                // Add a newline before the closing parentheses (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }
            } elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                // Top level reserved words start a new line and increase the special indent level
                $increaseSpecialIndent = true;

                // If the last indent type was 'special', decrease the special indent for this round
                reset($indentTypes);
                if (current($indentTypes)==='special') {
                    $indentLevel--;
                    array_shift($indentTypes);
                }

                // Add a newline after the top level reserved word
                $newline = true;
                // Add a newline before the top level reserved word (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                } else {
                    // If we already added a newline, redo the indentation since it may be different now
                    $return = rtrim($return, $tab) . str_repeat($tab, $indentLevel);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE], ' ')!==false ||
                    strpos($token[self::TOKEN_VALUE], "\n")!==false ||
                    strpos($token[self::TOKEN_VALUE], "\t")!==false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }

                //if SQL 'LIMIT' clause, start variable to reset newline
                if ($token[self::TOKEN_VALUE] === 'LIMIT' && ! $inlineParentheses) {
                    $clauseLimit = true;
                }
            } elseif ($clauseLimit &&
                $token[self::TOKEN_VALUE] !== ',' &&
                $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_NUMBER &&
                $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                // Checks if we are out of the limit clause
                $clauseLimit = false;
            } elseif ($token[self::TOKEN_VALUE] === ',' && ! $inlineParentheses) {
                // Commas start a new line (unless within inline parentheses or SQL 'LIMIT' clause)
                //If the previous TOKEN_VALUE is 'LIMIT', resets new line
                if ($clauseLimit === true) {
                    $newline     = false;
                    $clauseLimit = false;
                } else {
                    // All other cases of commas
                    $newline = true;
                }
            } elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE) {
            // Newline reserved words start a new line
                // Add a newline before the reserved word (if not already added)
                if (! $addedNewline) {
                    $return .= "\n" . str_repeat($tab, $indentLevel);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE], ' ')!==false ||
                    strpos($token[self::TOKEN_VALUE], "\n")!==false ||
                    strpos($token[self::TOKEN_VALUE], "\t")!==false) {
                    $highlighted = preg_replace('/\s+/', ' ', $highlighted);
                }
            } elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                // Multiple boundary characters in a row should not have spaces between them (not including parentheses)
                if (isset($tokens[$i-1]) && $tokens[$i-1][self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                    if (isset($originalTokens[$token['i']-1]) &&
                        $originalTokens[$token['i']-1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                        $return = rtrim($return, ' ');
                    }
                }
            }

            // If the token shouldn't have a space before it
            if ($token[self::TOKEN_VALUE] === '.' ||
                $token[self::TOKEN_VALUE] === ',' ||
                $token[self::TOKEN_VALUE] === ';') {
                $return = rtrim($return, ' ');
            }

            $return .= $highlighted . ' ';

            // If the token shouldn't have a space after it
            if ($token[self::TOKEN_VALUE] === '(' || $token[self::TOKEN_VALUE] === '.') {
                $return = rtrim($return, ' ');
            }

            // If this is the "-" of a negative number, it shouldn't have a space after it
            if ($token[self::TOKEN_VALUE] !== '-' ||
                ! isset($tokens[$i+1]) ||
                $tokens[$i+1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_NUMBER ||
                ! isset($tokens[$i-1])) {
                continue;
            }

            $prev = $tokens[$i-1][self::TOKEN_TYPE];
            if ($prev === self::TOKEN_TYPE_QUOTE ||
                $prev === self::TOKEN_TYPE_BACKTICK_QUOTE ||
                $prev === self::TOKEN_TYPE_WORD ||
                $prev === self::TOKEN_TYPE_NUMBER) {
                continue;
            }

            $return = rtrim($return, ' ');
        }

        // If there are unmatched parentheses
        if ($highlight && array_search('block', $indentTypes) !== false) {
            $return .= "\n" . $this->highlightError('WARNING: unclosed parentheses or section');
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t", $this->tab, $return));

        if ($highlight) {
            $return = $this->output($return);
        }

        return $return;
    }

    /**
     * Add syntax highlighting to a SQL string
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string with HTML styles applied
     */
    public function highlight(string $string) : string
    {
        $tokens = $this->tokenize($string);

        $return = '';

        foreach ($tokens as $token) {
            $return .= $this->highlightToken($token);
        }

        return $this->output($return);
    }

    /**
     * Split a SQL string into multiple queries.
     * Uses ";" as a query delimiter.
     *
     * @param string $string The SQL string
     *
     * @return string[] An array of individual query strings without trailing semicolons
     */
    public function splitQuery(string $string) : array
    {
        $queries      = [];
        $currentQuery = '';
        $empty        = true;

        $tokens = $this->tokenize($string);

        foreach ($tokens as $token) {
            // If this is a query separator
            if ($token[self::TOKEN_VALUE] === ';') {
                if (! $empty) {
                    $queries[] = $currentQuery . ';';
                }

                $currentQuery = '';
                $empty        = true;
                continue;
            }

            // If this is a non-empty character
            if ($token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE &&
                $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_COMMENT &&
                $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_BLOCK_COMMENT) {
                $empty = false;
            }

            $currentQuery .= $token[self::TOKEN_VALUE];
        }

        if (! $empty) {
            $queries[] = trim($currentQuery);
        }

        return $queries;
    }

    /**
     * Remove all comments from a SQL string
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string without comments
     */
    public function removeComments(string $string) : string
    {
        $result = '';

        $tokens = $this->tokenize($string);

        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT ||
                $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }

            $result .= $token[self::TOKEN_VALUE];
        }

        $result = $this->format($result, false);

        return $result;
    }

    /**
     * Compress a query by collapsing white space and removing comments
     *
     * @param string $string The SQL string
     *
     * @return string The SQL string without comments
     */
    public function compress(string $string) : string
    {
        $result = '';

        $tokens = $this->tokenize($string);

        $whitespace = true;
        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT ||
                $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }

            // Remove extra whitespace in reserved words (e.g "OUTER     JOIN" becomes "OUTER JOIN")

            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED ||
                $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE ||
                $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $token[self::TOKEN_VALUE] = preg_replace('/\s+/', ' ', $token[self::TOKEN_VALUE]);
            }

            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_WHITESPACE) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                }

                $whitespace = true;
                // Convert all whitespace to a single space
                $token[self::TOKEN_VALUE] = ' ';
            } else {
                $whitespace = false;
            }

            $result .= $token[self::TOKEN_VALUE];
        }

        return rtrim($result);
    }

    /**
     * Highlights a token depending on its type.
     *
     * @param mixed[] $token An associative array containing type and value.
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightToken(array $token) : string
    {
        [self::TOKEN_TYPE => $type, self::TOKEN_VALUE => $value] = $token;

        if (! $this->isCli()) {
            $value = defined('ENT_IGNORE') ?
                 htmlentities($value, ENT_COMPAT | ENT_IGNORE, 'UTF-8'):
                 htmlentities($value, ENT_COMPAT, 'UTF-8');
        }

        switch ($type) {
            case self::TOKEN_TYPE_BOUNDARY:
                return $this->highlightBoundary($value);
            case self::TOKEN_TYPE_WORD:
                return $this->highlightWord($value);
            case self::TOKEN_TYPE_BACKTICK_QUOTE:
                return $this->highlightBacktickQuote($value);
            case self::TOKEN_TYPE_QUOTE:
                return $this->highlightQuote($value);
            case self::TOKEN_TYPE_RESERVED:
            case self::TOKEN_TYPE_RESERVED_TOPLEVEL:
            case self::TOKEN_TYPE_RESERVED_NEWLINE:
                return $this->highlightReservedWord($value);
            case self::TOKEN_TYPE_NUMBER:
                return $this->highlightNumber($value);
            case self::TOKEN_TYPE_VARIABLE:
                return $this->highlightVariable($value);
            case self::TOKEN_TYPE_COMMENT:
            case self::TOKEN_TYPE_BLOCK_COMMENT:
                return $this->highlightComment($value);
            default:
                return $value;
        }
    }

    /**
     * Highlights a quoted string
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightQuote(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliQuote . $value . "\x1b[0m";
        }

        return '<span ' . $this->quoteAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a backtick quoted string
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightBacktickQuote(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliBacktickQuote . $value . "\x1b[0m";
        }

        return '<span ' . $this->backtickQuoteAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a reserved word
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightReservedWord(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliReserved . $value . "\x1b[0m";
        }

        return '<span ' . $this->reservedAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a boundary token
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightBoundary(string $value) : string
    {
        if ($value==='(' || $value===')') {
            return $value;
        }

        if ($this->isCli()) {
            return $this->cliBoundary . $value . "\x1b[0m";
        }

        return '<span ' . $this->boundaryAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a number
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightNumber(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliNumber . $value . "\x1b[0m";
        }

        return '<span ' . $this->numberAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights an error
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightError(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliError . $value . "\x1b[0m";
        }

        return '<span ' . $this->errorAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a comment
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightComment(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliComment . $value . "\x1b[0m";
        }

        return '<span ' . $this->commentAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a word token
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightWord(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliWord . $value . "\x1b[0m";
        }

        return '<span ' . $this->wordAttributes . '>' . $value . '</span>';
    }

    /**
     * Highlights a variable token
     *
     * @param string $value The token's value
     *
     * @return string HTML code of the highlighted token.
     */
    private function highlightVariable(string $value) : string
    {
        if ($this->isCli()) {
            return $this->cliVariable . $value . "\x1b[0m";
        }

        return '<span ' . $this->variableAttributes . '>' . $value . '</span>';
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param string[] $strings The strings to be quoted
     *
     * @return string[] The quoted strings
     */
    private function quoteRegex(array $strings) : array
    {
        return array_map(static function (string $string) : string {
            return preg_quote($string, '/');
        }, $strings);
    }

    /**
     * Helper function for building string output
     *
     * @param string $string The string to be quoted
     *
     * @return string The quoted string
     */
    private function output(string $string) : string
    {
        if ($this->isCli()) {
            return $string . "\n";
        }

        $string =trim($string);
        if (! $this->usePre) {
            return $string;
        }

        return '<pre ' . $this->preAttributes . '>' . $string . '</pre>';
    }

    private function isCli() : bool
    {
        if (isset($this->cli)) {
            return $this->cli;
        }

        return PHP_SAPI === 'cli';
    }
}
