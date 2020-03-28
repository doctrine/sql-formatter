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
input and returns a formatted HTML block inside a `pre` tag.

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

![](http://jdorn.github.com/sql-formatter/format-highlight.png)

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

![](http://jdorn.github.com/sql-formatter/format.png)

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

![](http://jdorn.github.com/sql-formatter/highlight.png)

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
echo (new SqlFormatter())->compress($query)
```

Output:

```sql
SELECT Id as temp, DateCreated as Created FROM MyTable;
```
