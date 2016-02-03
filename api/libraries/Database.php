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

namespace api\libraries;

use core\Runtime;
use api\exceptions\ConnectionException;
use api\interfaces\IStaticConstruct;

use driver\Database\sqlite3\Connection as ConnectionSqlite3;
use driver\Database\mysqli\Connection as ConnectionMySqli;

use api\libraries\Database\Result;
use api\libraries\Database\builder\SelectQuery;
use api\libraries\Database\builder\DeleteQuery;
use api\libraries\Database\builder\UpdateQuery;
use api\libraries\Database\builder\InsertQuery;
use api\libraries\Database\builder\ConditionQuery;

/**
 * Helper class for Database Connections
 *
 * This class can be used to establish a new Database Connection.
 * It will automatically locate the proper driver based on the connection protocol string.
 * It is prefered to always go through this class when creating connections.
 *
 * It also contains a default connection that can be configured from settings.
 * The protocol is then taken from settings 'DATABASE'. This connection
 * is established on first use and can be easier shared between classes.
 * Unless for some specific reason you need a particular database connection,
 * using the default connection is the best option.
 *
 * Last this class has shortcuts for the default connection.
 * This allows you to make static queries and so fourth on this connection.
 *
 * @package api\libraries
 */
class Database implements IStaticConstruct {

    /** @ignore */
    protected static /*Connection*/ $oConnection = null;

    /** @ignore */
    protected static /*int*/ $oAttempts = 0;

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onCreate() /*void*/ {}

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onDestroy() /*void*/ {
        if (static::$oConnection !== null) {
            if (!Runtime::hasLock("database")) {
                static::$oConnection->close();

            } else {
                Runtime::addLockCallback("database", [get_called_class(), "onDestroy"]);
            }
        }
    }

    /**
     * Get the default Connection instance
     *
     * @api
     * @see api\libraries\Database\Connection
     *
     * @return Connection|null
     *      The default Connection
     */
    public static function getInstance() /*Connection*/ {
		if (static::$oConnection === null || !static::$oConnection->isConnected()) {
            /*
             * Inform developer that something is disconnecting from the database.
             * No need to reconnect multiple times during each requests.
             */
            if (static::$oAttempts > 0) {
                trigger_error("The default database connection is being reconnected for the ".(static::$oAttempts+1)." time", E_USER_NOTICE);
            }

            $protocol = Runtime::$SETTINGS->getString("DATABASE");

            if ($protocol !== null) {
                static::$oAttempts++;
    			static::$oConnection = static::newInstance($protocol);
            }
		}

		return static::$oConnection;
	}

    /**
     * Create a new Connection instance
     *
     * @api
     * @see api\libraries\Database\Connection
     *
     * @param string $protocol
     *      Connection protocol
     *
     * @return Connection|null
     *      The new Connection
     */
    public static function newInstance(string $protocol) /*Connection*/ {
		$driver = substr($protocol, 0, strpos($protocol, ":"));
		$connection = null;

		switch ($driver) {
			case "sqlite3":
				$connection = new ConnectionSqlite3($protocol); break;

            case "mysqli":
                $connection = new ConnectionMySqli($protocol); break;

			default:
                /*
                 * Let modules add namespace hooks for missing drivers
                 */
                $class = "driver\Database\\".$driver."\Connection";

                if (Runtime::loadClassFile($class)) {
                    $connection = (new ReflectionClass($class))->newInstance($protocol);
                }
		}

		return $connection;
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#condition()
     */
    public static function condition(string $operator=null): ConditionQuery {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->condition($operator);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Database\Connection#select()
     */
    public static function select(string $table=null, string $alias=null): SelectQuery {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->select($table, $alias);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Database\Connection#delete()
     */
    public static function delete(string $table, string $alias=null): DeleteQuery {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->delete($table, $alias);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Database\Connection#insert()
     */
    public static function insert(string $table, string $alias=null): InsertQuery {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->insert($table, $alias);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Database\Connection#update()
     */
    public static function update(string $table, string $alias=null): UpdateQuery {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->update($table, $alias);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Database\Connection#enquire()
     */
    public static function enquire(string $sql, string $types, /*mixed...*/ &...$data) /*Result*/ {
		$instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->enquire($sql, $types, ...$data);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#execute()
     */
    public static function execute(string $sql, string $types, /*mixed...*/ &...$data): int {
		$instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->execute($sql, $types, ...$data);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#escape()
     */
    public static function escape(/*mixed*/ $data): string {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->escape($data);
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#connectError()
     */
    public static function connectError() /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->connectError();
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#connectErrNo()
     */
    public static function connectErrNo(): int {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->connectErrNo();
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#connectError()
     */
    public static function queryError() /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->connectError();
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#connectErrNo()
     */
    public static function queryErrNo(): int {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->connectErrNo();
        }

        throw new ConnectionException("Attempt to make query on dead resource");
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#isConnected()
     */
    public static function isConnected(): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->isConnected();
        }

        return false;
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#platform()
     */
    public static function platform() /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->platform();
        }

        return null;
	}

    /**
     * @api
     * @see api\libraries\Database\Connection#driver()
     */
    public static function driver() /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->driver();
        }

        return null;
	}
}
