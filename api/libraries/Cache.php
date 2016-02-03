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

use api\interfaces\IStaticConstruct;
use api\exceptions\ConnectionException;
use driver\Cache\file\Storage as FileStorage;
use driver\Cache\memcached\Storage as MemStorage;
use driver\Cache\database\Storage as DBStorage;
use core\Runtime;

/**
 * Base class for working with the default Cache Storage
 *
 * @package api\libraries
 */
class Cache implements IStaticConstruct {

    /** @ignore */
    protected static /*Storage*/ $oStorage = null;

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onCreate() /*void*/ {
        /*
         * This is used to provide a 'cache' lock.
         * Cache might be using a Database that should not be stopped until cache is done.
         */
        if (Runtime::$SETTINGS->getString("CACHE_DRIVER") == "database") {
            Runtime::addLock("database");
        }
    }

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onDestroy() /*void*/ {
        if (Runtime::$SETTINGS->getString("CACHE_DRIVER") == "database") {
            if (!Runtime::hasLock("cache")) {
                Runtime::removeLock("database");

            } else {
                Runtime::addLockCallback("cache", [get_called_class(), "onDestroy"]);
            }
        }
    }

    public static function getInstance() /*Storage*/ {
        if (static::$oStorage === null) {
            static::$oStorage = static::newInstance(
                Runtime::$SETTINGS->getString("CACHE_DRIVER", "file"),
                Runtime::$SETTINGS->getString("CACHE_PROTOCOL"));
		}

		return static::$oStorage;
    }

    public static function newInstance(string $driver, /*string*/ $protocol) /*Storage*/ {
		$oStorage = null;

		switch ($driver) {
			case "file":
				$oStorage = new FileStorage($protocol); break;

            case "memcached":
				$oStorage = new MemStorage($protocol); break;

            case "database":
                $oStorage = new DBStorage($protocol); break;

			default:
                /*
                 * Let modules add namespace hooks for missing drivers
                 */
                $class = "driver\Cache\\".$driver."\Storage";

                if (Runtime::loadClassFile($class)) {
                    $oStorage = (new ReflectionClass($class))->newInstance($protocol);
                }
		}

		return $oStorage;
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#set()
     */
    public static function set(string $key, /*mixed*/ $value, int $expires=0, string $encKey=null): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->set($key, $value, $expires, $encKey);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#get()
     */
    public static function get(string $key, /*mixed*/ $default=null, string $encKey=null) /*mixed*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->get($key, $default, $encKey);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#setRaw()
     */
    public static function setRaw(string $key, string $value, int $expires=0, string $encKey=null): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->setRaw($key, $value, $expires, $encKey);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#getRaw()
     */
    public static function getRaw(string $key, string $default=null, string $encKey=null) /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getRaw($key, $default, $encKey);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#remove()
     */
    public static function remove(string $key): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->remove($key);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Cache\Storage#flush()
     */
    public static function flush(): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->flush();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }
}
