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

namespace api\libraries\Cache;

/**
 * Abstract Storage class for Cache drivers
 *
 * @package api\libraries\Cache
 */
abstract class Storage {

    /**
     * Create a new Storage instance
     *
     * @param string $protocol
     *      A protocol. This is driver specific.
     */
    public function __construct(string $protocol) {

    }

    /**
     * Add a data to the cache
     *
     * @api
     *
     * @param string $key
     *      A key identifying the data storage
     *
     * @param mixed $value
     *      The value to store
     *
     * @param int $expires=0
     *      When the data should automatically expire in seconds
     *
     * @param string $encKey=null
     *      Encryption key, if set, the data will be encrypted using Crypt default config
     *
     * @return bool
     *      True if the data was added, False on error
     */
    abstract public function set(string $key, /*mixed*/ $value, int $expires=0, string $encKey=null): bool;

    /**
     * Get a data from the cache
     *
     * If you added cache data using `setRaw`, this can return the data
     * without attempting to decode it. If you did not set the data using `setRaw`,
     * It's unnown what you will get, depending on how the driver stores the data.
     *
     * @api
     *
     * @param string $key
     *      A key identifying the data storage
     *
     * @param mixed $default
     *      Default value to return if key does not exist
     *
     * @param string $encKey=null
     *      Encryption key, if set, the data will be encrypted using Crypt default config
     *
     * @return mixed
     *      The data, or NULL if data does not exist
     */
    abstract public function get(string $key, /*mixed*/ $default=null, string $encKey=null) /*mixed*/;

    /**
     * Add a string without encoding it
     *
     * Some driver might encode data with things like serialize.
     * This ensures that the data is written to cache as it is.
     * It's useful for data that has been encoded in other ways before adding it to the cache.
     *
     * @api
     *
     * @param string $key
     *      A key identifying the data storage
     *
     * @param string $value
     *      The value to store
     *
     * @param int $expires=0
     *      When the data should automatically expire in seconds
     *
     * @param string $encKey=null
     *      Encryption key, if set, the data will be encrypted using Crypt default config
     *
     * @return bool
     *      True if the data was added, False on error
     */
    abstract public function setRaw(string $key, string $value, int $expires=0, string $encKey=null): bool;

    /**
     * Get a string without decoding it
     *
     * If you added cache data using `setRaw`, this can return the data
     * without attempting to decode it. If you did not set the data using `setRaw`,
     * It's unnown what you will get, depending on how the driver stores the data.
     *
     * @api
     *
     * @param string $key
     *      A key identifying the data storage
     *
     * @param string $default
     *      Default value to return if key does not exist
     *
     * @param string $encKey=null
     *      Encryption key, if set, the data will be encrypted using Crypt default config
     *
     * @return bool
     *      The raw data, or NULL if data does not exist
     */
    abstract public function getRaw(string $key, string $default=null, string $encKey=null) /*string*/;

    /**
     * Remove data from the cache
     *
     * @api
     *
     * @param string $key
     *      A key identifying the data storage
     */
    abstract public function remove(string $key): bool;

    /**
     * Clear the entire cache
     *
     * @api
     */
    abstract public function flush(): bool;

    /**
     * Close the cache connection 
     *
     * @api
     */
    abstract public function close() /*void*/;
}
