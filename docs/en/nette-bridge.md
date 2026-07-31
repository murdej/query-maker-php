# Nette Database bridge

`Murdej\QueryMaker\Bridge\NetteActiveRow` is optional glue code for projects using
[Nette Database](https://doc.nette.org/en/database). It is **not** wired up by
`composer.json`'s `require` section — it references `Nette\Database\*` and
`Murdej\ActiveRow\*` classes that this package does not itself depend on, so you must install
Nette Database (and, for `createEntitySelect()`, whatever package provides
`Murdej\ActiveRow\DBRepository`/`DBSqlQuery`) yourself before using it:

```bash
composer require nette/database
```

```php
namespace Murdej\QueryMaker\Bridge;

class NetteActiveRow
{
    public static function createEntitySelect(DBRepository $repository, Query $query): DBSqlQuery;
    public static function createRowSelect(Explorer $database, Query $query): ResultSet;
    public static function createRowArray(Explorer $database, Query $query): array;
}
```

All three build the query with `MariaDB` internally, then hand `$result->query` and
`$result->values` to the corresponding Nette Database API (`Explorer::query()`'s
positional-placeholder form, or `Murdej\ActiveRow`'s `DBRepository::newSqlQuery()->code(...)`).

```php
use Murdej\QueryMaker\Bridge\NetteActiveRow;
use Murdej\QueryMaker\Common\Query;

$query = new Query();
$query->fromTable('users');
$query->conditions->addEq('active', 1);

// as a Nette ResultSet, iterable as ActiveRow objects
$resultSet = NetteActiveRow::createRowSelect($database, $query); // $database: Nette\Database\Explorer

// as a plain array of rows, fully materialized
$rows = NetteActiveRow::createRowArray($database, $query);

// as a Murdej\ActiveRow DBSqlQuery, for a specific repository/entity
$sqlQuery = NetteActiveRow::createEntitySelect($repository, $query); // $repository: Murdej\ActiveRow\DBRepository
```

There's nothing QueryMaker-specific to learn beyond this — the bridge exists purely so you don't
have to repeat `(new MariaDB())->makeQuery($query)` and the values-spreading call at every call
site. If your project doesn't use Nette Database, ignore this class entirely; the rest of
QueryMaker has no dependency on it.

Continue with [Known limitations](known-limitations.md).
