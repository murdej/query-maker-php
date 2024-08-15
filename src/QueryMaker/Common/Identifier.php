<?php

namespace Murdej\QueryMaker\Common;

class Identifier
{
    public function __construct(
        public string|Snippet $name,
        public ?string $alias = null,
    )
    {
    }


    public static function fromString(string $tableName): Identifier
    {
        $tableName = trim($tableName);
        if (($p = strpos(strtoupper($tableName), " AS ")) !== false) {
            return new self(
                trim(substr($tableName, 0, $p)),
                trim(substr($tableName, $p + 4)),
            );
        } else if (($p = strpos(strtoupper($tableName), " ")) !== false) {
            return new self(
                trim(substr($tableName, 0, $p)),
                trim(substr($tableName, $p + 1)),
            );
        } else if (($p = strpos(strtoupper($tableName), ".")) !== false) {
            return new self(
                trim(substr($tableName, $p + 1)),
                trim(substr($tableName, 0, $p)),
            );
        }
        return new self($tableName);
    }

    public function fieldSnippet(): Snippet
    {
        $s = new Snippet();
        if ($this->alias) $s->identifier($this->alias)->code('.');
        $s->identifier($this->name);
        return $s;
    }

    public function tableSnippet(): Snippet
    {
        $s = new Snippet();
        $s->identifier($this->name);
        if ($this->alias) $s->code(" ")->identifier($this->alias);
        return $s;
    }}