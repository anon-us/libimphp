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

abstract class QueryExecuter extends QueryBuilder {

    abstract protected function getConnection(): Connection;

    public function execute(): int {
        $conn = $this->getConnection();

        if (!$this->isCompiled()) {
            $this->compile();
        }

        $sql = $this->getCompiledSQL();
        $types = $this->getCompiledTypes();
        $data = $this->getCompiledData();

        return $conn->execute($sql, $types, ...$data);
    }
}
