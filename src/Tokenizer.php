<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function array_combine;
use function array_keys;
use function array_map;
use function arsort;
use function assert;
use function implode;
use function preg_match;
use function preg_quote;
use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

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
        'AFTER',
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
        'CURRENT ROW',
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
        'FILTER',
        'FIRST',
        'FIXED',
        'FLUSH',
        'FOR',
        'FORCE',
        'FOLLOWING',
        'FOREIGN',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GLOBAL',
        'GRANT',
        'GRANTS',
        'GROUP',
        'GROUPS',
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
        'MONTH',
        'MRG_MYISAM',
        'MYISAM',
        'NAMES',
        'NATURAL',
        'NO OTHERS',
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
        'RANGE',
        'RAID0',
        'RAID_CHUNKS',
        'RAID_CHUNKSIZE',
        'RAID_TYPE',
        'READ',
        'READ_ONLY',
        'READ_WRITE',
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
        'TIES',
        'TO',
        'TRAILING',
        'TRANSACTIONAL',
        'TRUE',
        'TRUNCATE',
        'TYPE',
        'TYPES',
        'UNBOUNDED',
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
     * @var list<string>
     */
    private array $reservedToplevel = [
        'WITH',
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
        'CHANGE',
        'MODIFY',
        'ALTER TABLE',
        'DELETE FROM',
        'UNION ALL',
        'UNION',
        'EXCEPT',
        'INTERSECT',
        'PARTITION BY',
        'ROWS',
        'RANGE',
        'GROUPS',
        'WINDOW',
    ];

    /** @var list<string> */
    private array $reservedNewline = [
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
        'EXCLUDE',
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
        'CHAR',
        'CHARACTER_LENGTH',
        'CHARSET',
        'CHAR_LENGTH',
        'CHECKSUM_AGG',
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
        'COUNT_BIG',
        'CRC32',
        'CROSSES',
        'CUME_DIST',
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
        'LAG',
        'LAST_DAY',
        'LAST_INSERT_ID',
        'LAST_VALUE',
        'LCASE',
        'LEAD',
        'LEAST',
        'LEFT',
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
        'PASSWORD',
        'PERCENT_RANK',
        'PERCENTILE_CONT',
        'PERCENTILE_DISC',
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
        'REPLACE',
        'REVERSE',
        'RIGHT',
        'ROUND',
        'ROW_COUNT',
        'ROW_NUMBER',
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
        'STDEV',
        'STDEVP',
        'STDDEV',
        'STDDEV_POP',
        'STDDEV_SAMP',
        'STRING_AGG',
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

    // Regular expressions for tokenizing

    private readonly string $regexBoundaries;
    private readonly string $regexReserved;
    private readonly string $regexReservedNewline;
    private readonly string $regexReservedToplevel;
    private readonly string $regexFunction;

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
     * Stuff that only needs to be done once. Builds regular expressions and
     * sorts the reserved words.
     */
    public function __construct()
    {
        // Sort reserved word list from longest word to shortest, 3x faster than usort
        $reservedMap = array_combine($this->reserved, array_map(strlen(...), $this->reserved));
        assert($reservedMap !== false);
        arsort($reservedMap);
        $this->reserved = array_keys($reservedMap);

        // Set up regular expressions
        $this->regexBoundaries       = '(' . implode(
            '|',
            $this->quoteRegex($this->boundaries),
        ) . ')';
        $this->regexReserved         = '(' . implode(
            '|',
            $this->quoteRegex($this->reserved),
        ) . ')';
        $this->regexReservedToplevel = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedToplevel),
        ) . ')');
        $this->regexReservedNewline  = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedNewline),
        ) . ')');

        $this->regexFunction = '(' . implode('|', $this->quoteRegex($this->functions)) . ')';
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param string $string The SQL string
     */
    public function tokenize(string $string): Cursor
    {
        $tokens = [];

        // Used to make sure the string keeps shrinking on each iteration
        $oldStringLen = strlen($string) + 1;

        $token = null;

        $currentLength = strlen($string);

        // Keep processing the string until it is empty
        while ($currentLength) {
            // If the string stopped shrinking, there was a problem
            if ($oldStringLen <= $currentLength) {
                $tokens[] = new Token(Token::TOKEN_TYPE_ERROR, $string);

                return new Cursor($tokens);
            }

            $oldStringLen =  $currentLength;

            // Get the next token and the token type
            $token       = $this->createNextToken($string, $token);
            $tokenLength = strlen($token->value());

            $tokens[] = $token;

            // Advance the string
            $string = substr($string, $tokenLength);

            $currentLength -= $tokenLength;
        }

        return new Cursor($tokens);
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation
     * are all their own tokens.
     *
     * @param string     $string   The SQL string
     * @param Token|null $previous The result of the previous createNextToken() call
     *
     * @return Token An associative array containing the type and value of the token.
     */
    private function createNextToken(string $string, Token|null $previous = null): Token
    {
        $matches = [];
        // Whitespace
        if (preg_match('/^\s+/', $string, $matches)) {
            return new Token(Token::TOKEN_TYPE_WHITESPACE, $matches[0]);
        }

        // Comment
        if (
            $string[0] === '#' ||
            (isset($string[1]) && ($string[0] === '-' && $string[1] === '-') ||
            (isset($string[1]) && $string[0] === '/' && $string[1] === '*'))
        ) {
            // Comment until end of line
            if ($string[0] === '-' || $string[0] === '#') {
                $last = strpos($string, "\n");
                $type = Token::TOKEN_TYPE_COMMENT;
            } else { // Comment until closing comment tag
                $pos = strpos($string, '*/', 2);
                assert($pos !== false);
                $last = $pos + 2;
                $type = Token::TOKEN_TYPE_BLOCK_COMMENT;
            }

            if ($last === false) {
                $last = strlen($string);
            }

            return new Token($type, substr($string, 0, $last));
        }

        // Quoted String
        if ($string[0] === '"' || $string[0] === '\'' || $string[0] === '`' || $string[0] === '[') {
            return new Token(
                ($string[0] === '`' || $string[0] === '['
                    ? Token::TOKEN_TYPE_BACKTICK_QUOTE
                    : Token::TOKEN_TYPE_QUOTE),
                $this->getQuotedString($string),
            );
        }

        // User-defined Variable
        if (($string[0] === '@' || $string[0] === ':') && isset($string[1])) {
            $value = null;
            $type  = Token::TOKEN_TYPE_VARIABLE;

            // If the variable name is quoted
            if ($string[1] === '"' || $string[1] === '\'' || $string[1] === '`') {
                $value = $string[0] . $this->getQuotedString(substr($string, 1));
            } else {
                // Non-quoted variable name
                preg_match('/^(' . $string[0] . '[a-zA-Z0-9\._\$]+)/', $string, $matches);
                if ($matches) {
                    $value = $matches[1];
                }
            }

            if ($value !== null) {
                return new Token($type, $value);
            }
        }

        // Number (decimal, binary, or hex)
        if (
            preg_match(
                '/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|' . $this->regexBoundaries . ')/',
                $string,
                $matches,
            )
        ) {
            return new Token(Token::TOKEN_TYPE_NUMBER, $matches[1]);
        }

        // Boundary Character (punctuation and symbols)
        if (preg_match('/^(' . $this->regexBoundaries . ')/', $string, $matches)) {
            return new Token(Token::TOKEN_TYPE_BOUNDARY, $matches[1]);
        }

        // A reserved word cannot be preceded by a '.'
        // this makes it so in "mytable.from", "from" is not considered a reserved word
        if (! $previous || $previous->value() !== '.') {
            $upper = strtoupper($string);
            // Top Level Reserved Word
            if (
                preg_match(
                    '/^(' . $this->regexReservedToplevel . ')($|\s|' . $this->regexBoundaries . ')/',
                    $upper,
                    $matches,
                )
            ) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED_TOPLEVEL,
                    substr($upper, 0, strlen($matches[1])),
                );
            }

            // Newline Reserved Word
            if (
                preg_match(
                    '/^(' . $this->regexReservedNewline . ')($|\s|' . $this->regexBoundaries . ')/',
                    $upper,
                    $matches,
                )
            ) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED_NEWLINE,
                    substr($upper, 0, strlen($matches[1])),
                );
            }

            // Other Reserved Word
            if (
                preg_match(
                    '/^(' . $this->regexReserved . ')($|\s|' . $this->regexBoundaries . ')/',
                    $upper,
                    $matches,
                )
            ) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED,
                    substr($upper, 0, strlen($matches[1])),
                );
            }
        }

        // A function must be succeeded by '('
        // this makes it so "count(" is considered a function, but "count" alone is not
        $upper = strtoupper($string);
        // function
        if (preg_match('/^(' . $this->regexFunction . '[(]|\s|[)])/', $upper, $matches)) {
            return new Token(
                Token::TOKEN_TYPE_RESERVED,
                substr($upper, 0, strlen($matches[1]) - 1),
            );
        }

        // Non reserved word
        preg_match('/^(.*?)($|\s|["\'`]|' . $this->regexBoundaries . ')/', $string, $matches);

        return new Token(Token::TOKEN_TYPE_WORD, $matches[1]);
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param string[] $strings The strings to be quoted
     *
     * @return string[] The quoted strings
     */
    private function quoteRegex(array $strings): array
    {
        return array_map(
            static fn (string $string): string => preg_quote($string, '/'),
            $strings,
        );
    }

    private function getQuotedString(string $string): string
    {
        $ret = '';

        // This checks for the following patterns:
        // 1. backtick quoted string using `` to escape
        // 2. square bracket quoted string (SQL Server) using ]] to escape
        // 3. double quoted string using "" or \" to escape
        // 4. single quoted string using '' or \' to escape
        if (
            preg_match(
                '/^(((`[^`]*($|`))+)|
            ((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|
            (("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|
            ((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/sx',
                $string,
                $matches,
            )
        ) {
            $ret = $matches[1];
        }

        return $ret;
    }
}
