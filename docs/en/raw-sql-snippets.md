# Raw SQL snippets

Every fluent helper (`addColumn`, `addEq`, `addColumns`, ...) is a thin wrapper that ultimately
builds a `Snippet`. When the fluent API doesn't cover what you need, build a `Snippet` yourself.

## `Snippet` builder methods

```php
->code(string $sql): self         // raw SQL, pasted verbatim — no escaping, no binding
->identifier(string $name): self  // an identifier, escaped by the maker (backticks for MariaDB)
->value(mixed $value): self       // a bound parameter — rendered as `?`, value appended to $values
->add(Snippet|SnippetChunk|Fulltext|null $x): self  // append another snippet/chunk/Fulltext
->paste(Snippet $snippet): self   // splice another snippet's chunks in directly
->beginList(string $sep): self / ->endList(): self  // auto-insert $sep between successive ->code()/->identifier()/->value() calls
->endl(int $indentChange = 0): self  // formatting only — newline + indentation, purely cosmetic
```

Every method returns `$this`, so calls chain.

## An entire query as a raw snippet

`DataSource::useSnippet()` bypasses the whole column/condition/join machinery and lets you hand
-write the SQL for that scope, while still getting parameter binding for any `->value()` calls:

```php
$query = new Query();
$query->useSnippet()
    ->code('SELECT ')->identifier('id')->code(', COUNT(*) c FROM ')->identifier('log')
    ->code(' WHERE created > ')->value('2026-01-01')
    ->code(' GROUP BY ')->identifier('id');

$maker = new MariaDB();
$result = $maker->makeQuery($query);
```

```sql
SELECT `id`, COUNT(*) c FROM `log` WHERE created > ? GROUP BY `id`
```

```php
print_r($result->values); // ['2026-01-01']
```

Once `$query->snippet` is set, `$query->columns`/`$query->conditions`/`$query->joins`/etc. are
ignored entirely for that scope — `useSnippet()` is an escape hatch for one query (or
sub-query/CTE/join) at a time, not something you combine with the fluent builders in the same
scope.

## A computed column or condition expression

The common, non-escape-hatch use of `Snippet` — a `SELECT`/`WHERE` expression that isn't a
plain column reference:

```php
$query->columns->addSnippet('total')
    ->code('SUM(')->identifier('amount')->code(')');

$query->conditions->addSnippet()
    ->code('YEAR(')->identifier('createdAt')->code(') = ')->value(2026);
```

```sql
SUM(`amount`) AS `total`
...
YEAR(`createdAt`) = ?
```

`ColumnCollection::addSnippet($alias)` and `ConditionCollection::addSnippet()` both return the
`Snippet` for you to fill in — there's no need to construct `Snippet` yourself for this case.

## `Identifier`

`Identifier` is the typed alternative to a raw `->identifier()` call when you specifically mean
"a column, possibly qualified with a table alias" rather than an arbitrary escaped string — see
[Core concepts](core-concepts.md#identifier). It knows two renderings:

```php
Identifier::fromString('u.name')->fieldSnippet(); // `u`.`name`  — for SELECT/WHERE
Identifier::fromString('users u')->tableSnippet(); // `users` `u` — for FROM/JOIN
```

## Building a list with a separator

`beginList()`/`endList()` insert the separator automatically between items, so you don't have to
track "is this the first item" yourself:

```php
$s = new Snippet();
$s->code('(')->beginList(', ');
foreach (['a', 'b', 'c'] as $col) {
    $s->identifier($col);
}
$s->endList()->code(')');
```

```sql
(`a`, `b`, `c`)
```

This is exactly how `addMultiIn()` builds its column-tuple lists internally.

Continue with [Nette Database bridge](nette-bridge.md).
