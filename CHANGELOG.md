# Changelog

All notable changes to this project will be documented in this file.

## [1.1.1]

### Fixed
- Implicit nullable parameters (`string $alias = null` → `?string $alias = null`) in `DataSource::fromSubSelect()` and `Snippet::value()` — deprecation since PHP 8.4
- Added `"php": ">=8.3"` constraint to `composer.json` to reflect use of typed class constants

## [1.1.0] - 2025-12-02

### Added
- Fulltext search support via `Fulltext` class with four search modes: `IN NATURAL LANGUAGE MODE`, `IN BOOLEAN MODE`, `IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION`, `WITH QUERY EXPANSION`
- `ConditionCollection::addFulltext()` — add a fulltext `MATCH ... AGAINST` condition
- `DataSource::addFulltextConditionAndOrder()` — add fulltext condition and ordering in one call
- `FulltextSnipperFactory` — factory for building fulltext search snippets
- `Union` and `UnionCollection` — support for `UNION` and `UNION ALL` queries
- `Query::$unions` — union queries attached to the main query
- `BaseMaker::makeUnion()` / `makeUnions()` — rendering of UNION clauses
- `BaseMaker::makeFulltext()` — rendering of MATCH/AGAINST expressions
- `QueryAndValues::add()` — merge two `QueryAndValues` instances

## [1.0.1] - 2024-10-03

### Changed
- `minimum-stability` set to `stable` in `composer.json`

## [1.0] - 2024-10-03

### Added
- `ConditionCollection::addNotEq()` — add a `!=` condition
- `ConditionCollection::addNotIn()` — add a `NOT IN (...)` condition
- `ConditionCollection::addMulti()` — add multiple conditions from an associative array using a concise DSL:
  - `'column' => value` → `column = value`
  - `'!column' => value` → `column != value`
  - `'column' => [...]` → `column IN (...)`
  - `'!column' => [...]` → `column NOT IN (...)`
  - `'column' => null` → `column IS NULL`
  - `'!column' => null` → `column IS NOT NULL`
  - `'SQL code'` (integer key) → raw SQL snippet
- `ColumnCollection::addPrefixed()` — add multiple columns from a table with a shared alias prefix
- `Column` — parse `ASC` / `DESC` direction and `AS alias` directly from a column string

## [0.1.0] - 2024-08-15

### Added
- Initial release
- `Query` / `DataSource` — fluent query builder supporting `SELECT`, `FROM`, `JOIN`, `WHERE`, `HAVING`, `GROUP BY`, `ORDER BY`, `LIMIT`
- `ConditionCollection` — conditions with `AND` / `OR` grouping; operators: `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`, sub-query `IN`, `EXISTS`
- `ColumnCollection` / `Column` — column list with alias support
- `OrderCollection` — ordering columns extending `ColumnCollection`
- `JoinCollection` / `Join` — `INNER`, `LEFT`, `RIGHT` joins; supports sub-query joins
- `CteCollection` / `Cte` — Common Table Expressions (`WITH ...`)
- `Snippet` / `SnippetChunk` — low-level SQL fragment builder with typed chunks (code, identifier, value)
- `Identifier` — wraps a table/field name and generates escaped snippets
- `BaseMaker` / `IMaker` — abstract SQL renderer
- `MariaDB` — concrete renderer for MariaDB / MySQL
- `NetteActiveRow` bridge — helpers for integrating with Nette Database `ActiveRow`
- `demo.php` — usage examples
