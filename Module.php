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

namespace module\libimphp;

include "Constants.php";

use api\interfaces\IStaticConstruct;
use api\interfaces\IClassFinder;
use api\interfaces\IBroadcastReceiver;
use api\libraries\collections\Bundle;
use api\libraries\Router;
use core\Cookies;
use core\Session;
use core\Runtime;

/**
 * @package module\libimphp
 * @ignore
 */
class Module implements IClassFinder, IStaticConstruct, IBroadcastReceiver {

    public static function onCreate() /*void*/ {
        /*
         * Hook page directory into the IMPHP System variable
         */
        Runtime::$SYSTEM["DIR_PAGE"] = Runtime::$SETTINGS->getString("DIR_PAGE_OVERWRITE", str_replace("\\", "/", __DIR__)."/page");

        /*
         * Attach the special cookie collection to the Runtime cookie property
         */
        Runtime::$COOKIE = new Cookies();

        /*
         * Attach the special session collection to the Runtime session property
         */
        Runtime::$SESSION = new Session();
    }

    public static function onDestroy() /*void*/ {

    }

    public static function onBroadcast(string $type, Bundle $data=null) /*void*/ {
        if ($type == "imphp.modules_loaded") {
            /*
             * Start Router if enabled
             */
            if (Runtime::$SETTINGS->getBoolean("ROUTER_ENABLED", true)) {
                Router::request(Runtime::$SYSTEM->getString("URI_LOCATION", "/"));
            }
        }
    }

    /**
     * Load api classes via IMPHP Autoloader
     *
     * This will allow the IMPHP Autoloader to locate and load
     * api classes included in this library
     *
     * @ignore
     *
     * @param string $classname
     *      Name of the class to find
     */
    /*Overwrite: IClassFinder*/
    public static function onFindClass(string $classname) /*string*/ {
        if (($pos = strpos($classname, "\\")) !== false) {
            $type = substr($classname, 0, $pos);

            if ($type == "page") {
                /*
                 * Create a valid file path with our custom location
                 */
                $file = Runtime::$SYSTEM["DIR_PAGE"]."/".str_replace("\\", "/", substr($classname, $pos+1)).".php";

            } else {
                $file = __DIR__."/".str_replace("\\", "/", $classname).".php";
            }

            if (is_file($file)) {
                /*
                 * Return the file to Runtime
                 */
                return $file;
            }
        }

        /*
         * Nothing here, let other finders take a shot at this
         */
        return null;
    }
}
