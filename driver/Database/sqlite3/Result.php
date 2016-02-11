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

namespace driver\Database\sqlite3;

use SQLite3Stmt;
use SQLite3Result;
use api\libraries\Database\Result as BaseResult;
use api\libraries\collections\Bundle;

/**
 * SQLite3 driver result class
 *
 * @package driver\Database\sqlite3
 */
class Result extends BaseResult {

    /** @ignore */
    protected /*SQLite3Result*/ $mResult;

    /** @ignore */
    protected /*SQLite3Stmt*/ $mStmt;

    /** @ignore */
    protected /*int*/ $mNumRows;

    /** @ignore */
    public function __construct(SQLite3Result $result, SQLite3Stmt $stmt=null, int $numRows=0) {
        $this->mResult = $result;
        $this->mStmt = $stmt;
        $this->mNumRows = $numRows;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function numRows(): int {
        return $this->mNumRows;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function destroy() /*void*/ {
        $this->mNumRows = 0;
		$this->mResult->finalize();

        if ($this->mStmt !== null) {
            $this->mStmt->close();
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseResult*/
    public function fetch(bool $close=false) /*array*/ {
        $row = $this->mResult->fetcharray(SQLITE3_NUM);

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
        $row = $this->mResult->fetcharray(SQLITE3_ASSOC);

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
			$pos = $this->mNumRows + $pos;

			if ($pos < 0) {
				$pos = 0;
			}
		}

        /*
         * Sqlite does not have seek, so we obtain this feature a bit differently
         */

		$this->mResult->reset();

        $i=0;
		for (; $i < $pos && $i <= $this->mNumRows; $i++) {
			$this->mResult->fetcharray(SQLITE3_NUM);
		}

        return $pos == $i;
    }
}
