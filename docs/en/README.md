# QueryMaker PHP — Documentation

QueryMaker is a fluent, object-oriented SQL query builder for PHP. Instead of concatenating
strings, you build a `Query` object out of small composable parts (columns, conditions, joins,
CTEs, unions, ...) and hand it to a `Maker` (currently MariaDB/MySQL), which renders it to a
parameterized SQL string plus an array of bound values.

```php
use Murdej\QueryMaker\Common\Query;
use Murdej\QueryMaker\Maker\MariaDB;

$query = new Query();
$query->columns->addColumns('id', 'name', 'email');
$query->fromTable('users u');
$query->conditions->addEq('u.active', 1);
$query->orders->addAscColumn('name');
$query->limitCount = 20;

$maker = new MariaDB();
$result = $maker->makeQuery($query); // QueryAndValues { query: string, values: array }

echo $result->query;
```

```sql
SELECT `id`, `name`, `email`
FROM `users` `u`
WHERE
    (`u`.`active` = ?)
    ORDER BY `name` ASC
LIMIT 0, 20
```

```php
print_r($result->values); // [1]
```

Every value ends up as a `?` placeholder in `$result->query` and the matching PHP value in
`$result->values`, in the same order — ready to hand to `PDO::prepare()`/`execute()`,
`mysqli`, Nette Database, or any other driver that accepts positional placeholders.

## Table of contents

1. [Installation](installation.md)
2. [Core concepts](core-concepts.md) — `Query`, `DataSource`, `Maker`, `QueryAndValues`, `Identifier`
3. [Columns (`SELECT`)](columns.md)
4. [Conditions (`WHERE` / `HAVING`)](conditions.md)
5. [Joins](joins.md)
6. [Subqueries and `EXISTS`](subqueries.md)
7. [Grouping, ordering and `LIMIT`](group-order-limit.md)
8. [Common Table Expressions (`WITH`)](cte.md)
9. [`UNION` / `UNION ALL`](unions.md)
10. [Fulltext search (`MATCH ... AGAINST`)](fulltext-search.md)
11. [Raw SQL snippets](raw-sql-snippets.md)
12. [Nette Database bridge](nette-bridge.md)
13. [Known limitations](known-limitations.md) — things the API exposes but currently renders incorrectly
14. [Unsupported SQL constructs](unsupported-constructs.md) — things the API has no support for at all

## Why QueryMaker

- **Composable** — every part of a query (a column, a condition, a join target, a CTE body...)
  is itself a `DataSource`/`Snippet`, so sub-selects and nested conditions are built with the
  exact same API as the top-level query.
- **Parameterized by construction** — there is no string interpolation of values anywhere in the
  public API; every scalar you pass in becomes a bound placeholder.
- **Small surface** — the library only builds SQL. It doesn't run queries, doesn't manage
  connections, and doesn't map results to objects. Send the generated `query`/`values` to
  whatever database layer you already use.

See [Known limitations](known-limitations.md) before relying on `addColumns()`'s shorthand
parsing, `addNotIn()`, or the fulltext convenience methods — each has a caveat worth knowing
about up front. QueryMaker is also `SELECT`-only and MySQL/MariaDB-only — see
[Unsupported SQL constructs](unsupported-constructs.md) for the full list of what it doesn't do.
