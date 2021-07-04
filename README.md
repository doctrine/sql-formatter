# SqlFormatter

A lightweight php package for formatting sql statements.

It can automatically indent and add line breaks in addition to syntax
highlighting.

## History

This package is a fork from https://github.com/jdorn/sql-formatter
Here is what the original History section says:

> I found myself having to debug auto-generated SQL statements all the time and
> wanted some way to easily output formatted HTML without having to include a
> huge library or copy and paste into online formatters.

> I was originally planning to extract the formatting code from PhpMyAdmin,
> but that was 10,000+ lines of code and used global variables.

> I saw that other people had the same problem and used Stack Overflow user
> losif's answer as a starting point.  http://stackoverflow.com/a/3924147

― @jdorn

## Usage

The `SqlFormatter` class has a method `format` which takes an SQL string as
input and returns a formatted block.

Sample usage:

```php
<?php
require_once 'vendor/autoload.php';

use Doctrine\SqlFormatter\SqlFormatter;

$query = "SELECT count(*),`Column1`,`Testing`, `Testing Three` FROM `Table1`
    WHERE Column1 = 'testing' AND ( (`Column2` = `Column3` OR Column4 >= NOW()) )
    GROUP BY Column1 ORDER BY Column3 DESC LIMIT 5,10";

echo (new SqlFormatter())->format($query);
```

Output:

<pre style="color: black; background-color: white;"><span style="font-weight:bold;">SELECT</span>
  <span style="font-weight:bold;">count</span>(<span >*</span>)<span >,</span>
  <span style="color: purple;">`Column1`</span><span >,</span>
  <span style="color: purple;">`Testing`</span><span >,</span>
  <span style="color: purple;">`Testing Three`</span>
<span style="font-weight:bold;">FROM</span>
  <span style="color: purple;">`Table1`</span>
<span style="font-weight:bold;">WHERE</span>
  <span style="color: #333;">Column1</span> <span >=</span> <span style="color: blue;">'testing'</span>
  <span style="font-weight:bold;">AND</span> (
    (
      <span style="color: purple;">`Column2`</span> <span >=</span> <span style="color: purple;">`Column3`</span>
      <span style="font-weight:bold;">OR</span> <span style="color: #333;">Column4</span> <span >&gt;</span><span >=</span> <span style="font-weight:bold;">NOW()</span>
    )
  )
<span style="font-weight:bold;">GROUP BY</span>
  <span style="color: #333;">Column1</span>
<span style="font-weight:bold;">ORDER BY</span>
  <span style="color: #333;">Column3</span> <span style="font-weight:bold;">DESC</span>
<span style="font-weight:bold;">LIMIT</span>
  <span style="color: green;">5</span><span >,</span> <span style="color: green;">10</span></pre>

When you run php under cli and instantiated `SqlFormatter` without argument, highlighted with `CliHighlighter`.

SqlFormatter constructor takes `Highlighter` implementations. `HtmlHighlighter` etc.


### Formatting Only

If you don't want syntax highlighting and only want the indentations and
line breaks, pass in a `NullHighlighter` instance as the second parameter.

This is useful for outputting to error logs or other non-html formats.

```php
<?php

use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

echo (new SqlFormatter(new NullHighlighter()))->format($query);
```

Output:

```
SELECT
  count(*),
  `Column1`,
  `Testing`,
  `Testing Three`
FROM
  `Table1`
WHERE
  Column1 = 'testing'
  AND (
    (
      `Column2` = `Column3`
      OR Column4 >= NOW()
    )
  )
GROUP BY
  Column1
ORDER BY
  Column3 DESC
LIMIT
  5, 10
```

### Syntax Highlighting Only

There is a separate method `highlight` that preserves all original whitespace
and just adds syntax highlighting.

This is useful for sql that is already well formatted and just needs to be a
little easier to read.

```php
<?php
echo (new SqlFormatter())->highlight($query);
```

Output:

<pre style="color: black; background-color: white;"><span style="font-weight:bold;">SELECT</span> <span style="font-weight:bold;">count</span>(<span >*</span>)<span >,</span><span style="color: purple;">`Column1`</span><span >,</span><span style="color: purple;">`Testing`</span><span >,</span> <span style="color: purple;">`Testing Three`</span> <span style="font-weight:bold;">FROM</span> <span style="color: purple;">`Table1`</span>
    <span style="font-weight:bold;">WHERE</span> <span style="color: #333;">Column1</span> <span >=</span> <span style="color: blue;">'testing'</span> <span style="font-weight:bold;">AND</span> ( (<span style="color: purple;">`Column2`</span> <span >=</span> <span style="color: purple;">`Column3`</span> <span style="font-weight:bold;">OR</span> <span style="color: #333;">Column4</span> <span >&gt;</span><span >=</span> <span style="font-weight:bold;">NOW()</span>) )
    <span style="font-weight:bold;">GROUP BY</span> <span style="color: #333;">Column1</span> <span style="font-weight:bold;">ORDER BY</span> <span style="color: #333;">Column3</span> <span style="font-weight:bold;">DESC</span> <span style="font-weight:bold;">LIMIT</span> <span style="color: green;">5</span><span >,</span><span style="color: green;">10</span></pre>

### Compress Query

The `compress` method removes all comments and compresses whitespace.

This is useful for outputting queries that can be copy pasted to the command
line easily.

```sql
-- This is a comment
    SELECT
    /* This is another comment
    On more than one line */
    Id #This is one final comment
    as temp, DateCreated as Created FROM MyTable;
```

```php
echo (new SqlFormatter())->compress($query);
```

Output:

```sql
SELECT Id as temp, DateCreated as Created FROM MyTable;
```
