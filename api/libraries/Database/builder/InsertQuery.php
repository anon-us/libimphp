<?php
/*
 * This file is part of the IMPHP Project: https://github.com/IMPHP
 *
 * Copyright (c) 2016 Daniel Bergløv
 *
 * IMPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * IMPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with IMPHP. If not, see <http://www.gnu.org/licenses/>
 */
namespace api\libraries\Database\builder;

use api\libraries\Database\Connection;

class InsertQuery extends QueryExecuter {

    use QueryBuilder_Table_Limited;
    use QueryBuilder_Field_Changeable;

    protected /*QueryExecuter*/ $mResolveExecuter = null;
    protected /*bool*/ $mIgnoreEnabled = null;
    protected /*array*/ $mPlaceholders = [];
    protected /*bool*/ $mIsCompiled = false;
    protected /*Connection*/ $mConnection = null;

    public function __construct(Connection $connection, string $table, string $alias=null) {
        $this->table($table, $alias);
        $this->mConnection = $connection;
    }

    protected function getConnection(): Connection {
        return $this->mConnection;
    }

    public function isCompiled(): bool {
        return $this->mIsCompiled;
    }

    public function resolve(QueryExecuter $builder) /*this*/ {
        $this->mResolveExecuter = $builder;
        $this->mIgnoreEnabled = true;

        return $this;
    }

    public function ignore() /*this*/ {
        $this->mIgnoreEnabled = true;

        return $this;
    }

    protected function prepareFieldSQL(string $field, string $value): string {
        $id = $this->_nextFieldId();
        $this->mPlaceholders[$id] = $value;

        return $field;
    }

    public function execute(): int {
        $result = parent::execute();

        if ($result == 0 && $this->mResolveExecuter !== null) {
            $result = $this->mResolveExecuter->execute();
        }

        return $result;
    }

    public function compile() /*this*/ {
        $this->mCompiledTypes = "";
        $this->mCompiledData = [];
        $this->mCompiledSQL = $this->mIgnoreEnabled ? "INSERT OR IGNORE" : "INSERT";

        if ($this->countSegments("table") > 0) {
            $this->mCompiledSQL .= " INTO";
            $this->compileSegments("table");
        }

        if ($this->countSegments("field") > 0) {
            $this->mCompiledSQL .= " (";
            $this->compileSegments("field");
            $this->mCompiledSQL .= ")";

            $this->mCompiledSQL .= " VALUES (";
            $this->mCompiledSQL .= implode(", ", $this->mPlaceholders);
            $this->mCompiledSQL .= ")";
        }

        if ($this->mResolveExecuter !== null) {
            $this->mResolveExecuter->compile();
        }

        $this->mIsCompiled = true;

        return $this;
    }
}
