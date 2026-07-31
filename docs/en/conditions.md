# Conditions (`WHERE` / `HAVING`)

`$query->conditions` and `$query->havings` are both `ConditionCollection` instances (rendered
as `WHERE` and `HAVING` respectively — same API for both). Every `add*()` method returns
`$this`, so calls chain, and every condition you add is combined with the collection's boolean
operator (`AND` by default at the top level).

Wherever a method accepts a "field" argument, you can pass:

- a plain string (`'status'`, `'o.total'`) — resolved as a field with `Identifier::fromString()`;
- an `Identifier` — for referencing a column of an *outer* query from inside a sub-query;
- a `Snippet`/`SnippetChunk` — for a computed expression.

Values (the right-hand side of a comparison) are always bound as parameters unless you pass an
`Identifier`/`Snippet` explicitly (comparing two columns) or a raw array (for `IN`).

## Equality and comparison

```php
$query->conditions->addEq('active', 1);
$query->conditions->addNotEq('status', 'deleted');
$query->conditions->add('price', Condition::Operator_gt, 100);
```

```sql
WHERE
	(`active` = ?)
	 and (`status` != ?)
	 and (`price` > ?)
```

`add(mixed $a, string $operator, mixed $b, mixed $c = null)` is the general-purpose form;
`$operator` is one of the `Condition::Operator_*` constants: `eq` (`=`), `neq` (`!=`), `gt`
(`>`), `lt` (`<`), `gte` (`>=`), `lte` (`<=`), `like` (`LIKE`), plus the compound operators
covered below.

## `LIKE`

```php
$query->conditions->addLike('name', '%phone%');

// same LIKE pattern against several columns, OR'd together — see "Grouping conditions" below
$query->conditions->addMultiLike('%sale%', 'tag', 'badge');
```

```sql
WHERE
	(`name` LIKE ?)
	 and (`tag` LIKE ?)
	 and (`badge` LIKE ?)
```

Note `addMultiLike()` adds one `LIKE` condition per field, **AND**-ed together at the top level
(it does not itself group them with `OR`) — group them explicitly if you want "any of these
columns matches" (see below).

## `IN` / `NOT IN`

```php
$query->conditions->addIn('tag', ['sale', 'new']);
$query->conditions->addNotIn('id', [1, 2, 3]);
```

```sql
WHERE
	(`tag` IN (?, ?))
	 and (`id` NOT IN (?, ?, ?))
```

`null` values inside the array are handled specially — they turn into an `OR ... IS NULL`
branch instead of a broken `IN (NULL)`:

```php
$query->conditions->addIn('category', [1, 2, null]);
```

```sql
WHERE
	(`category` IN (?, ?) OR `category` IS NULL)
```

An empty array short-circuits to an always-false condition (with the field name kept as a
comment, for debuggability), instead of generating invalid `IN ()` SQL:

```php
$query->conditions->addIn('id', []);
```

```sql
WHERE
	(1=0 /* `id` */)
```

> `addNotIn()`/`Condition::Operator_notIn` currently render as `` `col` NOTIN (...) `` — missing
> the space between `NOT` and `IN`. See
> [Known limitations](known-limitations.md#not-in-renders-without-a-space-notin).

## `IS NULL` / `IS NOT NULL`

```php
$query->conditions->addIsNull('deletedAt');
$query->conditions->addNotNull('publishedAt');
```

```sql
WHERE
	(`deletedAt` IS NULL )
	 and (`publishedAt` NOT NULL )
```

## `addMulti()` — build several conditions from an associative array

A concise DSL for the common case of turning a filter array (e.g. from a search form) into
conditions:

```php
$query->conditions->addMulti([
    'categoryId' => 5,          // categoryId = 5
    '!status' => 'deleted',     // status != 'deleted'
    'tag' => ['sale', 'new'],   // tag IN ('sale', 'new')
    '!archived' => [1, 2],      // archived NOT IN (1, 2)
    'deletedAt' => null,        // deletedAt IS NULL
    '!publishedAt' => null,     // publishedAt NOT NULL
    'stock > 0',                // raw SQL, added as-is (integer array key)
]);
```

```sql
WHERE
	(`categoryId` = ?)
	 and (`status` != ?)
	 and (`tag` IN (?, ?))
	 and (`archived` NOTIN (?, ?))
	 and (`deletedAt` IS NULL )
	 and (`publishedAt` NOT NULL )
	 and (stock > 0)
```

Rules, by value shape:

| Key | Value | Generated |
|---|---|---|
| `'column'` | scalar | `column = value` |
| `'!column'` | scalar | `column != value` |
| `'column'` | array | `column IN (...)` |
| `'!column'` | array | `column NOT IN (...)` |
| `'column'` | `null` | `column IS NULL` |
| `'!column'` | `null` | `column IS NOT NULL` |
| *(integer key)* | `'raw SQL'` | pasted verbatim, unescaped |

Skip any key you don't want to filter by (e.g. build the array conditionally) rather than
passing `null` for "no filter" — `null` always means `IS NULL`.

## `addMultiIn()` — row-value `IN`

Filters on a *combination* of columns matching one of several row tuples — the multi-column
equivalent of `IN`:

```php
$query->conditions->addMultiIn(
    ['year', 'month'],
    [[2026, 1], [2026, 2]],
);
```

```sql
(`year`, `month`) IN ((?, ?), (?, ?))
```

Values can also be given as associative rows keyed by column name instead of positional arrays;
an empty `$values` array again short-circuits to `1=0` (with the column list as a comment)
instead of invalid SQL.

## `addRange()` — inclusive/exclusive range, with optional open ends

```php
public function addRange(
    mixed $val, mixed $min, mixed $max,
    bool $nullIsUnlimited = false,
    bool $includingMin = true,
    bool $includingMax = true,
): ConditionCollection
```

The common case — a value must fall within `[min, max]` (both columns):

```php
use Murdej\QueryMaker\Common\Identifier;

$query->conditions->addRange(150, Identifier::fromString('minPrice'), Identifier::fromString('maxPrice'));
```

```sql
WHERE
	((
			(? >= `minPrice`)
			 AND (? <= `maxPrice`)
			))
```

- `$val === null` with `$nullIsUnlimited = false` (the default) makes the whole condition
  always false — a `null` filter value means "match nothing", not "match everything".
- `$val === null` with `$nullIsUnlimited = true` skips the condition entirely (matches
  everything).
- `$includingMin` / `$includingMax` switch each bound between `>=`/`<=` (inclusive, default)
  and `>`/`<` (exclusive).
- When `$min`/`$max` is an `Identifier` (a column, e.g. a "valid from"/"valid to" column on the
  row being filtered) **and** `$nullIsUnlimited` is `true`, that bound becomes
  `col IS NULL OR val >= col` — i.e. a `NULL` bound column means that side is unlimited:

```php
$query->conditions->addRange(10, Identifier::fromString('min'), Identifier::fromString('max'), true);
```

```sql
((
    ((`min` IS NULL ) OR (? >= `min`))
     AND ((`max` IS NULL ) OR (? <= `max`))
))
```

This is the pattern for "row is valid between `validFrom`/`validTo`, where a `NULL` bound means
no limit on that side."

## Grouping conditions (`AND`/`OR` nesting)

Every `ConditionCollection` has a boolean `operation` (`AND` or `OR`). Nest a sub-group with
`addSubconditions()`:

```php
use Murdej\QueryMaker\Common\ConditionCollection;

$query->conditions->addEq('active', 1);

$or = $query->conditions->addSubconditions(ConditionCollection::Operation_Or);
$or->addLike('name', '%phone%');
$or->addLike('description', '%phone%');
```

```sql
WHERE
	(`active` = ?)
	 and ((
			(`name` LIKE ?)
			 OR (`description` LIKE ?)
			))
```

Nest as deeply as you need — `addSubconditions()` returns another `ConditionCollection` with
the same full API.

## `EXISTS` — correlated sub-query condition

```php
$exists = $query->conditions->addExists();
$exists->fromTable('orders o');
$exists->conditions->addEq('o.customerId', Identifier::fromString('c.id'));
$exists->conditions->add('o.total', Condition::Operator_gt, 1000);
```

```sql
WHERE
	(EXISTS(
		SELECT *
		FROM `orders` `o`
		WHERE
			(`o`.`customerId` = `c`.`id`)
			 and (`o`.`total` > ?)
			))
```

`addExists()` returns a plain `DataSource` — build it exactly like a `Query`, including its own
columns/joins/conditions (the columns are irrelevant to `EXISTS` and can be left as `*`).

## `column IN (sub-query)`

There's no dedicated fluent helper for this yet, but `Condition` exposes the operator publicly
(`$conds` is a public array), so you can build one directly:

```php
use Murdej\QueryMaker\Common\Condition;
use Murdej\QueryMaker\Common\DataSource;
use Murdej\QueryMaker\Common\Identifier;

$cond = new Condition(Condition::Operator_inSubQuery, Identifier::fromString('c.id')->fieldSnippet());
$cond->subQuery = new DataSource($query);
$cond->subQuery->fromTable('orders o');
$cond->subQuery->columns->addColumn('o.customerId');
$cond->subQuery->conditions->addEq('o.total', 1000);
$query->conditions->conds[] = $cond;
```

```sql
WHERE
	(`c`.`id` IN (
		SELECT `o`.`customerId`
		FROM `orders` `o`
		WHERE
			(`o`.`total` = ?)
			))
```

## Raw condition snippet / always-false

`addSnippet()` on a `ConditionCollection` returns a `Snippet` you fill in yourself, for
anything the helpers above don't cover:

```php
$query->conditions->addSnippet()->code('YEAR(')->identifier('createdAt')->code(') = ')->value(2026);
```

`Condition::false()` builds a `0 = 1` condition — useful as a static "match nothing" fallback,
e.g. when a filter's allowed-values list turns out empty at runtime.

Continue with [Joins](joins.md).
