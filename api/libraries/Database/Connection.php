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

use api\libraries\Database\builder\SelectQuery;
use api\libraries\Database\builder\DeleteQuery;
use api\libraries\Database\builder\UpdateQuery;
use api\libraries\Database\builder\InsertQuery;
use api\libraries\Database\builder\ConditionQuery;

/**
 * Abstract driver connection class
 *
 * All database drivers should extend from this
 *
 * @package api\libraries\Database
 */
abstract class Connection {

    /** @ignore */
    protected static /*regexp[]*/ $_ESCAPE = ['\\', "\0", "\n", "\r", "'", '"', "\x1a"];

    /** @ignore */
    protected static /*regexp[]*/ $_REPLACE = ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'];

    /**
     * Construct a new connection to the database
     *
     * @api
     *
     * @param string $protocol
     *      A protocol with the information needed to establish the connection
     */
    public abstract function __construct(string $protocol);

    /**
     * Escape a user input value to make it SQL highjack safe
     *
     * This method converts datatypes types to the appropriate string representations.
     * It also excludes types that does not go directly into SQL Queries.
     *
     *      * `int`, `float`: Converted to string representation of their value
     *      * `boolean`: Converted into 1 or 0 for true and false
     *      * `string`: Properly escaped and wrapped around single quotes
     *      * `NULL`: Converted to string representation of NULL
     *      * Everything else if set to string representation of NULL
     *
     * Only use this if you are inserting data elements directory into your SQL Query.
     * Do not use it on data elements added to the input parameters, which is what you should
     * be using in most cases, if not all.
     *
     * @api
     *
     * @param mixed $data
     *      The value to escape
     *
     * @return string
     *      A safe escaped string where data type has been properly converted
     *
     * @todo
     *      Strings should not always be quoted. We might have a date for example
     */
    public function escape(/*mixed*/ $data): string {
        if (is_int($data) || is_float($data)) {
            return strval($data);

        } elseif (is_bool($data)) {
            return $data ? "1" : "0";

        } else if (is_string($data)) {
            return "'".str_replace(static::$_ESCAPE, static::$_REPLACE, $data)."'";

        } else {
            return "NULL";
        }
	}

    /**
     *
     */
    public function condition(string $operator=null): ConditionQuery {
        return new ConditionQuery($operator);
    }

    /**
     *
     */
    public function select(string $table=null, string $alias=null): SelectQuery {
        return new SelectQuery($this, $table, $alias);
    }

    /**
     *
     */
    public function delete(string $table, string $alias=null): DeleteQuery {
        return new DeleteQuery($this, $table, $alias);
    }

    /**
     *
     */
    public function insert(string $table, string $alias=null): InsertQuery {
        return new InsertQuery($this, $table, $alias);
    }

    /**
     *
     */
    public function update(string $table, string $alias=null): UpdateQuery {
        return new UpdateQuery($this, $table, $alias);
    }

    /**
     * Check whether we have a connection to the database or not
     *
     * @api
     *
     * @return bool
     *      True if the connection is alive, false otherwise
     */
    abstract public function isConnected(): bool;

    /**
     * Make an enquiry on the database
     *
     * The $sql argument can be formated with sprintf() plaveholders.
     * Data for these placeholders can be placed in $data which will
     * then be auto escaped and data type converted.
     *
     * @api
     *
     * @param string $sql
     *      The SQL string including optional sprintf() formatting
     *
     * @param string $types=null
     *      When using $data, this argument must contain datatype for each of the $data segments.
     *       - 's'='string', 'i'='integer', 'f'='float' or 'b'='blob'
     *
     * @param array<mixed> $data=null
     *      Data for the $sql sprintf() placeholders
     *
     * @return Result
     *      A Result object containing the returned data or error information if something went wrong
     */
    abstract public function enquire(string $sql, string $types=null, /*mixed...*/ &...$data) /*Result*/;

    /**
     * Make a query on the database
     *
     * This is the same as query() only it will not return a Result.
     * Instead it returns the number of affected rows and is useful
     * for actions like delete, update and create operations.
     *
     * @api
     *
     * @param string $sql
     *      The SQL string including optional sprintf() formatting
     *
     * @param string $types=null
     *      When using $data, this argument must contain datatype for each of the $data segments.
     *       - 's'='string', 'i'='integer', 'f'='float' or 'b'='blob'
     *
     * @param array<mixed> $data=null
     *      Data for the $sql sprintf() placeholders
     *
     * @return int
     *      Number of affected rows
     */
    abstract public function execute(string $sql, string $types=null, /*mixed...*/ &...$data): int;

    /**
     * Get connection error message if connecting failed
     *
     * @api
     *
     * @return string|null
     *      The connection error or null if there is no errors
     */
    abstract public function connectError() /*string*/;

    /**
     * Get connection error number if connecting failed
     *
     * @api
     *
     * @return int
     *      The connection error number or 0 if there is no errors
     */
    abstract public function connectErrNo(): int;

    /**
     * Get error message from last query
     *
     * @api
     *
     * @return string|null
     *      The error or null if there is no errors
     */
    abstract public function queryError() /*string*/;

    /**
     * Get error number from last query
     *
     * @api
     *
     * @return int
     *      The error number or 0 if there is no errors
     */
    abstract public function queryErrNo(): int;

    /**
     * Get the name of the database this driver uses
     *
     * This is the name of the database type for example MySQL or MSSql etc.
     *
     * @api
     *
     * @return string
     *      The name of the database
     */
    abstract public function platform(): string;

    /**
     * Get the name of this driver
     *
     * This is the name of this particular driver without the drv_* prefix.
     * This is the same as the name used in the connection protocol [name]://
     *
     * @api
     *
     * @return string
     *      The name of the driver
     */
    abstract public function driver(): string;

    /**
     * Close the database connection
     *
     * @api
     */
    abstract public function close() /*void*/;
}
