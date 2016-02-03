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

use api\exceptions\ConnectionException;
use driver\Router\imphp\Resolver as IMPHPResolver;
use core\Runtime;

/**
 * Base class for working with the default Router Resolver
 *
 * @package api\libraries
 */
class Router {

    /** @ignore */
    protected static /*Encrypter*/ $oResolver = null;

    public static function getInstance() /*Resolver*/ {
        if (static::$oResolver === null) {
            $driver = Runtime::$SETTINGS->getString("ROUTER_DRIVER", "imphp");

            switch ($driver) {
    			case "imphp":
    				static::$oResolver = new IMPHPResolver(); break;

    			default:
                    /*
                     * Let modules add namespace hooks for missing drivers
                     */
                    $class = "driver\Router\\".$driver."\Resolver";

                    if (Runtime::loadClassFile($class)) {
                        static::$oResolver = (new ReflectionClass($class))->newInstance();
                    }
    		}
		}

		return static::$oResolver;
    }

    /**
     * @api
     * @see api\libraries\Router\getController#getController()
     */
    public static function getController() /*Controller*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getController($request);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Router\getController#getRequest()
     */
    public static function getRequest() /*String*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getRequest();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Router\getController#getSegments()
     */
    public static function getSegments(): array {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getSegments();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Router\getController#getSegment()
     */
    public static function getSegment(int $pos, string $default=null) /*String*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getSegment($pos, $default);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Router\getController#request()
     */
    public static function request(string $request) /*void*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		$instance->request($request);

        } else {
            throw new ConnectionException("Attempt to make call on dead resource");
        }
    }
}
