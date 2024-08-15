<?php

namespace Murdej\QueryMaker\Bridge;

use Murdej\ActiveRow\DBRepository;
use Murdej\ActiveRow\DBSqlQuery;
use Murdej\QueryMaker\Common\Query;
use Murdej\QueryMaker\Maker\MariaDB;
use Nette\Database\Explorer;
use Nette\Database\ResultSet;

class NetteActiveRow
{
    /**
     * Return as DBSqlQuery for concrete entity
     * @param DBRepository $repository
     * @param Query $query
     * @return DBSqlQuery
     */
    public static function createEntitySelect(DBRepository $repository, Query $query): DBSqlQuery
    {
        //todo: detect DB engine
        $qm = new MariaDB();
        $qav = $qm->makeQuery($query);

        return $repository->newSqlQuery()
            ->code($qav->query, ...$qav->values);
    }

    /*
     * Return as DBSqlQuery for generic Row
     */
    public static function createRowSelect(Explorer $database, Query $query): ResultSet
    {
        //todo: detect DB engine
        $qm = new MariaDB();
        $qav = $qm->makeQuery($query);

        return $database->query($qav->query, ...$qav->values);
    }

    public static function createRowArray(Explorer $database, Query $query): array
    {
        $res = [];
        foreach (self::createRowSelect($database, $query) as $row) $res[] = $row;

        return $res;
    }
}