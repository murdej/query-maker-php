# Fulltext search (`MATCH ... AGAINST`)

QueryMaker can render MySQL/MariaDB fulltext search expressions via the `Fulltext` value
object:

```php
namespace Murdej\QueryMaker\Common;

class Fulltext
{
    public function __construct(
        public string $searchTerm,
        public array $columns,
        public string $mode = self::Mode_Natural,
    ) {}

    public const string Mode_Natural = 'IN NATURAL LANGUAGE MODE';
    public const string Mode_NaturalQueryExpansion = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';
    public const string Mode_Boolean = 'IN BOOLEAN MODE';
    public const string Mode_QueryExpansion = 'WITH QUERY EXPANSION';
}
```

> **Use `Fulltext` directly, as shown below.** The higher-level convenience wrappers
> (`ConditionCollection::addFulltext()`, `OrderCollection::addFulltext()`, and
> `DataSource::addFulltextConditionAndOrder()`) currently throw a `TypeError` for every input
> shape — they are broken in the current version. See
> [Known limitations](known-limitations.md#fulltext-convenience-methods-are-broken) for details.
> The pattern below does not use them and works correctly.

## Condition, relevance column, and ordering by relevance

A `Fulltext` object can be used as a condition operand directly, and/or embedded in a column
snippet to expose the match score — build it once and reuse it in both places so the search
term is only bound once per distinct use:

```php
use Murdej\QueryMaker\Common\Fulltext;
use Murdej\QueryMaker\Common\Condition;

$fulltext = new Fulltext('database performance', ['title', 'body'], Fulltext::Mode_Natural);

$query = new Query();
$query->columns->addColumn('id');
$query->columns->addColumn('title');
$query->columns->addSnippet('relevance')->add($fulltext);
$query->fromTable('articles');
$query->conditions->conds[] = new Condition(null, $fulltext);
$query->orders->addDescColumn('relevance');
```

```sql
SELECT `id`, `title`, MATCH(`title`, `body`) AGAINST(? IN NATURAL LANGUAGE MODE) AS `relevance`
FROM `articles`
WHERE
	(MATCH(`title`, `body`) AGAINST(? IN NATURAL LANGUAGE MODE))
	ORDER BY `relevance` DESC
```

```php
print_r($result->values); // ['database performance', 'database performance']
```

- Pushing a `new Condition(null, $fulltext)` directly onto `$query->conditions->conds[]` is
  needed because there's no `addFulltext()`-style fluent wrapper that works today — `conds` is a
  public array precisely to allow this. It renders as a bare `MATCH(...) AGAINST(...)`
  condition, true when the row matches.
- Only `WHERE`-position `MATCH ... AGAINST` (as used above) participates in MySQL's fulltext
  index lookup for query planning; the copy used as a `SELECT` column is evaluated again purely
  to expose the relevance score for `ORDER BY` — this mirrors the standard MySQL fulltext
  pattern of repeating the `MATCH` expression in both places.

## Search modes

Pass the mode as the third constructor argument:

```php
new Fulltext($term, ['title', 'body'], Fulltext::Mode_Boolean);              // IN BOOLEAN MODE
new Fulltext($term, ['title', 'body'], Fulltext::Mode_NaturalQueryExpansion); // IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
new Fulltext($term, ['title', 'body']);                                      // defaults to Mode_Natural
```

`Mode_Boolean` is what you want for user-facing search boxes that support `+required -excluded
"exact phrase"` syntax; `$searchTerm` is passed through as-is (still safely bound as a
parameter) — QueryMaker does not validate or sanitize boolean-mode operator syntax, so garbled
user input can produce a MySQL syntax error inside the `AGAINST(...)` clause. If you accept raw
boolean-mode queries from users, validate/escape special characters (`+ - > < ( ) ~ * "`)
yourself before constructing `Fulltext`.

Continue with [Raw SQL snippets](raw-sql-snippets.md).
