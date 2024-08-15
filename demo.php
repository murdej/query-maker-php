<?php

header('Content-type: text/plain');

use Murdej\QueryMaker\Common\Condition;
use Murdej\QueryMaker\Common\Identifier;
use Murdej\QueryMaker\Common\Join;
use Murdej\QueryMaker\Common\Query;

require __DIR__ . '/vendor/autoload.php';

$query = new Query();
$query->columns->addColumn('tt.name');
$query->columns->addSnippet('qq')->code('QUOQI(')->identifier("tt.name")->code(")");
$query->columns->addSubSelect("tab2", "sss")->columns->addColumn("bubu");
$query->columns->addColumns("foo", "f.bar", "eee AS q", "eee q1", "t.wx xc");
$sq = $query->fromSubSelect("tt");
$sq->fromTable("foo f", "t")->conditions->addEq("f.ee", 1240);
$query->joins->add(Join::Type_Left, "bar b", "id:barId");
$query->conditions->add("a", Condition::Operator_in, [1,2,45]);
$query->ctes->add("cfoo", "foo f")->columns->addColumn("f.ggg");
$query->ctes->add("cfoo2", "foo2 f")->columns->addColumn("f.www");
$query->orders->addAscColumn("numOrder");
$query->joins->addSubquery("INNER", "jsq", "qqId")->from->fromTable("jsqtable dd");
$query->conditions
	->addRange(10, Identifier::fromString('min'), Identifier::fromString('max'))
	->addRange(10, Identifier::fromString('min'), Identifier::fromString('max'), true)
	->addRange(10, Identifier::fromString('min'), Identifier::fromString('maxi'), false, true, true)
	->addRange(10, Identifier::fromString('mini'), Identifier::fromString('max'), false, false, false)
	->addRange(null, Identifier::fromString('min'), Identifier::fromString('max'), true, false, true)
	->addRange(Identifier::fromString('val'), 10, null, true)
	->addRange(Identifier::fromString('val'), null, 10, true)
	->addIn('empty', [])
	->addIn('null', [null])
	->addIn('any', [42])
	->addIn('anyNull', [42, 43, null])
	->addMultiIn(['empty_multi_in_a', 'b'], [])
	;

$maker = new \Murdej\QueryMaker\Maker\MariaDB();
$res = $maker->makeQuery($query);

echo $res->query;
echo "\n";
print_r($res->values);