# Grouping, ordering and `LIMIT`

## `GROUP BY`

`$query->groups` is a plain `ColumnCollection` — same `addColumn()` / `addColumns()` /
`addPrefixed()` / `addSnippet()` API as `SELECT` columns (see [Columns](columns.md)):

```php
$query->columns->addColumn('customerId');
$query->columns->addSnippet('total')->code('SUM(')->identifier('amount')->code(')');
$query->fromTable('orders');
$query->conditions->addEq('status', 'paid');
$query->groups->addColumn('customerId');
```

```sql
SELECT `customerId`, SUM(`amount`) AS `total`
FROM `orders`
WHERE
	(`status` = ?)
	GROUP BY `customerId`
```

## `HAVING`

`$query->havings` is a `ConditionCollection` — the exact same API as `WHERE`
(see [Conditions](conditions.md)):

```php
$query->havings->add('total', '>', 100);
```

```sql
WHERE
	(`status` = ?)
	HAVING
	(`total` > ?)
	GROUP BY `customerId`
```

## `ORDER BY`

`$query->orders` is an `OrderCollection` (extends `ColumnCollection`) with `Asc`/`Desc`
convenience wrappers around every `add*` method:

```php
public function addAscColumn(string|Identifier $column, string|null $alias = null): ColumnCollection
public function addDescColumn(string|Identifier $column, string|null $alias = null): ColumnCollection
public function addAscSnippet(string $alias): Snippet
public function addDescSnippet(string $alias): Snippet
public function addAscSubSelect(string|null $tableName, string $alias): DataSource
public function addDescSubSelect(string|null $tableName, string $alias): DataSource
```

```php
$query->orders->addAscColumn('name');
$query->orders->addDescColumn('createdAt');
```

```sql
ORDER BY `name` ASC , `createdAt` DESC
```

You can also pass `'ASC'`/`'DESC'` as a trailing word directly to the underlying
`addColumn($column, $alias, $direction)` — the direction is a first-class part of a `Column`,
not string post-processing.

## `LIMIT`

```php
$query->limitFrom = 0;    // default is already 0
$query->limitCount = 20;  // required for LIMIT to be emitted at all
```

```sql
LIMIT 0, 20
```

`LIMIT` is only rendered when `$limitCount` is set (truthy); `$limitFrom` defaults to `0` and is
the offset.

## `SQL_CALC_FOUND_ROWS`

Set `Query::$prepareObtainCount = true` to prefix the `SELECT` with `SQL_CALC_FOUND_ROWS`
(MySQL/MariaDB), so a subsequent `SELECT FOUND_ROWS()` on the same connection returns the total
row count ignoring `LIMIT` — useful for paginated listings without a separate `COUNT(*)` query:

```php
$query->prepareObtainCount = true;
$query->columns->addColumns('id', 'name');
$query->fromTable('products');
$query->limitCount = 10;
```

```sql
SELECT SQL_CALC_FOUND_ROWS `id`, `name`
FROM `products`
LIMIT 0, 10
```

Continue with [Common Table Expressions](cte.md).
