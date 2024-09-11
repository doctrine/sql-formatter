<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function array_key_last;
use function array_map;
use function array_pop;
use function assert;
use function count;
use function implode;
use function is_int;
use function preg_match;
use function preg_quote;
use function reset;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;
use function usort;

/** @internal */
final class Tokenizer
{
    /**
     * Reserved words (for syntax highlighting)
     *
     * @var list<string>
     */
    private array $reserved = [
        'ACCESSIBLE',
        'ACTION',
        'ADD',
        'AFTER',
        'AGAINST',
        'AGGREGATE',
        'ALGORITHM',
        'ALL',
        'ALTER',
        'ANALYSE',
        'ANALYZE',
        'AND',
        'AS',
        'ASC',
        'AUTOCOMMIT',
        'AUTO_INCREMENT',
        'BACKUP',
        'BEGIN',
        'BETWEEN',
        'BIGINT',
        'BINARY',
        'BINLOG',
        'BLOB',
        'BOTH',
        'BY',
        'CASCADE',
        'CASE',
        'CHANGE',
        'CHANGED',
        'CHAR',
        'CHARACTER',
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
        'CURRENT',
        'CURRENT_TIMESTAMP',
        'DATABASE',
        'DATABASES',
        'DAY',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DECIMAL',
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
        'DOUBLE',
        'DROP',
        'DUMPFILE',
        'DUPLICATE',
        'DYNAMIC',
        'ELSE',
        'ENCLOSED',
        'END',
        'ENGINE',
        'ENGINES',
        'ENGINE_TYPE',
        'ESCAPE',
        'ESCAPED',
        'EVENTS',
        'EXCEPT',
        'EXCLUDE',
        'EXEC',
        'EXECUTE',
        'EXISTS',
        'EXPLAIN',
        'EXTENDED',
        'FALSE',
        'FAST',
        'FETCH',
        'FIELDS',
        'FILE',
        'FILTER',
        'FIRST',
        'FIXED',
        'FLOAT',
        'FLOAT4',
        'FLOAT8',
        'FLUSH',
        'FOLLOWING',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FROM',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GLOBAL',
        'GRANT',
        'GRANTS',
        'GROUP',
        'GROUPS',
        'HAVING',
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
        'INNER',
        'INSERT',
        'INSERT_ID',
        'INSERT_METHOD',
        'INT',
        'INT1',
        'INT2',
        'INT3',
        'INT4',
        'INT8',
        'INTEGER',
        'INTERSECT',
        'INTERVAL',
        'INTO',
        'INVOKER',
        'IS',
        'ISOLATION',
        'JOIN',
        'KEY',
        'KEYS',
        'KILL',
        'LAST_INSERT_ID',
        'LEADING',
        'LEFT',
        'LEVEL',
        'LIKE',
        'LIMIT',
        'LINEAR',
        'LINES',
        'LOAD',
        'LOCAL',
        'LOCK',
        'LOCKS',
        'LOGS',
        'LONG',
        'LONGBLOB',
        'LONGTEXT',
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
        'MEDIUMBLOB',
        'MEDIUMINT',
        'MEDIUMTEXT',
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
        'NULL',
        'NUMERIC',
        'OFFSET',
        'ON',
        'OPEN',
        'OPTIMIZE',
        'OPTION',
        'OPTIONALLY',
        'OR',
        'ORDER',
        'OUTER',
        'OUTFILE',
        'OVER',
        'PACK_KEYS',
        'PAGE',
        'PARTIAL',
        'PARTITION',
        'PARTITIONS',
        'PASSWORD',
        'PRECEDING',
        'PRIMARY',
        'PRIVILEGES',
        'PROCEDURE',
        'PROCESS',
        'PROCESSLIST',
        'PURGE',
        'QUICK',
        'RAID0',
        'RAID_CHUNKS',
        'RAID_CHUNKSIZE',
        'RAID_TYPE',
        'RANGE',
        'READ',
        'READ_ONLY',
        'READ_WRITE',
        'REAL',
        'RECURSIVE',
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
        'RIGHT',
        'RLIKE',
        'ROLLBACK',
        'ROW',
        'ROWS',
        'ROW_FORMAT',
        'SECOND',
        'SECURITY',
        'SELECT',
        'SEPARATOR',
        'SERIALIZABLE',
        'SESSION',
        'SET',
        'SHARE',
        'SHOW',
        'SHUTDOWN',
        'SLAVE',
        'SMALLINT',
        'SONAME',
        'SOUNDS',
        'SQL',
        'SQL_AUTO_IS_NULL',
        'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS',
        'SQL_BIG_TABLES',
        'SQL_BUFFER_RESULT',
        'SQL_CACHE',
        'SQL_CALC_FOUND_ROWS',
        'SQL_LOG_BIN',
        'SQL_LOG_OFF',
        'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES',
        'SQL_MAX_JOIN_SIZE',
        'SQL_NO_CACHE',
        'SQL_QUOTE_SHOW_CREATE',
        'SQL_SAFE_UPDATES',
        'SQL_SELECT_LIMIT',
        'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT',
        'SQL_WARNINGS',
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
        'TIES',
        'TINYBLOB',
        'TINYINT',
        'TINYTEXT',
        'TO',
        'TRAILING',
        'TRANSACTIONAL',
        'TRUE',
        'TRUNCATE',
        'TYPE',
        'TYPES',
        'UNBOUNDED',
        'UNCOMMITTED',
        'UNION',
        'UNIQUE',
        'UNLOCK',
        'UNSIGNED',
        'UPDATE',
        'USAGE',
        'USE',
        'USING',
        'VALUES',
        'VARBINARY',
        'VARCHAR',
        'VARCHARACTER',
        'VARIABLES',
        'VIEW',
        'WHEN',
        'WHERE',
        'WINDOW',
        'WITH',
        'WORK',
        'WRITE',
        'XOR',
        'YEAR_MONTH',
    ];

    /**
     * For SQL formatting
     * These keywords will all be on their own line
     *
     * @var list<string>
     */
    private array $reservedToplevel = [
        'ADD',
        'ALTER TABLE',
        'CHANGE',
        'DELETE FROM',
        'DROP',
        'EXCEPT',
        'FETCH',
        'FROM',
        'GROUP BY',
        'GROUPS',
        'HAVING',
        'INTERSECT',
        'LIMIT',
        'MODIFY',
        'OFFSET',
        'ORDER BY',
        'PARTITION BY',
        'RANGE',
        'ROWS',
        'SELECT',
        'SET',
        'UNION',
        'UNION ALL',
        'UPDATE',
        'VALUES',
        'WHERE',
        'WINDOW',
        'WITH',
    ];

    /** @var list<string> */
    private array $reservedNewline = [
        'AND',
        'EXCLUDE',
        'INNER JOIN',
        'JOIN',
        'LEFT JOIN',
        'LEFT OUTER JOIN',
        'OR',
        'OUTER JOIN',
        'RIGHT JOIN',
        'RIGHT OUTER JOIN',
        'STRAIGHT_JOIN',
        'XOR',
    ];

    /** @var list<string> */
    private array $functions = [
        'ABS',
        'ACOS',
        'ADDDATE',
        'ADDTIME',
        'AES_DECRYPT',
        'AES_ENCRYPT',
        'APPROX_COUNT_DISTINCT',
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
        'CHARACTER_LENGTH',
        'CHAR_LENGTH',
        'CHECKSUM_AGG',
        'COALESCE',
        'COERCIBILITY',
        'COMPRESS',
        'CONCAT',
        'CONCAT_WS',
        'CONNECTION_ID',
        'CONV',
        'CONVERT_TZ',
        'CONVEXHULL',
        'COS',
        'COT',
        'COUNT',
        'COUNT_BIG',
        'CRC32',
        'CROSSES',
        'CUME_DIST',
        'CURDATE',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'CURRENT_USER',
        'CURTIME',
        'DATE',
        'DATEDIFF',
        'DATE_ADD',
        'DATE_DIFF',
        'DATE_FORMAT',
        'DATE_SUB',
        'DAYNAME',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFYEAR',
        'DECODE',
        'DEGREES',
        'DENSE_RANK',
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
        'FIRST_VALUE',
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
        'GROUPING',
        'GROUPING_ID',
        'GROUP_CONCAT',
        'GROUP_UNIQUE_USERS',
        'HEX',
        'INET_ATON',
        'INET_NTOA',
        'INSTR',
        'INTERIORRINGN',
        'INTERSECTION',
        'INTERSECTS',
        'ISCLOSED',
        'ISEMPTY',
        'ISNULL',
        'ISRING',
        'ISSIMPLE',
        'IS_FREE_LOCK',
        'IS_USED_LOCK',
        'LAG',
        'LAST_DAY',
        'LAST_VALUE',
        'LCASE',
        'LEAD',
        'LEAST',
        'LENGTH',
        'LINEFROMTEXT',
        'LINEFROMWKB',
        'LINESTRING',
        'LINESTRINGFROMTEXT',
        'LINESTRINGFROMWKB',
        'LISTAGG',
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
        'MLINEFROMTEXT',
        'MLINEFROMWKB',
        'MOD',
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
        'NOW',
        'NTH_VALUE',
        'NTILE',
        'NULLIF',
        'NUMGEOMETRIES',
        'NUMINTERIORRINGS',
        'NUMPOINTS',
        'OCT',
        'OCTET_LENGTH',
        'OLD_PASSWORD',
        'ORD',
        'OVERLAPS',
        'PERCENTILE_CONT',
        'PERCENTILE_DISC',
        'PERCENT_RANK',
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
        'RANK',
        'RELATED',
        'RELEASE_LOCK',
        'REPEAT',
        'REVERSE',
        'ROUND',
        'ROW_COUNT',
        'ROW_NUMBER',
        'RPAD',
        'RTRIM',
        'SCHEMA',
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
        'STDEV',
        'STDEVP',
        'STRCMP',
        'STRING_AGG',
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
        'VAR',
        'VARIANCE',
        'VARP',
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

    /** Regular expression for tokenizing. */
    private readonly string $tokenizeRegex;

    /**
     * Punctuation that can be used as a boundary between other tokens
     *
     * @var list<string>
     */
    private array $boundaries = [
        ',',
        ';',
        '::', // PostgreSQL cast operator
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

    /**
     * Stuff that only needs to be done once. Builds tokenizing regular expression.
     */
    public function __construct()
    {
        $this->tokenizeRegex = $this->makeTokenizeRegex($this->makeTokenizeRegexes());
    }

    /**
     * Make regex from a list of values matching longest value first.
     *
     * Optimized for speed by matching alternative branch only once
     * https://github.com/PCRE2Project/pcre2/issues/411 .
     *
     * @param list<string> $values
     */
    private function makeRegexFromList(array $values, bool $sorted = false): string
    {
        // sort list alphabetically and from longest word to shortest
        if (! $sorted) {
            usort($values, static function (string $a, string $b) {
                return str_starts_with($a, $b) || str_starts_with($b, $a)
                    ? strlen($b) <=> strlen($a)
                    : $a <=> $b;
            });
        }

        /** @var array<int|string, list<string>> $valuesBySharedPrefix */
        $valuesBySharedPrefix = [];
        $items                = [];
        $prefix               = null;

        foreach ($values as $v) {
            if ($prefix !== null && ! str_starts_with($v, substr($prefix, 0, 1))) {
                $valuesBySharedPrefix[$prefix] = $items;
                $items                         = [];
                $prefix                        = null;
            }

            $items[] = $v;

            if ($prefix === null) {
                $prefix = $v;
            } else {
                while (! str_starts_with($v, $prefix)) {
                    $prefix = substr($prefix, 0, -1);
                }
            }
        }

        if ($items !== []) {
            $valuesBySharedPrefix[$prefix] = $items;
            $items                         = [];
            $prefix                        = null;
        }

        $regex = '(?>';

        foreach ($valuesBySharedPrefix as $prefix => $items) {
            if ($regex !== '(?>') {
                $regex .= '|';
            }

            if (is_int($prefix)) {
                $prefix = (string) $prefix;
            }

            $regex .= preg_quote($prefix, '/');

            $regex .= count($items) === 1
                ? preg_quote(substr(reset($items), strlen($prefix)), '/')
                : $this->makeRegexFromList(array_map(static fn ($v) => substr($v, strlen($prefix)), $items), true);
        }

        return $regex . ')';
    }

    /** @return array<Token::TOKEN_TYPE_*, string> */
    private function makeTokenizeRegexes(): array
    {
        // Set up regular expressions
        $regexBoundaries       = $this->makeRegexFromList($this->boundaries);
        $regexReserved         = $this->makeRegexFromList($this->reserved);
        $regexReservedToplevel = str_replace(' ', '\s+', $this->makeRegexFromList($this->reservedToplevel));
        $regexReservedNewline  = str_replace(' ', '\s+', $this->makeRegexFromList($this->reservedNewline));
        $regexFunction         = $this->makeRegexFromList($this->functions);

        return [
            Token::TOKEN_TYPE_WHITESPACE => '\s+',
            Token::TOKEN_TYPE_COMMENT => '(?:--|#)[^\n]*+',
            Token::TOKEN_TYPE_BLOCK_COMMENT => '/\*(?:[^*]+|\*(?!/))*+(?:\*|$)(?:/|$)',
            // 1. backtick quoted string using `` to escape
            // 2. square bracket quoted string (SQL Server) using ]] to escape
            Token::TOKEN_TYPE_BACKTICK_QUOTE => <<<'EOD'
                (?>(?x)
                    `(?:[^`]+|`(?:`|$))*+(?:`|$)
                    |\[(?:[^\]]+|\](?:\]|$))*+(?:\]|$)
                )
                EOD,
            // 3. double quoted string using "" or \" to escape
            // 4. single quoted string using '' or \' to escape
            Token::TOKEN_TYPE_QUOTE => <<<'EOD'
                (?>(?sx)
                    '(?:[^'\\]+|\\(?:.|$)|'(?:'|$))*+(?:'|$)
                    |"(?:[^"\\]+|\\(?:.|$)|"(?:"|$))*+(?:"|$)
                )
                EOD,
            // User-defined variable, possibly with quoted name
            Token::TOKEN_TYPE_VARIABLE => '[@:](?:[\w.$]++|(?&t_' . Token::TOKEN_TYPE_BACKTICK_QUOTE . ')|(?&t_' . Token::TOKEN_TYPE_QUOTE . '))',
            // decimal, binary, or hex
            Token::TOKEN_TYPE_NUMBER => '(?:\d+(?:\.\d+)?|0x[\da-fA-F]+|0b[01]+)(?=$|\s|"\'`|' . $regexBoundaries . ')',
            // punctuation and symbols
            Token::TOKEN_TYPE_BOUNDARY => $regexBoundaries,
            // A reserved word cannot be preceded by a '.'
            // this makes it so in "mytable.from", "from" is not considered a reserved word
            Token::TOKEN_TYPE_RESERVED_TOPLEVEL => '(?<!\.)' . $regexReservedToplevel . '(?=$|\s|' . $regexBoundaries . ')',
            Token::TOKEN_TYPE_RESERVED_NEWLINE => '(?<!\.)' . $regexReservedNewline . '(?=$|\s|' . $regexBoundaries . ')',
            Token::TOKEN_TYPE_RESERVED => '(?<!\.)' . $regexReserved . '(?=$|\s|' . $regexBoundaries . ')'
                // A function must be succeeded by '('
                // this makes it so "count(" is considered a function, but "count" alone is not function
                . '|' . $regexFunction . '(?=\s*\()',
            Token::TOKEN_TYPE_WORD => '.*?(?=$|\s|["\'`]|' . $regexBoundaries . ')',
        ];
    }

    /** @param array<Token::TOKEN_TYPE_*, string> $regexes */
    private function makeTokenizeRegex(array $regexes): string
    {
        $parts = [];

        foreach ($regexes as $type => $regex) {
            $parts[] = '(?<t_' . $type . '>' . $regex . ')';
        }

        return '~\G(?:' . implode('|', $parts) . ')~';
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param string $string The SQL string
     */
    public function tokenize(string $string): Cursor
    {
        $tokenizeRegex = $this->tokenizeRegex;
        $upper         = strtoupper($string);

        $tokens = [];
        $offset = 0;

        while ($offset < strlen($string)) {
            // Get the next token and the token type
            preg_match($tokenizeRegex, $upper, $matches, 0, $offset);
            assert(($matches[0] ?? '') !== '');

            while (is_int($lastMatchesKey = array_key_last($matches))) {
                array_pop($matches);
            }

            assert(str_starts_with($lastMatchesKey, 't_'));

            /** @var Token::TOKEN_TYPE_* $tokenType */
            $tokenType = (int) substr($lastMatchesKey, 2);

            $token = new Token($tokenType, substr($string, $offset, strlen($matches[0])));

            $offset += strlen($token->value());

            $tokens[] = $token;
        }

        return new Cursor($tokens);
    }
}
