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

namespace driver\Database\mysqli;

use mysqli_stmt;
use mysqli_result;
use api\libraries\Database\Result as BaseResult;
use api\libraries\collections\Bundle;

/**
 * MySQLi driver result class
 *
 * @package driver\Database\mysqli
 */
class Result extends BaseResult {

    /** @ignore */
    protected /*mysqli_result*/ $mResult;

    /** @ignore */
    protected /*mysqli_result*/ $mStmt;

    /** @ignore */
    public function __construct(mysqli_result $result, mysqli_stmt $stmt=null) {
        $this->mResult = $result;
        $this->mStmt = $stmt;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function numRows(): int {
        return $this->mResult->num_rows;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function destroy() /*void*/ {
        $this->mResult->free();

        if ($this->mStmt !== null) {
            $this->mStmt->close();
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function fetch(bool $close=false) /*array*/ {
        $row = $this->mResult->fetch_row();

        if ($close) {
            $this->destroy();
        }

        if (is_array($row)) {
            return $row;
        }

        return null;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function fetchAssoc(bool $close=false) /*array*/ {
        $row = $this->mResult->fetch_assoc();

        if ($close) {
            $this->destroy();
        }

        if (is_array($row)) {
            return $row;
        }

        return null;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function seek(int $pos): bool {
        if ($pos < 0) {
            $pos = $this->mResult->num_rows + $pos;

            if ($pos < 0) {
                $pos = 0;
            }
        }

        return $this->mResult->data_seek($pos < $this->mResult->num_rows ? $pos : ($this->mResult->num_rows - 1));
    }
}
