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

/**
 * Library class to work with Paths
 *
 * This class can help make it much simpler working with paths.
 * You can easily convert between Absolute paths and Relative paths,
 * you can create link, url's for files etc.
 *
 * @package api\libraries
 *
 * @todo
 *      Make it work across Windows devices '[letter]:/'
 */
class Path {

    /**
     * Turn a relative path into an absolute path
     *
     * This similar to PHP's `realpath()` only this work on paths that does not really exist.
     * Instead of creating it on real path information, this method let's you build a
     * path relative to a custom (real or fictional) path.
     *
     * If you pass this an Absolute path, this method will only resolve
     * nested `.` and `..`
     *
     * __Example__
     *
     * ```
     * Path::toAbsolute("/my/absolute/path/../file"); // Will become '/my/absolute/file'
     * Path::toAbsolute("../file", "/my/absolute/path"); // Will become '/my/absolute/file'
     * Path::toAbsolute(".././file", "/my/absolute/path"); // Will become '/my/absolute/file'
     * Path::toAbsolute("file", "/my/absolute"); // Will become '/my/absolute/file'
     * ```
     *
     * If you pass this method a relative path and does not supply $from,
     * then the path will be created relative to the current IMPHP root directory.
     *
     * @api
     */
    public static function toAbsolute(string $path, $from=null): string {
        $path = rtrim(str_replace(["\\", "//"], "/", $path), "/");
        $pathLen = strlen($path);

        if ($pathLen == 0 || substr($path, 0, 1) == "/" || strpos($path, ":") !== false) {
            $path = substr($path, strpos($path, "/"));
            $absolute = $path;

        } else {
            if ($from === null) {
                $from = rtrim(substr(str_replace(["\\", "//"], "/", Runtime::$SYSTEM["DIR_ROOT"]), strpos(Runtime::$SYSTEM["DIR_ROOT"], "/")), "/");

            } else {
                $from = rtrim(substr(str_replace(["\\", "//"], "/", $from), strpos($from, "/")), "/");
            }

            if ($pathLen == 1 && $path == ".") {
                $absolute = $from;

            } elseif (strlen($path) > 1 && substr($path, 0, 2) == "./") {
                $absolute = $from."/".substr($path, 2);

            } elseif (preg_match("/^(?:\.\.?(\/|$))+/", $path, $matches)) {
                $count = substr_count($matches[0], "..");
                $path = substr($path, strlen($matches[0]));

                for ($i=$count; $i > 0 && ($pos = strrpos($from, "/")) !== false; $i--) {
                    $from = substr($from, 0, $pos);
                }

                $absolute = $from."/$path";

            } else {
                $absolute = $from."/$path";
            }
        }

        /*
         * Resolve nested '.' and '..'
         */
        if (strpos($absolute, ".") !== false) {
            for ($n=1; $n > 0;) {
                $absolute = preg_replace("/^\/?\.\.\/?|(\/|^)(?!\.\.)([^\/]+\/|^)?\.\.(\/|$)|(\/|^)\.(\/|$)/", "/", rtrim($absolute, "/"), -1, $n);
            }

        } else {
            $absolute = rtrim($absolute, "/");
        }

        return empty($absolute) ? "/" : $absolute;
    }

    /**
     * Turn a absolute path into a relative path
     *
     * __Example__
     *
     * ```
     * Path::toAbsolute("/my/absolute/file", "/my/absolute/path"); // Will become '../file'
     * Path::toAbsolute("/my/absolute/path2/file", "/my/absolute/path"); // Will become '../path2/file'
     * ```
     *
     * @api
     */
    public static function toRelative(string $path, $from=null): string {
        if (strlen($path) == 0 || substr($path, 0, 1) == ".") {
            return $path;
        }

        if ($from === null) {
            $from = trim(substr(str_replace(["\\", "//"], "/", Runtime::$SYSTEM["DIR_ROOT"]), strpos(Runtime::$SYSTEM["DIR_ROOT"], "/")), "/");

        } else {
            $from = trim(substr(str_replace(["\\", "//"], "/", $from), strpos($from, "/")), "/");
        }

        $path = trim(static::toAbsolute($path), "/");

        /*
         * Relative is root or the two paths matches
         */
        if (strcmp($path, $from) === 0) {
            return ".";

        } else {
            /*
             * Remove matches up through the tree
             */
            $pathArr = strpos($path, "/") !== false ? explode("/", $path) : [];
            $relArr = strpos($from, "/") !== false ? explode("/", $from) : [];

            for ($i=0,$x=0; isset($pathArr[$i]) && isset($relArr[$i]) && strcmp($pathArr[$i], $relArr[$i]) === 0; $i++) {
                $x += strlen($pathArr[$i])+1;
            }

            /*
             * Find the remaining portion that create the local path with
             */
            $relative = substr($path, $x);

            /*
             * It's a sub-dir to relative
             */
            if($i == count($relArr)) {
                return "./$relative";

            /*
             * It's a outer-dir
             */
            } else {
                // $subs = count($relArr)-$i;
                return str_repeat("../", count($relArr)-$i).$relative;
            }
        }
    }

    /**
     * Create a link address
     *
     * This is a tool that makes it much simpler to create site link addresses.
     * It uses current uri information combined with parsed parameters to compile a
     * proper address.
     *
     * __Example__
     *
     * Example URL with rewrite: http://domain.com/current/path?key1=val1&key2=val2
     * Example URL without rewrite: http://domain.com?$=current/path&key1=val1&key2=val2
     *
     * ```
     * Path::link("../")                        Produces: http://domain.com/current?key1=val1&key2=val2                         Rewrite On
     * Path::link("../")                        Produces: http://domain.com?$=/current&key1=val1&key2=val2                      Rewrite Off
     *
     * Path::link("subpath")                    Produces: http://domain.com/current/path/subpath?key1=val1&key2=val2            Rewrite On
     * Path::link("subpath")                    Produces: http://domain.com?$=/current/path/subpath&key1=val1&key2=val2         Rewrite Off
     *
     * Path::link("/subpath?key1=val10")        Produces: http://domain.com/subpath?key1=val10                                  Rewrite On
     * Path::link("/subpath?key1=val10")        Produces: http://domain.com?$=/subpath&key1=val10                               Rewrite Off
     *
     * Path::link("&key1=val10")                Produces: http://domain.com/current/path/subpath?key10=val1&key2=val2           Rewrite On
     * Path::link("&key1=val10")                Produces: http://domain.com?$=/current/path/subpath&key10=val1&key2=val2        Rewrite Off
     *
     * Path::link("/?")                         Produces: http://domain.com                                                     Rewrite On
     * Path::link("/?")                         Produces: http://domain.com?$=/                                                 Rewrite Off
     * ```
     *
     * If you do not parse any parameters, this will print out the current uri.
     * If you want to reset the querystring, simply append '?' and optionally new names and values.
     * Appending '&' will only alter existing querystring values, if the name already exist, otherwise the new name will be added.
     *
     * @api
     *
     * @param string $path=""
     *      The new location or a relative to the current
     *
     * @param string $baseUrl=null
     *      Force a custom base url
     *
     * @param bool $noRewrite=false
     *      If true, rewrite settings will be disregarted
     *
     * @return string
     *      A full site url
     */
    public static function link(string $path="", string $baseUrl=null, bool $noRewrite=false): string {
        if (($pos = strpos($path, "?")) !== false) {
            $qs = trim(substr($path, $pos), "?");
            $path = substr($path, 0, $pos);

        } else {
            $qs = "";
            $get = [];

            if (($pos = strpos($path, "&")) !== false) {
                $tmp = trim(substr($path, $pos), "&");
                $path = substr($path, 0, $pos);

                if (!empty($tmp)) {
                    $entries = explode("&", $tmp);

                    foreach ($entries as $entry) {
                        list($name, $value) = explode("=", $entry);

                        if (strpos($name, "[]")) {
                            if (!isset($get[$name])) {
                                $get[$name] = [];
                            }

                            $get[$name][] = substr($name, -2);

                        } else {
                            $get[$name] = $value;
                        }
                    }

                    foreach (Runtime::$GET as $name => $value) {
                        if (!isset($get[$name]) && $name != "$") {
                            $get[$name] =& Runtime::$GET[$name];
                        }
                    }

                } else {
                    $get =& Runtime::$GET;
                }

            } else {
                $get =& Runtime::$GET;
            }

            foreach ($get as $name => $value) {
                if ($name != "$") {
                    if (is_array($value)) {
                        foreach ($value as $arrValue) {
                            $qs .= (!empty($qs) ? "&" : "").$name."[]=".rawurlencode($arrValue);
                        }

                    } else {
                        $qs .= (!empty($qs) ? "&" : "").$name."=".rawurlencode($value);
                    }
                }
            }
        }

        if (empty($path)) {
            $path = Runtime::$SYSTEM["URI_LOCATION"];

        } else {
            $path = static::toAbsolute($path, Runtime::$SYSTEM["URI_LOCATION"]);
        }

        $baseUrl = $baseUrl ?? Runtime::$SYSTEM->getString("URI_BASE", "");
        $rewrite = $noRewrite ? false : Runtime::$SETTINGS->getBoolean("PATH_URL_REWRITE");

        return rtrim($baseUrl, "/").($rewrite ? $path : "?$=$path").(!empty($qs) ? ($rewrite ? "?" : "&").$qs : "");
    }

    /**
     * Create a file link
     *
     * This will generate a url to a file based on real path information.
     *
     * __Example__
     *
     * ```
     * Path::fileLink("file", __DIR__)      Produces: http://localhost/path/to/file
     * ```
     *
     * It creates an absolute path based on either $from or the page root using $path
     * as releative, or absolute if starting with '/'. It then uses the path to generate
     * a valid url to that file location.
     *
     * Because this method is mostly intended for creating paths to client side content like
     * css, javascript, images etc., it will add an id tag to the path to ensure that old
     * client caches can be cleared. You can use the third parameter to disable this on
     * desired paths where it might not be suitable.
     *
     * @api
     *
     * @param $path
     *      Relative or absolut path to the file
     *
     * @param $from=null
     *      Absolute start location if $path is relative
     *
     * @param string $baseUrl=null
     *      Custom base url
     */
    public static function fileLink($path, $from=null, string $baseUrl=null, bool $noTag=false): string {
        $path = static::toAbsolute($path, $from);
        $baseUrl = $baseUrl ?? Runtime::$SETTINGS["URI_BASE_OVERWRITE"] ?? Runtime::$SYSTEM->getString("URI_BASE", "");
        $root = rtrim(substr(str_replace(["\\", "//"], "/", Runtime::$SYSTEM["DIR_ROOT"]), strpos(Runtime::$SYSTEM["DIR_ROOT"], "/")), "/");
        $rootLength = strlen($root);

        if ($rootLength < strlen($path) && substr($path, 0, $rootLength) == $root) {
            $path = substr($path, $rootLength);
        }

        if (!$noTag) {
            $idTag = Runtime::$SETTINGS->getInt("PATH_ID_TAG", -1);

            if ($idTag > 0 || $idTag == -1) {
                $path .= (strpos($path, "?") === false ? "?" : "&")."id=".($idTag > 0 ? $idTag : time());
            }
        }

        return rtrim($baseUrl, "/")."/".ltrim($path, "/");
    }
}
