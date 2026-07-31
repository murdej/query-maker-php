# Known limitations

The behaviors below were verified against the current source (commit `c5dbbbf`) by generating
and inspecting the actual SQL output. None of them are things you need to work around by
avoiding the library — each has a documented, working alternative — but each has bitten a naive
first attempt during the writing of this documentation, so they're worth knowing up front.

This page is about API surface that exists but currently renders incorrectly. For SQL
constructs the API has no support for at all (`INSERT`/`UPDATE`/`DELETE`, `SELECT DISTINCT`,
window functions, other dialects, ...), see
[Unsupported SQL constructs](unsupported-constructs.md) instead.

## `addColumns()` shorthand is letters-only

`ColumnCollection::addColumns()` (the multi-argument shorthand) parses each string with a regex
that only matches `[a-zA-Z]` in identifiers and aliases — no digits, no underscores:

```php
$query->columns->addColumns('user_id', 'col2 AS alias2');
```

```sql
SELECT `user`, `col`
```

`user_id` is truncated to `user`; `col2 AS alias2` is truncated to `col` with the alias dropped
entirely. **Use `addColumn()` (singular)** for any real column name — it parses through
`Identifier::fromString()`, which has no such restriction:

```php
$query->columns->addColumn('user_id');
$query->columns->addColumn('t2.col2', 'alias2');
```

```sql
SELECT `user_id`, `t2`.`col2` AS `alias2`
```

Reserve `addColumns()` for quick prototypes with short, purely alphabetic names.

## `NOT IN` renders without a space (`NOTIN`)

`ConditionCollection::addNotIn()` (and `addMulti()`'s `'!column' => [...]` form) currently
render as:

```php
$query->conditions->addNotIn('id', [1, 2, 3]);
```

```sql
WHERE
	(`id` NOTIN (?, ?, ?))
```

`NOTIN` is not valid SQL syntax on any engine — this is a genuine bug in
`BaseMaker::makeCondition()` (a missing space in the string that builds the `IN`/`NOT IN`
keyword), not a formatting choice. Until it's fixed upstream, avoid `addNotIn()` and the
`'!column' => [...]` shape of `addMulti()`; build the same condition as a raw snippet instead,
using `identifier()`/`value()` so column names and bound values are still handled safely:

```php
$snippet = $query->conditions->addSnippet();
$snippet->identifier('id')->code(' NOT IN (')->beginList(', ');
foreach ([1, 2, 3] as $id) $snippet->value($id);
$snippet->endList()->code(')');
```

```sql
WHERE
	(`id` NOT IN (?, ?, ?))
```

## Fulltext convenience methods are broken

`ConditionCollection::addFulltext()`, `OrderCollection::addFulltext()`, and
`DataSource::addFulltextConditionAndOrder()` all delegate to
`FulltextSnipperFactory::createFulltextSnipper()`, which throws a `TypeError` for every calling
convention documented in the changelog — both a plain list of column names (`['title', 'body']`)
and a single column name (`'title'`) fail:

```php
$query->conditions->addFulltext('database performance', ['title', 'body']);
// Fatal error: Snippet::add(): Argument #1 ($a) must be of type
// Snippet|SnippetChunk|Fulltext|null, int given
```

Use the `Fulltext` value object directly instead — see
[Fulltext search](fulltext-search.md) for the full, verified-working pattern:

```php
use Murdej\QueryMaker\Common\Fulltext;
use Murdej\QueryMaker\Common\Condition;

$fulltext = new Fulltext('database performance', ['title', 'body']);
$query->conditions->conds[] = new Condition(null, $fulltext);
```

## `addSubquery()` alias parameter is not applied

`JoinCollection::addSubquery(string $type, string $subQueryAlias, ...)`'s `$subQueryAlias`
argument is accepted but never copied into the join's rendered alias — internally it always
constructs the `Join` with an empty alias string:

```php
$join = $query->joins->addSubquery('LEFT', 'lastOrder', 'customerId');
// $join->subQueryAlias === "" — NOT "lastOrder"
```

Set `$join->subQueryAlias` yourself right after the call (the property is public):

```php
$join = $query->joins->addSubquery('LEFT', 'lastOrder', 'customerId');
$join->subQueryAlias = 'lastOrder';
```

See [Joins](joins.md#joining-a-sub-query) for the complete working example.

## Missing space before `GROUP BY` / `ORDER BY` on a bare `FROM`

When a query's `FROM` is a plain table with **no** `JOIN`, `WHERE`, or `HAVING` clause, the
renderer concatenates `GROUP BY`/`ORDER BY` directly onto the table reference with no separating
whitespace:

```php
$query->columns->addColumns('id', 'name');
$query->fromTable('products');
$query->groups->addColumn('id');
```

```sql
SELECT `id`, `name`
FROM `products`GROUP BY `id`
```

`` `products`GROUP `` is invalid SQL — most drivers will reject it as a syntax error. Any `JOIN`
or `WHERE`/`HAVING` clause before `GROUP BY`/`ORDER BY` inserts the newline that's otherwise
missing, so this only affects the narrow case of an unfiltered, unjoined aggregate/sorted query.
Workarounds:

- Add any `WHERE` condition (even a harmless always-true one) before `GROUP BY`/`ORDER BY`.
- Or post-process `$result->query` to insert whitespace before `GROUP BY`/`ORDER BY` if it's
  glued to the preceding identifier.
- Or always print/log the generated SQL for aggregate queries during development, so this is
  caught before it reaches production.

## Reuse a fresh `Maker` instance per query

`BaseMaker::$content` (the `Snippet` accumulating the rendered SQL) is not reset between calls
to `makeQuery()`. Calling `makeQuery()` twice on the same maker instance concatenates the
second query onto the first's output instead of starting fresh:

```php
$maker = new MariaDB();
$r1 = $maker->makeQuery($query1); // fine
$r2 = $maker->makeQuery($query2); // $r2->query is $query1's SQL followed by $query2's SQL
```

Always construct a new maker (`new MariaDB()`) immediately before each `makeQuery()` call, as
every example in this documentation does.

## Correlated sub-queries are not validated

Nothing in QueryMaker checks that an `Identifier` used inside a sub-query actually refers to a
column the outer query exposes at that point — see
[Subqueries and EXISTS](subqueries.md#correlated-sub-queries-referencing-the-outer-querys-columns).
A typo in a correlated reference does not raise an error; it silently produces SQL that
references the wrong table/alias (which the database will then reject, or worse, resolve to an
unintended column if the name happens to exist elsewhere). Always inspect `$result->query` for
non-trivial correlated sub-queries during development.
