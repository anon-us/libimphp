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

namespace api\libraries\Router;

use core\Runtime;

/**
 * Base Resolver class used by Router
 *
 * Router drivers should extend from this class
 *
 * @package api\libraries\Router
 */
abstract class Resolver {

    /**
     * Constructs a new Resolver instance
     *
     * @api
     */
    public function __construct() {
        if (Runtime::$SETTINGS->getBoolean("ROUTER_BUFFER", true)) {
            Runtime::addShutdownReceiver([$this, "onPrepareOutput"]);

            if (extension_loaded("zlib") && !in_array(strtolower(ini_get("zlib.output_compression")), ["on", "true", "1"])) {
                ob_start("ob_gzhandler", 0, 0);
            }

            ob_start();
        }

        Runtime::addClassFileFinder([$this, "onFindClass"]);
    }

    /**
     * Callback for preparing output
     *
     * Used to allow page classes to give a final touch
     * on output before it is sent to the client.
     *
     * @ignore
     */
    public function onPrepareOutput() {
        $controller = $this->getController();

        if ($controller !== null) {
            echo $controller->imOnPrepareOutput(ob_get_clean());

        } else {
            echo ob_get_clean();
        }
    }

    /**
     * Callback for Runtime class loading
     *
     * This allows Runtime to locate page classes
     *
     * @ignore
     */
    public function onFindClass(string $classname) /*string*/ {
        if (strpos($classname, "page\\") === 0) {
            $file = Runtime::$SYSTEM["DIR_DATA"]."/".str_replace("\\", "/", $classname).".php";

            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Return a specific segment of the request string
     *
     * A request string is a uri like string deviced by '/'.
     * Each division is split into segments that can be obtained individually
     * by this method.
     *
     * @api
     *
     * @param int $pos
     *      Position of the segment, can also be negative to go from right to left
     *
     * @param string $default=null
     *      Default value that is returned of the position does not exist
     *
     * @return string|null
     *      The requested segment, or default or NULL if it does not exist
     */
    public function getSegment(int $pos, string $default=null) /*String*/ {
        $segments = $this->getSegments();
        $pos = $pos < 0 ? count($segments)+$pos : $pos;

        return $segments[$pos] ?? $default;
    }

    /**
     * Make a request to the router
     *
     * Can be used to initiate a request or make an internal redirect
     * from one controller to another.
     *
     * @api
     *
     * @param string $request
     *      A request string
     */
    abstract public function request(string $request) /*void*/;

    /**
     * Get the current active controller
     *
     * @api
     *
     * @return Controller|null
     *      The current controller or NULL if none is set
     */
    abstract public function getController() /*Controller*/;

    /**
     * Get the entire request string
     *
     * @api
     *
     * @return string|null
     *      The request string or NULL if none is set
     */
    abstract public function getRequest() /*String*/;

    /**
     * Get all request segments
     *
     * Like `getSegment()` only this returns the entire array of segments
     *
     * @api
     *
     * @return array
     *      The request segments or and empty array if none if set
     */
    abstract public function getSegments(): array;
}
