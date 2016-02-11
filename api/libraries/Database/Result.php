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

namespace api\libraries\Database;

/**
 * Abstract driver result class
 *
 * All database drivers should extend from this
 *
 * @package api\libraries\Database
 */
abstract class Result {

    /**
     * @api
     *
     * @return int
     *      The number of total rows returned from the query
     */
    public abstract function numRows(): int;

    /**
     * Free up the result
     *
     * @api
     */
	public abstract function destroy() /*void*/;

    /**
     * @api
     *
     * @param bool $close=false
     *      If true, return the curren row and destroy the result
     *
     * @return Bundle
     *      Fetch the next row as a Bundle
     */
    public abstract function fetch(bool $close=false) /*array*/;

    /**
     * @api
     *
     * @param bool $close=false
     *      If true, return the curren row and destroy the result
     *
     * @return array<mixed>
     *      Fetch the next row as an indexed array
     */
	public abstract function fetchAssoc(bool $close=false) /*array*/;

    /**
     * Moves the internal row pointer to a requested position
     *
     * @api
     *
     * @param int $pos
     *      The new pointer position. Can also be negative to start from right to left
     *
     * @return bool
     *      True on success, false otherwise
     */
	public abstract function seek(int $pos): bool;
}
