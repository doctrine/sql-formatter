<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';

use Doctrine\SqlFormatter\SqlFormatter;

$formatter = new SqlFormatter();

// Example statements for formatting and highlighting
$statements = [
    <<<'SQL'
    SELECT DATE_FORMAT(b.t_create, '%Y-%c-%d') dateID, b.title memo
    FROM (SELECT id FROM orc_scheme_detail d WHERE d.business=208
    AND d.type IN (29,30,31,321,33,34,3542,361,327,38,39,40,41,42,431,4422,415,4546,47,48,'a',
    29,30,31,321,33,34,3542,361,327,38,39,40,41,42,431,4422,415,4546,47,48,'a')
    AND d.title IS NOT NULL AND t_create >=
    DATE_FORMAT((DATE_SUB(NOW(),INTERVAL 1 DAY)),'%Y-%c-%d') AND t_create
    < DATE_FORMAT(NOW(), '%Y-%c-%d') ORDER BY d.id LIMIT 2,10) a,
    orc_scheme_detail b WHERE a.id = b.id
SQL,

    <<<'SQL'
    SELECT * from Table1 LEFT
    OUTER JOIN Table2 on Table1.id = Table2.id
SQL,

    <<<'SQL'
    SELECT * FROM MyTable WHERE id = 46'
SQL,

    <<<'SQL'
    SELECT count(*),`Column1` as count,`Testing`, `Testing Three` FROM `Table1`
    WHERE Column1 = 'testing' AND ( (`Column2` = `Column3` OR Column4 >= NOW()) )
    GROUP BY Column1 ORDER BY Column3 DESC LIMIT 5,10
SQL,

    <<<'SQL'
    select * from `Table`, (SELECT group_concat(column1) as col FROM Table2 GROUP BY category)
    Table2, Table3 where Table2.col = (Table3.col2 - `Table`.id)
SQL,

    <<<'SQL'
    insert ignore into Table3 (column1, column2) VALUES ('test1','test2'), ('test3','test4');
SQL,

    <<<'SQL'
    UPDATE MyTable SET name='sql', category='databases' WHERE id > '65'
SQL,

    <<<'SQL'
    delete from MyTable WHERE name LIKE "test%"'
SQL,

    <<<'SQL'
    SELECT * FROM UnmatchedParens WHERE ( A = B)) AND (((Test=1)
SQL,

    <<<'SQL'
    -- This is a comment
    SELECT
    /* This is another comment
    On more than one line */
    Id #This is one final comment
    as temp, DateCreated as Created FROM MyTable;
SQL,
];

// Example statements for splitting SQL strings into individual queries
$splitStatements = [
    <<<'SQL'
    DROP TABLE IF EXISTS MyTable;
    CREATE TABLE MyTable ( id int );
    INSERT INTO MyTable    (id)
        VALUES
        (1),(2),(3),(4);
    SELECT * FROM MyTable;
SQL,

    <<<'SQL'
    SELECT ";"; SELECT ";\\"; a;";
    SELECT ";
        abc";
    SELECT a,b #comment;
    FROM test;
SQL,

    <<<'SQL'
    -- Drop the table first if it exists
    DROP TABLE IF EXISTS MyTable;

    -- Create the table
    CREATE TABLE MyTable ( id int );

    -- Insert values
    INSERT INTO MyTable (id)
        VALUES
        (1),(2),(3),(4);

    -- Done
SQL,
];

// Example statements for removing comments
$commentStatements = [
    <<<'SQL'
    -- This is a comment
    SELECT
    /* This is another comment
    On more than one line */
    Id #This is one final comment
    as temp, DateCreated as Created FROM MyTable;
SQL,
];
?>
<!DOCTYPE html>
<html>
    <head>
        <title>SqlFormatter Examples</title>
        <style>
            body {
                font-family: arial;
            }

            table, td, th {
                border: 1px solid #aaa;
            }

            table {
                border-width: 1px 1px 0 0;
                border-spacing: 0;
            }

            td, th {
                border-width: 0 0 1px 1px;
                padding: 5px 10px;
                vertical-align: top;
            }

            pre {
                padding: 0;
                margin: 0;
            }
        </style>
    </head>
    <body>

        <h1>Formatting And Syntax Highlighting</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$formatted = (new SqlFormatter())->format($sql);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Formatted And Highlighted</th>
            </tr>
            <?php foreach ($statements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $sql; ?></pre>
                </td>
                <td><?= $formatter->format($sql); ?></td>
            </tr>
            <?php endforeach ?>
        </table>


        <h1>Formatting Only</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$formatted = (new SqlFormatter())->format($sql, false);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Formatted</th>
            </tr>
            <?php foreach ($statements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $sql; ?></pre>
                </td>
                <td><pre><?= htmlentities($formatter->format($sql, false)); ?></pre></td>
            </tr>
            <?php endforeach ?>
        </table>


        <h1>Syntax Highlighting Only</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$highlighted = (new SqlFormatter())->highlight($sql);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Highlighted</th>
            </tr>
            <?php foreach ($statements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $sql; ?></pre>
                </td>
                <td><?= $formatter->highlight($sql); ?></td>
            </tr>
            <?php endforeach ?>
        </table>


        <h1>Compress Query</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$compressed = (new SqlFormatter())->compress($sql);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Compressed</th>
            </tr>
            <?php foreach ($statements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $sql; ?></pre>
                </td>
                <td><pre><?= $formatter->compress($sql); ?></pre></td>
            </tr>
            <?php endforeach ?>
        </table>


        <h1>Splitting SQL Strings Into Individual Queries</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$queries = (new SqlFormatter())->splitQuery($sql);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Split</th>
            </tr>
            <?php foreach ($splitStatements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $formatter->highlight($sql); ?></pre>
                </td>
                <td>
                    <ol>
                        <?php foreach ($formatter->splitQuery($sql) as $query) :?>
                        <li><pre><?=  $formatter->highlight($query) ?></pre></li>
                        <?php endforeach ?>
                    </ol>
                </td>
            </tr>
            <?php endforeach ?>
        </table>


        <h1>Removing Comments</h1>

        <div>
            Usage:
            <pre>
            <?php highlight_string(
                '<?php' . "\n" . '$nocomments = (new SqlFormatter())->removeComments($sql);' . "\n" . '?>'
            ); ?>
            </pre>
        </div>
        <table>
            <tr>
                <th>Original</th>
                <th>Comments Removed</th>
            </tr>
            <?php foreach ($commentStatements as $sql) : ?>
            <tr>
                <td>
                    <pre><?= $formatter->highlight($sql); ?></pre>
                </td>
                <td>
                    <pre><?= $formatter->highlight($formatter->removeComments($sql)) ?></pre>
                </td>
            </tr>
            <?php endforeach ?>
        </table>

    </body>
</html>
