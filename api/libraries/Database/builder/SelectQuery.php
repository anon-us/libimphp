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

class SelectQuery extends QueryBuilder {

    use QueryBuilder_Table_Extended;
    use QueryBuilder_Field_Selectable;
    use QueryBuilder_Conditional;
    use QueryBuilder_Join;

    protected /*string*/ $mSortQuery = null;
    protected /*string*/ $mLimitQuery = null;
    protected /*bool*/ $mDistinctEnabled = false;
    protected /*bool*/ $mCountEnabled = false;
    protected /*bool*/ $mIsCompiled = false;
    protected /*Connection*/ $mConnection = null;

    public function __construct(Connection $connection, string $table=null, string $alias=null) {
        if ($table !== null) {
            $this->table($table, $alias);
        }

        $this->mConnection = $connection;
    }

    public function enquire() /*Result*/ {
        $conn = $this->getConnection();

        if (!$this->isCompiled()) {
            $this->compile();
        }

        $sql = $this->getCompiledSQL();
        $types = $this->getCompiledTypes();
        $data = $this->getCompiledData();

        return $conn->enquire($sql, $types, ...$data);
    }

    public function sortAsc(string ...$fields) {
        $this->mSortQuery = " ORDER BY ".implode(", ", $fields)." ASC";

        return $this;
    }

    public function sortDesc(string ...$fields) {
        $this->mSortQuery = " ORDER BY ".implode(", ", $fields)." DESC";

        return $this;
    }

    public function range(int $max, int $offset=0) {
        $this->mLimitQuery = " LIMIT ${max} OFFSET ${offset}";
    }

    public function distinct() /*this*/ {
        $this->mDistinctEnabled = true;

        return $this;
    }

    public function count() /*this*/ {
        $this->mCountEnabled = true;

        return $this;
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

        if ($this->mCountEnabled) {
            $this->mCompiledSQL = "SELECT COUNT(*) FROM (";

        } else {
            $this->mCompiledSQL = "";
        }

        $this->mCompiledSQL .= "SELECT".($this->mDistinctEnabled ? " DISTINCT" : "");

        if ($this->countSegments("field") > 0) {
            $this->compileSegments("field");

        } else {
            $this->mCompiledSQL .= " *";
        }

        if ($this->countSegments("table") > 0) {
            $this->mCompiledSQL .= " FROM";
            $this->compileSegments("table");
        }

        if ($this->countSegments("join") > 0) {
            $this->compileSegments("join");
        }

        if ($this->countSegments("conditional") > 0) {
            $this->mCompiledSQL .= " WHERE";
            $this->compileSegments("conditional");
        }

        if (!empty($this->mSortQuery)) {
            $this->mCompiledSQL .= $this->mSortQuery;
        }

        if (!empty($this->mLimitQuery)) {
            $this->mCompiledSQL .= $this->mLimitQuery;
        }

        if ($this->mCountEnabled) {
            $this->mCompiledSQL .= ")";
        }

        $this->mIsCompiled = true;

        return $this;
    }
}
