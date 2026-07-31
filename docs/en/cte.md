# Common Table Expressions (`WITH`)

`$query->ctes` (a `CteCollection`, only available on `Query`, not on nested `DataSource`
scopes) builds `WITH alias AS (...)` clauses ahead of the main `SELECT`.

```php
public function add(string $alias, ?string $tableName = null): DataSource
```

```php
$cte = $query->ctes->add('activeCustomers', 'customers c');
$cte->columns->addColumn('c.id');
$cte->columns->addColumn('c.name');
$cte->conditions->addEq('c.active', 1);

$query->columns->addColumn('id');
$query->columns->addColumn('name');
$query->fromTable('activeCustomers');
```

```sql
WITH
	`activeCustomers` AS (
		SELECT `c`.`id`, `c`.`name`
		FROM `customers` `c`
		WHERE
			(`c`.`active` = ?)
			)
SELECT `id`, `name`
FROM `activeCustomers`
```

Add multiple CTEs by calling `->add()` again — they're comma-separated inside a single `WITH`:

```php
$query->ctes->add('cfoo', 'foo f')->columns->addColumn('f.ggg');
$query->ctes->add('cfoo2', 'foo2 f')->columns->addColumn('f.www');
```

```sql
WITH
	`cfoo` AS (
		SELECT `f`.`ggg`
		FROM `foo` `f`
	),
	`cfoo2` AS (
		SELECT `f`.`www`
		FROM `foo2` `f`
	)
```

`add()` returns a `DataSource`, so a CTE body has the full fluent API (joins, conditions,
grouping, its own nested sub-queries...) — a CTE is not limited to a simple `SELECT ... FROM
table`. Pass `null` as `$tableName` if you want to set `->from` yourself (e.g. to a sub-select).

`$query->ctes` can then be referenced from `$query->from`, from joins, or from anywhere else a
table name is expected — QueryMaker doesn't validate the reference, it just emits whatever table
name string you give it, same as a real table.

Continue with [Unions](unions.md).
