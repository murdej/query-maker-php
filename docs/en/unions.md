# `UNION` / `UNION ALL`

`$query->unions` (a `UnionCollection`, only on `Query`) attaches one or more `UNION` branches
after the main query.

```php
public function add(string $type = Union::Type_Distinct, ?string $tableName = null): DataSource
public function addAll(?string $tableName = null): DataSource
public function addDistinct(?string $tableName = null): DataSource
```

```php
$query = new Query();
$query->columns->addColumns('id', 'name');
$query->fromTable('customers');
$query->conditions->addEq('active', 1);

$u = $query->unions->addAll();
$u->columns->addColumns('id', 'name');
$u->fromTable('archived_customers');
```

```sql
(SELECT `id`, `name`
FROM `customers`
WHERE
	(`active` = ?)
	)
UNION (
	SELECT `id`, `name`
	FROM `archived_customers`
)
```

- `addAll()` → `UNION ALL`; `addDistinct()` (or `add()` with no argument) → plain `UNION`
  (deduplicated).
- Once any union is attached, the main query is automatically wrapped in parentheses, so
  operator precedence between the main query and its `LIMIT`/`ORDER BY` is unambiguous.
- Add as many branches as you need — each call to `add()`/`addAll()`/`addDistinct()` appends
  another `UNION (...)` block, each with its own `DataSource` (own columns, `FROM`, conditions,
  etc.).

Continue with [Fulltext search](fulltext-search.md).
