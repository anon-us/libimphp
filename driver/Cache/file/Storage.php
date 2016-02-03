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

namespace driver\Cache\file;

use api\libraries\Crypt;
use api\libraries\Cache\Storage as BaseStorage;
use Exception;

/**
 * File based cache driver
 *
 * Do not use this driver in production unless you make very little changes
 * to the cached data. This driver stores key values in files which is in no way
 * locked or otherwise protected against any form of cross read/write between multiple requests.
 * Also it caches data that is read which means that at the end of the request, this data
 * could already be outdated if changed by another request.
 *
 * If you need to store static caches for long periods of time, or you just want a cache driver
 * while testing on a server where databases and/or memcached is not setup, then this will
 * do just fine. But if you need production caching that is constantly changed, then
 * do use one of the other drivers instead.
 *
 * Also do not select this as driver if you use the 'cache' driver for sessions.
 * The sessions library has it's own file based driver.
 *
 * @package driver\Cache\file
 */
class Storage extends BaseStorage {

    /** @ignore */
    protected /*string*/ $mPath;

    /** @ignore */
    protected /*array*/ $mCache = [];

    /**
     * Create a new File Storage instance
     *
     * @api
     *
     * @param string $dir=null
     *      Directory for cache files or use the default which is the php.ini defined session dir.
     *      The directory needs to be writable and allow listings.
     *
     */
    /*Overwrite: BaseStorage*/
    public function __construct(string $dir=null) {
        if ($dir === null) {
            $dir = session_save_path();  // This we know is writable
        }

        if (!is_dir($dir)) {
            if (mkdir($dir) === false) {
                throw new Exception("Could not create cache directory '$dir'");
            }
        }

        $this->mPath = rtrim(realpath($dir), "\\/").DIRECTORY_SEPARATOR;

        /*
         * Periodic cleanup
         */
        if (mt_rand(0,100) == 100) {
            if ($handle = opendir($this->mPath)) {
                $time = time();

                while ($file = readdir($handle)) {
                    if (substr($file, 0, 6) == "mtime_") {
                        $cache = $this->mPath.substr($file, 6);
                        $mtime = $this->mPath.$file;
                        $maxlifetime = file_get_contents($mtime);

                        if ((filemtime($cache) + $maxlifetime) < $time) {
                            unlink($mtime);
                            unlink($cache);
                        }
                    }
                }

                closedir($handle);
            }
        }
    }

    /** @ignore */
    protected function write(string $key, string $value, int $expires=0, string $encKey=null): bool {
        $fileKey = sha1($key); // Key could contain non-filename characters
        $mtimeFile = $this->mPath."mtime_$fileKey.cache";
        $cacheFile = $this->mPath."$fileKey.cache";
        $status = false;

        if ($expires == 0 && is_file($mtimeFile)) {
            unlink($mtimeFile);

        } elseif ($expires > 0) {
            file_put_contents($mtimeFile, strval($expires));
        }

        if ($encKey !== null) {
            $status = file_put_contents($cacheFile, Crypt::encrypt($value, $encKey, false));

        } else {
            $status = file_put_contents($cacheFile, $value);
        }

        return $status !== false;
    }

    /** @ignore */
    protected function read(string $key, string $encKey=null) /*mixed*/ {
        $fileKey = sha1($key); // Key could contain non-filename characters
        $cacheFile = $this->mPath."$fileKey.cache";
        $data = null;

        if (is_file($cacheFile)) {
            if ($encKey !== null) {
                $data = Crypt::decrypt(file_get_contents($cacheFile), $encKey, false);

            } else {
                $data = file_get_contents($cacheFile);
            }
        }

        return $data;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function set(string $key, /*mixed*/ $value, int $expires=0, string $encKey=null): bool {
        if ($this->write($key, serialize($value), $expires, $encKey)) {
            $this->mCache[$key] = $value;

            return true;
        }

        return false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function setRaw(string $key, string $value, int $expires=0, string $encKey=null): bool {
        if ($this->write($key, $value, $expires, $encKey)) {
            $this->mCache[$key] = $value;

            return true;
        }

        return false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function get(string $key, /*mixed*/ $default=null, string $encKey=null) /*mixed*/ {
        $value = $this->mCache[$key] ?? null;

        if ($value === null) {
            $value = $this->read($key, $encKey);

            if ($value !== null) {
                $value = unserialize($value);
            }
        }

        return $value ?? $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function getRaw(string $key, string $default=null, string $encKey=null) /*string*/ {
        $value = $this->mCache[$key] ?? null;

        if ($value === null) {
            $value = $this->read($key, $encKey);
        }

        return $value ?? $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function remove(string $key): bool {
        $fileKey = sha1($key);
        $mtimeFile = $this->mPath."mtime_$fileKey.cache";
        $cacheFile = $this->mPath."$fileKey.cache";

        if (is_file($cacheFile)) {
            if (is_file($mtimeFile)) {
                unlink($mtimeFile);
            }

            unlink($cacheFile);
        }

        unset($this->mCache[$key]);

        return !isset($this->mCache[$key]) && !is_file($cacheFile);
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function flush(): bool {
        if ($handle = opendir($this->mPath)) {
            $status = true;

            while ($file = readdir($handle)) {
                if (substr($file, -6) == ".cache") {
                    $status = unlink($file);
                }
            }

            closedir($handle);

            return $status;

        } else {
            return false;
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function close() /*void*/ {
        // Nothing to do
    }
}
