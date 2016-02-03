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
use api\libraries\collections\ImmVector;
use api\exceptions\ConnectionException;
use driver\Auth\imphp\User as IMHPUser;
use core\Runtime;

/**
 * Base class for working with the default User
 *
 * @package api\libraries
 */
class Auth implements IStaticConstruct {

    /** @ignore */
    protected static /*User*/ $oUser = null;

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onCreate() /*void*/ {
        /*
         * This is used to provide a 'auth' lock.
         * Since this library is depended on sessions, locking this will also lock sessions.
         * That way sessions is not closed and re-opened if used during shutdown.
         */
        Runtime::addLock("session");
    }

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onDestroy() /*void*/ {
        if (!Runtime::hasLock("auth")) {
            Runtime::removeLock("session");

        } else {
            Runtime::addLockCallback("auth", [get_called_class(), "onDestroy"]);
        }
    }

    /**
     * Get the default User instance
     *
     * @api
     * @see api\libraries\Auth\User
     *
     * @return User
     *      The default User
     */
    public static function getInstance() /*Resolver*/ {
        if (static::$oUser === null) {
            $driver = Runtime::$SETTINGS->getString("AUTH_DRIVER", "imphp");

            switch ($driver) {
                case "imphp":
                    static::$oUser = new IMHPUser(); break;

                default:
                    /*
                     * Let modules add namespace hooks for missing drivers
                     */
                    $class = "driver\Auth\\".$driver."\User";

                    if (Runtime::loadClassFile($class)) {
                        static::$oUser = (new ReflectionClass($class))->newInstance();
                    }
            }
        }

        return static::$oUser;
    }

    /**
     * @api
     * @see api\libraries\Auth\User#login()
     */
    public static function login(string $username, string $password): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->login($username, $password);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Auth\User#logout()
     */
    public static function logout() /*void*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		$instance->logout();

        } else {
            throw new ConnectionException("Attempt to make call on dead resource");
        }
    }

    /**
     * @api
     * @see api\libraries\Auth\User#isLoggedIn()
     */
    public static function isLoggedIn(): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->isLoggedIn();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Auth\User#getGroups()
     */
    public static function getGroups(): ImmVector {
        $instance = static::getInstance();

        if ($instance !== null) {
            return $instance->getGroups();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Auth\User#inGroup()
     */
    public static function inGroup(string ...$groups): int {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->inGroup(...$groups);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }
}
