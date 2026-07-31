# Core concepts

## `Query` and `DataSource`

`Query` (`Murdej\QueryMaker\Common\Query`) is the object you build and pass to a maker. It
extends `DataSource`, which holds everything a single `SELECT` needs:

| Property | Type | Meaning |
|---|---|---|
| `$columns` | `ColumnCollection` | the `SELECT` list |
| `$from` | `Identifier\|DataSource\|null` | a table (`Identifier`) or a sub-select (`DataSource`) |
| `$alias` | `?string` | alias for `$from` |
| `$joins` | `JoinCollection` | `JOIN` clauses |
| `$conditions` | `ConditionCollection` | `WHERE` clause |
| `$groups` | `ColumnCollection` | `GROUP BY` |
| `$havings` | `ConditionCollection` | `HAVING` clause |
| `$orders` | `OrderCollection` | `ORDER BY` |
| `$limitFrom` / `$limitCount` | `?int` | `LIMIT` |
| `$snippet` | `?Snippet` | if set, overrides everything above with a raw snippet (see [Raw SQL snippets](raw-sql-snippets.md)) |

`Query` adds two things `DataSource` doesn't have, because they only make sense at the top level:

| Property | Type | Meaning |
|---|---|---|
| `$ctes` | `CteCollection` | `WITH ...` clauses, see [CTE](cte.md) |
| `$unions` | `UnionCollection` | `UNION` / `UNION ALL`, see [Unions](unions.md) |
| `$prepareObtainCount` | `bool` | adds `SQL_CALC_FOUND_ROWS` to the `SELECT`, see [Grouping, ordering and LIMIT](group-order-limit.md#sql_calc_found_rows) |

A plain `DataSource` (without CTEs/unions) is what you get back whenever you open a nested
scope: a sub-select column, an `EXISTS` sub-query, a join's sub-query, or a CTE body. It has the
exact same fluent API as `Query` (columns, conditions, joins, groups, havings, orders, limit),
because internally it *is* the same class.

```php
use Murdej\QueryMaker\Common\Query;

$query = new Query();
$query->fromTable('orders o');           // $query->from = Identifier('orders', alias 'o')
$sub = $query->fromSubSelect('recent');  // $query->from = a nested DataSource, aliased 'recent'
$sub->fromTable('orders')->conditions->addEq('year', 2026);
```

## `Maker`, `BaseMaker`, `MariaDB`

A maker turns a `Query` into SQL. `IMaker` is the interface; `BaseMaker` implements all the
dialect-agnostic rendering logic (columns, joins, conditions, CTEs, unions, fulltext...) and
delegates only identifier-escaping to the concrete subclass:

```php
namespace Murdej\QueryMaker\Maker;

class MariaDB extends BaseMaker
{
    function escapeIdentifier(string $identifier)
    {
        return $identifier === "*" ? $identifier : "`$identifier`";
    }
}
```

Rendering a query is one call:

```php
$maker = new MariaDB();
$result = $maker->makeQuery($query); // QueryAndValues
```

> **Use a fresh `Maker` instance per query.** A `BaseMaker` accumulates the rendered SQL in an
> internal `Snippet` (`$maker->content`) that is **not** reset between calls. Calling
> `makeQuery()` twice on the same `MariaDB` instance concatenates the second query's SQL onto
> the first's. Always do `new MariaDB()` (or any maker) right before each `makeQuery()` call, as
> every example in this documentation does.

### `QueryAndValues`

```php
namespace Murdej\QueryMaker\Maker;

class QueryAndValues
{
    public string $query = "";
    public array $values = [];

    public function add(QueryAndValues $queryAndValues) { /* merges query + values */ }
}
```

`$query` is SQL with `?` placeholders; `$values` are the corresponding bound parameters in
order. Pass them straight to your database layer:

```php
// PDO
$stmt = $pdo->prepare($result->query);
$stmt->execute($result->values);

// mysqli via mysqli_stmt (types string built from $result->values if needed)
$stmt = $mysqli->prepare($result->query);
$stmt->bind_param(str_repeat('s', count($result->values)), ...$result->values);
$stmt->execute();
```

### Writing a new maker

To support another dialect (e.g. PostgreSQL), extend `BaseMaker` and implement
`escapeIdentifier()` with that dialect's quoting rules; override individual `make*()` methods
(`makeColumn`, `makeCondition`, `makeJoin`, ...) only where the dialect actually differs.

## `Identifier`

`Identifier` represents a table or column name, optionally with an alias, and knows how to
render itself both as a **field reference** (`alias.name`) and as a **table reference**
(`name alias`):

```php
use Murdej\QueryMaker\Common\Identifier;

Identifier::fromString('u.name');      // Identifier(name: 'name', alias: 'u')  → field: `u`.`name`
Identifier::fromString('users u');     // Identifier(name: 'users', alias: 'u') → table: `users` `u`
Identifier::fromString('users AS u');  // same as above
Identifier::fromString('users');       // Identifier(name: 'users')
```

Anywhere the API accepts `string|Identifier`, a plain string is parsed with
`Identifier::fromString()` for you — you only need to build `Identifier` explicitly when you
want to reference a column that belongs to an outer query from inside a sub-select (a
correlated sub-query), since a bare string would otherwise be resolved relative to the
sub-select itself:

```php
$sub->conditions->addEq('o.customerId', Identifier::fromString('c.id'));
```

## `Snippet`, `SnippetChunk`

Everything eventually gets rendered through `Snippet`, a small builder of typed chunks:

- `->code($sql)` — raw SQL text, pasted verbatim (no escaping).
- `->identifier($name)` — an identifier, escaped by the maker (backticks for MariaDB).
- `->value($mixed)` — a bound value, rendered as `?` with `$mixed` appended to `$values`.

You rarely need `Snippet` directly for everyday queries — column/condition helper methods build
it for you — but it's the escape hatch for anything the fluent API doesn't cover. See
[Raw SQL snippets](raw-sql-snippets.md).

Continue with [Columns](columns.md).
