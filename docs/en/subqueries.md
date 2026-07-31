# Subqueries and `EXISTS`

QueryMaker supports sub-queries in every position SQL allows them. Because a nested scope is
always just another `DataSource` with the same fluent API as `Query`, there's nothing special
to learn beyond "call the method that opens the scope, then build it like a normal query."

| Position | How to open it | Documented in |
|---|---|---|
| `FROM (subquery) alias` | `$query->fromSubSelect($alias)` | below |
| A join target | `$query->joins->addSubquery(...)` | [Joins](joins.md#joining-a-sub-query) |
| A scalar column | `$query->columns->addSubSelect(...)` | [Columns](columns.md) |
| An `EXISTS(...)` column | `$query->columns->addExistsSubSelect(...)` | [Columns](columns.md) |
| `WHERE EXISTS (...)` | `$query->conditions->addExists()` | [Conditions](conditions.md#exists--correlated-sub-query-condition) |
| `WHERE col IN (subquery)` | manual `Condition` (no fluent helper yet) | [Conditions](conditions.md#column-in-sub-query) |
| CTE body (`WITH alias AS (...)`) | `$query->ctes->add($alias, ...)` | [CTE](cte.md) |
| `UNION` branch | `$query->unions->add(...)` | [Unions](unions.md) |

## `FROM (subquery)`

```php
$query = new Query();
$sq = $query->fromSubSelect('recent');
$sq->fromTable('orders o')->conditions->addEq('o.createdAt', '2026-01-01');
```

```sql
SELECT *
FROM (
	SELECT *
	FROM `orders` `o`
	WHERE
		(`o`.`createdAt` = ?)
		)
```

Note the outer alias (`'recent'` here) currently has no effect on the rendered `FROM (...)`
clause — plain sub-selects in `FROM` are emitted without a trailing `AS alias`. If the outer
query needs to reference sub-select columns by that alias, add the alias to the SQL yourself via
a raw snippet, or restructure the query as a join (see [Joins](joins.md#joining-a-sub-query),
which *does* apply its alias once set explicitly).

## Correlated sub-queries: referencing the outer query's columns

Inside any nested scope, a plain string like `'o.customerId'` is always resolved as a column of
*that* scope. To reference a column of the *outer* query from inside a sub-select/`EXISTS`/CTE,
wrap it in `Identifier::fromString()` explicitly:

```php
use Murdej\QueryMaker\Common\Identifier;

$sub = $query->columns->addSubSelect('orders o', 'orderCount');
$sub->columns->addSnippet('cnt')->code('COUNT(*)');
$sub->conditions->addEq('o.customerId', Identifier::fromString('c.id')); // c = outer query's alias
```

This is purely a documentation convention on your side — QueryMaker doesn't validate which
scope a column actually belongs to, it just renders whatever `Identifier`/string you give it at
that exact point in the SQL tree. Getting it wrong doesn't error; it silently produces SQL that
references the wrong table, so double-check correlated conditions against the printed query.

Continue with [Grouping, ordering and LIMIT](group-order-limit.md).
