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

namespace driver\Router\imphp;

use api\libraries\Router\Resolver as BaseResolver;
use core\Runtime;

/**
 * A simple resolver class for the Router
 *
 * Loads controllers based on array keys with IMPHP settings.
 * The settigs index "ROUTER" should contain an array where
 * keys are to match the request string and the value contains the full
 * class name of the controller.
 *
 * Keys may contain regexp, or use shortcuts:
 *
 *      * :num:     - The segment may contain numbers
 *      * :alpha:     - The segment may contain alphanumeric (A-Za-z 0-9 _ and -)
 *      * :all:     - The segment may contain anything
 *
 * The values may also contain redirects.
 *
 *      * ':/new/request'       - Will reset the search and begin searhing for a key matching '/new/request'
 *
 * RegExp matches can also be added to redirects
 *
 *      * '/some/request/(:num)' => ':/new/request/$1'
 *
 * Once a controller is loaded, it is up to it's constructer to decide what's next
 *
 * Note that if called from inside a Controller scope, the request will be set as
 * pending and only executed when the scope is done. Every call while pending,
 * will overwrite the pending request.
 *
 * @package driver\Router\imphp
 */
class Resolver extends BaseResolver {

    /** @ignore */
    protected /*bool*/ $mRequestLocked = false;

    /** @ignore */
    protected /*string*/ $mPendingRequest = null;

    /** @ignore */
    protected /*Controller*/ $mController;

    /** @ignore */
    protected /*string*/ $mRequest;

    /** @ignore */
    protected /*array*/ $mSegments;

    /** @inheritdoc */
    /*Overwrite: BaseResolver*/
    public function getController() /*Controller*/ {
        return $this->mController;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResolver*/
    public function getRequest() /*String*/ {
        return $this->mRequest;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResolver*/
    public function getSegments(): array {
        return $this->mSegments;
    }

    /** @inheritdoc */
    /*Overwrite: BaseResolver*/
    public function request(string $request) /*void*/ {
        if ($this->mRequestLocked) {
            $this->mPendingRequest = $request;

            return;
        }

        $routes = Runtime::$SETTINGS->getArray("ROUTER");
        $controller = null;
        $request = rtrim($request, "/");

        if (empty($request)) {
            $request = "/";
        }

        /*
         * Pages should be allowed to issue a special page defined in settings as ROUTER_[num].
         * These however should not be available from te URI
         */
        $special = preg_match("#^ROUTER_[0-9]+$#", $request) && strcmp($request, Runtime::$SYSTEM->getString("URI_LOCATION", "/")) !== 0;

        if ($routes !== null && !$special) {
            $controller = $routes[$request] ?? null;

            if (empty($controller)) {
                foreach ($routes as $key => $value) {
                    $key = str_replace([":alpha:", ":all:", ":num:"], ["[A-Za-z0-9\-_]+", ".+", "[0-9]+"], $key);

                    if (preg_match('#^'.$key.'$#', $request)) {
                        if (!empty($value)) {
                            if (substr($value, 0, 1) == ":") {
                                $value = substr($value, 1);

                                if (strpos($val, "$") !== false) {
                                    $value = preg_replace("#^".$key."$#", $value, $request);
                                }

                                $this->request($value);

                                return;

                            } else {
                                $controller = $value;
                            }

                            break;
                        }
                    }
                }

            } elseif (substr($controller, 0, 1) == ":") {
                $this->request(substr($controller, 1));

                return;
            }

        } elseif ($special) {
            $controller = Runtime::$SETTINGS->getString($request);
        }

        $request = trim($request, "/");
        $controller = "page\\".trim($controller, "\\");

        if (!Runtime::loadClassFile($controller)) {
            $controller = "page\\".trim(Runtime::$SETTINGS->getString("ROUTER_404"), "\\");

            if (!Runtime::loadClassFile($controller)) {
                /*
                 * Do not parse output through any controller if one was selected previously
                 */
                $this->mController = null;

                header(Runtime::$SERVER["SERVER_PROTOCOL"]." 404 Not Found", 404, true);

                Runtime::quit(["404 Not Found","The requested file does not exist"], 1);
            }
        }

        $this->mSegments = empty($request) ? [] : explode("/", $request);
        $this->mRequest = "/".$request;

        /*
         * Let's say we redirect from with a controller
         *
         * 1. Controller1::__construct invokes a new assignment to $this->mController by calling $this->request()
         *      However Controller1::__construct does not finish until $this->request() returns, so it stalls between
         *      property assignments.
         *
         * 2. Controller2::__construct does a lot of page loading
         *
         * 3. Controller1::__construct finishes it's construct and is now assigned to $this->mController, overwriting Controller2
         *
         * 4. Shutdown now executes, but uses Controller1 instead of Controller2
         *
         * We need to finish the first controller before starting a new.
         * To do this, we add a pending feature.
         */
        $this->mRequestLocked = true;
        $this->mController = new $controller();
        $this->mRequestLocked = false;

        if ($this->mPendingRequest !== null) {
            $pending = $this->mPendingRequest;
            $this->mPendingRequest = null;

            $this->request($pending);
        }
    }
}
