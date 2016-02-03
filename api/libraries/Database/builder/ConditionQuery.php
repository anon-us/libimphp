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

class ConditionQuery extends QueryBuilder {

    use QueryBuilder_Conditional;

    protected /*bool*/ $mIsCompiled = false;

    public function __construct(string $operator=null) {
        if ($operator !== null) {
            $operator = strtolower($operator);

            if ($operator == "or") {
                $this->setCondOR();

            } else {
                $this->setCondAND();
            }
        }
    }

    public function isCompiled(): bool {
        return $this->mIsCompiled;
    }

    public function compile() /*this*/ {
        $this->mIsCompiled = true;
        $this->mCompiledSQL = "";
        $this->compileSegments("conditional");

        return $this;
    }
}
