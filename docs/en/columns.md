# Columns (`SELECT`)

`$query->columns` is a `ColumnCollection`. If you never add any column, the maker emits `*`.

## `addColumn()` — one column at a time

```php
public function addColumn(string|Identifier $column, string|null $alias = null, ?string $direction = null): ColumnCollection
```

```php
$query = new Query();
$query->columns->addColumn('id');
$query->columns->addColumn('u.name', 'userName');
$query->fromTable('users u');
```

```sql
SELECT `id`, `u`.`name` AS `userName`
FROM `users` `u`
```

`addColumn()` accepts any real identifier — letters, digits, underscores, `table.column` — since
it parses the string through `Identifier::fromString()`. Prefer it over `addColumns()` (below)
whenever your identifiers aren't purely alphabetic.

## `addColumns()` — several plain columns at once

```php
public function addColumns(string ...$columns)
```

A shorthand for a handful of simple columns, with optional inline alias:

```php
$query->columns->addColumns('id', 'f.bar', 'eee AS q', 't.wx xc');
```

```sql
SELECT `id`, `f`.`bar`, `eee` AS `q`, `t`.`wx` AS `xc`
```

> **Only use `addColumns()` for pure-alphabetic identifiers.** Its shorthand parser is
> regex-based and does not recognize digits or underscores in either the column name or the
> alias — `addColumns('user_id')` silently becomes `` `user` `` and `addColumns('col2 AS a2')`
> becomes `` `col` `` with the alias dropped entirely. See
> [Known limitations](known-limitations.md#addcolumns-shorthand-is-letters-only) for details.
> Use `addColumn()` for anything with digits or underscores.

## `addPrefixed()` — many columns from one table, with a shared alias prefix

```php
public function addPrefixed(string $table, string $prefix, array $columns): ColumnCollection
```

Handy when selecting from a joined table and avoiding name collisions:

```php
$query->columns->addPrefixed('u', 'user_', ['id', 'name', 'email']);
```

```sql
SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, `u`.`email` AS `user_email`
```

## `addSnippet()` — a computed / expression column

Returns a `Snippet` you build up yourself (see [Raw SQL snippets](raw-sql-snippets.md)):

```php
$query->columns->addSnippet('total')
    ->code('SUM(')->identifier('amount')->code(')');
$query->fromTable('invoices');
$query->groups->addColumn('customerId');
```

```sql
SELECT SUM(`amount`) AS `total`
FROM `invoices`GROUP BY `customerId`
```

(The missing space before `GROUP BY` here is a real formatting quirk of the current renderer —
see [Known limitations](known-limitations.md#missing-space-before-group-by--order-by-on-a-bare-from).)

## `addSubSelect()` — scalar sub-query as a column

```php
public function addSubSelect(string|null $tableName, string $alias, ?string $direction = null): DataSource
```

Returns a `DataSource` you configure like any other query; it is wrapped in `(...)` and
aliased for you.

```php
use Murdej\QueryMaker\Common\Identifier;

$query = new Query();
$query->columns->addColumn('c.id');
$query->columns->addColumn('c.name');

$sub = $query->columns->addSubSelect('orders o', 'orderCount');
$sub->columns->addSnippet('cnt')->code('COUNT(*)');
$sub->conditions->addEq('o.customerId', Identifier::fromString('c.id'));

$query->fromTable('customers c');
```

```sql
SELECT `c`.`id`, `c`.`name`, (
	SELECT COUNT(*) AS `cnt`
	FROM `orders` `o`
	WHERE
		(`o`.`customerId` = `c`.`id`)
		) AS `orderCount`
FROM `customers` `c`
```

Note the `Identifier::fromString('c.id')` on the right-hand side of the correlated condition:
a plain string `'c.id'` there would be treated as just another field reference, which happens
to render the same way in this case, but using `Identifier` documents intent and is required
wherever the raw string would otherwise be ambiguous (see [Core concepts](core-concepts.md#identifier)).

Passing `null` as `$tableName` lets you build the sub-select's `FROM` yourself (e.g. as another
sub-select, or a raw snippet) instead of a plain table.

## `addExistsSubSelect()` — `EXISTS(...)` as a column

Same as `addSubSelect()`, but wraps the sub-query in `EXISTS(...)` instead of `(...)` — useful
for boolean "has related rows" flag columns:

```php
$existsSub = $query->columns->addExistsSubSelect('orders o2', 'hasOrders');
$existsSub->conditions->addEq('o2.customerId', Identifier::fromString('c.id'));
```

```sql
EXISTS(
	SELECT *
	FROM `orders` `o2`
	WHERE
		(`o2`.`customerId` = `c`.`id`)
		) AS `hasOrders`
```

## Column direction (`ORDER BY`)

The `$direction` argument (`'ASC'` / `'DESC'`) on `addColumn()` / `addSnippet()` /
`addSubSelect()` is what powers `OrderCollection`, described in
[Grouping, ordering and LIMIT](group-order-limit.md) — `OrderCollection` extends
`ColumnCollection` and adds `addAscColumn()` / `addDescColumn()` (and the `Asc`/`Desc` variants
of the other `add*` methods) as convenience wrappers around it.

Continue with [Conditions](conditions.md).
