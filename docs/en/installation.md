# Installation

QueryMaker requires **PHP 8.3+** (it uses typed class constants). It has no runtime dependencies.

```bash
composer require murdej/query-maker-php
```

Autoloading follows PSR-4, mapped to the `Murdej\QueryMaker\` namespace:

```json
{
    "autoload": {
        "psr-4": {
            "Murdej\\QueryMaker\\": "src/QueryMaker/"
        }
    }
}
```

## Namespaces

| Namespace | Contents |
|---|---|
| `Murdej\QueryMaker\Common` | `Query`, `DataSource`, columns, conditions, joins, CTEs, unions, `Identifier`, `Snippet` — everything you use to build a query |
| `Murdej\QueryMaker\Maker` | `IMaker`, `BaseMaker`, `MariaDB`, `QueryAndValues` — everything that renders a `Query` to SQL |
| `Murdej\QueryMaker\Factory` | `FulltextSnipperFactory` — internal helper used by the fulltext convenience methods |
| `Murdej\QueryMaker\Bridge` | `NetteActiveRow` — optional glue code for Nette Database (see [Nette Database bridge](nette-bridge.md)) |

Only `MariaDB` (works for MySQL and MariaDB) ships as a concrete renderer today. Other
databases can be supported by extending `BaseMaker` and implementing `escapeIdentifier()`
(see [Core concepts](core-concepts.md#writing-a-new-maker)).

## Minimal working example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Murdej\QueryMaker\Common\Query;
use Murdej\QueryMaker\Maker\MariaDB;

$query = new Query();
$query->fromTable('users');

$maker = new MariaDB();
$result = $maker->makeQuery($query);

echo $result->query;
```

```sql
SELECT *
FROM `users`
```

Continue with [Core concepts](core-concepts.md) to see how `Query` is put together.
