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

use api\libraries\Path;
use api\libraries\Router;
use api\interfaces\IStaticConstruct;

/**
 * Import CSS and JS files
 *
 * _Note that this depends on you using the Router library for page loads_
 *
 * When you devide content into smaller sections of files, it can be tricky to keep
 * track of which files has been set to be included. Some content files may depends
 * on the same files as other content files being included, which can result
 * in the same file being included multiple times.
 *
 * You may also include two content files where one depends on an additional file
 * that needs to be included in between two files being included by the other content file.
 *
 * This class can help keep track of all this. It contains a regular `import` method
 * that will make sure that files are included only ones. It also contains a `stack` method
 * allowing you to define multiple files in a specific order. The method will make sure that this
 * order is followed, even if some of these files are already being included. The `stack` method can
 * also be pre-defined using the `group` method. This method uses stacks defined in IMPHP's settings
 * allowing you to include specific pr-defined stacks based on a group name.
 *
 * To add stacks to the settings, add an array with index `FILE_IMPORT` to your settings file.
 * This array is single-level using group/stack name as key with an indexed array as value specifying each
 * JS and CSS file within this particular group/stack.
 *
 * All files that are parsed to this class must be relative or absolute server paths.
 * Do not parse files via `Path::fileLink` as this will break the checks and your files
 * will end up being included multiple times. File links are handled automatically during inclusion.
 *
 * @package api\libraries
 */
class FileImport implements IStaticConstruct {

    /** @ignore */
    protected static /*array*/ $mImportedStyle = [];

    /** @ignore */
    protected static /*array*/ $mImportedScript = [];

    /** @ignore */
    protected static /*array*/ $mImportedGroups = [];

    /** @ignore */
    protected static /*array*/ $mGroups = [];

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onCreate() /*void*/ {
        if (!Runtime::$SETTINGS->getBoolean("FILE_IMPORT_DISABLE_AUTOINCLUDE")) {
            if (Runtime::$SETTINGS->containsKey("FILE_IMPORT")) {
                static::$mGroups = Runtime::$SETTINGS->getArray("FILE_IMPORT", []);
            }

            Runtime::addLock("router");
        }
    }

    /** @ignore */
    /*Overwrite: IStaticConstruct*/
    public static function onDestroy() /*void*/ {
        if (!Runtime::$SETTINGS->getBoolean("FILE_IMPORT_DISABLE_AUTOINCLUDE")) {
            $controller = Router::getController();

            if ($controller !== null) {
                foreach (static::$mImportedStyle as $style) {
                    $controller->imAddImport(Path::fileLink($style), "importStyle");
                }

                foreach (static::$mImportedScript as $script) {
                    $controller->imAddImport(Path::fileLink($script), "importScript");
                }
            }

            Runtime::removeLock("router");
        }
    }

    /**
     * Get all current collected style files
     *
     * @return array
     *      An array of all style files currently in the ordered stack
     */
    public function getStyleImports(): array {
        return $this->mImportedStyle;
    }

    /**
     * Get all current collected script files
     *
     * @return array
     *      An array of all script files currently in the ordered stack
     */
    public function getScriptImports(): array {
        return $this->mImportedScript;
    }

    /**
     * Import a client side file
     *
     * Files ending with `.css` is included as style and `.js` as script files.
     *
     * @api
     *
     * @param string $path
     *      Server path to the file
     *
     * @param string $parent=null
     *      Optional path to be used when resolving relative paths
     */
    public static function import(string $path, string $parent=null): bool {
        $file = Path::toAbsolute($path, $parent);
        $type = substr($file, strrpos($file, ".")+1);

        if ($type == "css") {
            $cache =& static::$mImportedStyle;

        } else {
            $cache =& static::$mImportedScript;
        }

        if (!in_array($file, $cache)) {
            array_push($cache, $file);
        }

        return true;
    }

    /**
     * Import a pre-defined stack
     *
     * @api
     *
     * @param string $name
     *      Name of the pre-defined stack
     */
    public static function importGroup(string $name): bool {
        if (isset(static::$mGroups[$name])) {
            if (!isset(static::$mImportedGroups[$name])) {
                static::importStack(static::$mGroups[$name]);
                static::$mImportedGroups[$name] = true;
            }

            return true;
        }

        return false;
    }

    /**
     * Import a stack of files
     *
     * @api
     *
     * @param array $stack
     *      Indexed array containing all files to be included
     *
     * @param string $parent=null
     *      Optional path to be used when resolving relative paths
     *
     * @return int
     *      The number of files that was included, already included files was skipped
     */
    public static function importStack(array $stack, string $parent=null): int {
        $offset = -1;
        $count = 0;

        foreach ($stack as $file) {
            $file = Path::toAbsolute($file, $parent);
            $type = substr($file, strrpos($file, ".")+1);

            if ($type == "css") {
                $cache =& static::$mImportedStyle;

            } else {
                $cache =& static::$mImportedScript;
            }

            if (($pos = array_search($file, $cache)) !== false) {
                $offset = $pos+1;

            } else {
                if ($offset >= 0) {
                    array_splice($cache, $offset, 0, $file);

                    $pos++;

                } else {
                    array_unshift($cache, $file);

                    $offset = 1;
                }

                $count++;
            }
        }

        return $count;
    }
}
