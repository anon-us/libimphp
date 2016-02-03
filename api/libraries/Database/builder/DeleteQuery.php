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

class DeleteQuery extends QueryExecuter {

    use QueryBuilder_Conditional;
    use QueryBuilder_Join;
    use QueryBuilder_Table_Extended;

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

    public function compile() /*this*/ {
        $this->mCompiledTypes = "";
        $this->mCompiledData = [];
        $this->mCompiledSQL = "DELETE ".$this->mMasterTbl." FROM";

        if ($this->countSegments("table") > 0) {
            $this->compileSegments("table");
        }

        if ($this->countSegments("join") > 0) {
            $this->compileSegments("join");
        }

        if ($this->countSegments("conditional") > 0) {
            $this->mCompiledSQL .= " WHERE";
            $this->compileSegments("conditional");
        }

        $this->mIsCompiled = true;

        return $this;
    }
}
