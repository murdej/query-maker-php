# Joins

`$query->joins` is a `JoinCollection`. Types are `Join::Type_Inner`, `Join::Type_Left`,
`Join::Type_Right`, `Join::Type_Outer` (or just pass the raw string, it's upper-cased when
rendered).

```php
public function add(string $type, Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
public function addLeft(Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
public function addInner(Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
```

## Joining a table, with an explicit condition

```php
use Murdej\QueryMaker\Common\ConditionCollection;
use Murdej\QueryMaker\Common\Identifier;

$on = new ConditionCollection('and', $query);
$on->addEq('c.id', Identifier::fromString('o.customerId'));
$query->joins->addLeft('customers c', $on);
```

## Joining a table, with the `"field:table.field"` shorthand

When `$on` is a plain string, it's parsed for you into an equi-join condition. Three shapes are
recognized:

**1. `"field"` — same column name on both sides**

```php
$query->joins->add('Inner', 'order_items oi', 'orderId');
```

```sql
INNER JOIN `order_items` `oi`
	ON
		(`oi`.`orderId` = `o`.`orderId`)
```

(`o` here is the alias of the query's own `FROM` table — the join condition always resolves the
right-hand side against the query's `$from` alias.)

**2. `"field:field"` — different column name on each side, both local**

```php
$query->joins->addLeft('bar b', 'id:barId');
```

```sql
LEFT JOIN `bar` `b`
	ON
		(`b`.`id` = `t`.`barId`)
```

**3. `"field:table.field"` — right-hand side fully qualified**

```php
$query->joins->addLeft('customers c', 'id:o.customerId');
```

```sql
LEFT JOIN `customers` `c`
	ON
		(`c`.`id` = `o`.`customerId`)
```

For anything more complex than a single equality (composite keys, extra filters in the `ON`
clause), build a `ConditionCollection` explicitly as in the first example — its full API
(`addEq`, `addIn`, `addSubconditions`, ...) is available for `ON` clauses too.

## Joining a sub-query

```php
public function addSubquery(string $type, string $subQueryAlias, string|ConditionCollection|Condition $on): Join
```

```php
$join = $query->joins->addSubquery('LEFT', 'lastOrder', 'customerId');
$join->subQueryAlias = 'lastOrder';
$join->from->fromTable('orders o2');
$join->from->columns->addColumn('o2.customerId');
$join->from->columns->addSnippet('maxDate')->code('MAX(')->identifier('o2.createdAt')->code(')');
$join->from->groups->addColumn('o2.customerId');

$query->columns->addColumn('o.id');
$query->columns->addColumn('lastOrder.maxDate');
$query->fromTable('orders o');
```

```sql
SELECT `o`.`id`, `lastOrder`.`maxDate`
FROM `orders` `o`
LEFT JOIN  (
	SELECT `o2`.`customerId`, MAX(`o2.createdAt`) AS `maxDate`
	FROM `orders` `o2`GROUP BY `o2`.`customerId`
	) `lastOrder`
	ON
		(`customerId` = `o`.`customerId`)
```

`addSubquery()` returns the `Join`; its `->from` is the sub-query's `DataSource`, configured
exactly like a top-level query. `$on` is parsed the same way as for a table join — here `'customerId'`
resolves to `` `` (join alias) `.customerId = `o`.customerId `` once you set the alias (see the
note below), so it reads as "sub-query's `customerId` equals the outer query's `customerId`."

> **Set `$join->subQueryAlias` explicitly after calling `addSubquery()`.** The second
> parameter (named `$subQueryAlias` in the signature) is currently *not* copied into the
> rendered alias — without the extra assignment shown above, the sub-query is emitted with an
> empty alias (`` `` ``), which is invalid SQL and also breaks the `.customerId` reference in
> the outer query. See
> [Known limitations](known-limitations.md#addsubquery-alias-parameter-is-not-applied).

Continue with [Subqueries and EXISTS](subqueries.md).
