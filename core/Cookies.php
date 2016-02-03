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

namespace core;

use api\libraries\collections\Bundle;
use api\libraries\Crypt;
use core\Runtime;
use Traversable;

/**
 * A Cookie collection class
 *
 * This should not be used as a regular collection class.
 * It is used by the core of this library to create a cookie class
 * that can work both as a cookie tool and at the same time act as
 * a collection and a regular array for cookie data.
 *
 * @package core
 */
class Cookies extends Bundle {

    protected /*string*/ $mPrefix;

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function __construct(Traversable $data=null) {
        $this->mData = [];
        $this->mPrefix = Runtime::$SETTINGS->getString("COOKIE_PREFIX", "IMPHP_");

        if ($_COOKIE != null) {
            foreach ($_COOKIE as $key => $value) {
                $this->mData[$key] = @unserialize($value);

                /*
                 * Do we have encrypted data?
                 */
                if ($this->mData[$key] === false && strcmp($value, "b:0;") !== 0) {
                    $this->mData[$key] = $value;
                }
            }
        }
    }

    /**
     * Set/Change a cookie
     *
     * This will both Set/Change the value within this Bundle,
     * and send cookie data to the client, except in cases where the client
     * is either a terminal or a web crawler.
     *
     * You can store anything within a cookie when using this class,
     * since data is serialized before sending it.
     *
     * @api
     *
     * @param string $key
     *      The name of the cookie
     *
     * @param mixed $value
     *      The value
     *
     * @param int $expires=0
     *      How long the cookie should live, '0' for forever
     *
     * @param bool $secure=false
     *      If set to true, the cookie will only be sent by the client on SSL connections
     *
     * @param string $domain=null
     *      Set a domain for the cookie
     *
     * @param string $path=null
     *      Set a path for the cookie, defaults to '/'
     *
     * @param string $cryptKey=null
     *      If set, the cookie will be encrypted using the libimphp Crypt library
     */
    /*Overwrite: Bundle*/
    public function set(string $key, /*mixed*/ $value, int $expires=0, bool $secure=false, string $domain=null, string $path=null, string $cryptKey=null) /*void*/ {
        if ($path == null) {
            $path = Runtime::$SETTINGS->getString("COOKIE_PATH", "/");
        }

        if ($domain == null) {
            $domain = Runtime::$SETTINGS->getString("COOKIE_DOMAIN", "");
        }

        /*
         * The key might already be hashed
         */
        if (!isset($this->mData[$key])) {
            $key = Crypt::hash($this->mPrefix.$key);
        }

        $this->mData[$key] = $value;

        if (!in_array(Runtime::$SYSTEM["REQUEST_CLIENT"], ["terminal", "crawler"])) {
            setcookie(
    			$key,
    			$cryptKey !== null ? Crypt::encrypt(serialize($value), $cryptKey, true) : serialize($value),
    			$expires,
    			$path,
    			$domain,
    			$secure,
                true
    		);
        }
    }

    /**
     * Get a cookie value
     *
     * Note that if you are getting data from an encrypted cookie,
     * it might throw a 'CryptException' if the data could not be
     * authenticated.
     *
     * @api
     *
     * @param string $key
     *      The name of the cookie
     *
     * @param mixed $default=null
     *      Default value to return if the cookie does not exist
     *
     * @param string $cryptKey=null
     *      Used to decrypt cookie data that was encrypted in the 'Set()' method
     */
    /*Overwrite: Bundle*/
    public function get(string $key, /*mixed*/ $default=null, string $cryptKey=null) /*mixed*/ {
        /*
         * The key might already be hashed
         */
        if (isset($this->mData[$key])) {
            return $cryptKey !== null ? unserialize(Crypt::decrypt($this->mData[$key], $cryptKey, true)) : $this->mData[$key];

        } else {
            $key = Crypt::hash($this->mPrefix.$key);

            if (isset($this->mData[$key])) {
                return $cryptKey !== null ? unserialize(Crypt::decrypt($this->mData[$key], $cryptKey, true)) : $this->mData[$key];
            }
        }

        return $default;
    }

    /**
     * Remove a cookie
     *
     * Removes both the Bundle data and sends a delete request to the client.
     * The arguments must match the once used to set the cookie.
     * If not, the client will in most cases not delete it, and it will be re-sent
     * on the next request.
     *
     * @api
     *
     * @param string $key
     *      The name of the cookie
     *
     * @param bool $secure=false
     *      If set to true, the cookie will only be sent by the client on SSL connections
     *
     * @param string $domain=null
     *      Set a domain for the cookie
     *
     * @param string $path=null
     *      Set a path for the cookie, defaults to '/'
     */
    /*Overwrite: Bundle*/
    public function remove(string $key, bool $secure=false, string $domain=null, string $path=null) /*void*/ {
        /*
         * The key might already be hashed
         */
        if (!isset($this->mData[$key])) {
            $key = Crypt::hash($this->mPrefix.$key);
        }

        if (isset($this->mData[$key])) {
            if ($path == null) {
                $path = Runtime::$SETTINGS->getString("COOKIE_PATH", "/");
            }

            if ($domain == null) {
                $domain = Runtime::$SETTINGS->getString("COOKIE_DOMAIN", "");
            }

            unset($this->mData[$key]);

            if (!in_array(Runtime::$SYSTEM["REQUEST_CLIENT"], ["terminal", "crawler"])) {
                setcookie(
        			$key,
        			"deleted",               // Hack for some older IE browsers
        			time()-(60*60*24*366),
        			$path,
        			$domain,
        			$secure,
                    true
        		);
            }
        }
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function containsKey(string $key): bool {
        return isset($this->mData[$key]) || isset($this->mData[Crypt::hash($this->mPrefix.$key)]);
    }

    /** @ignore */
    /*Overwrite: Bundle*/
    public function offsetExists(/*mixed*/ $key): bool {
        return is_string($key) && (isset($this->mData[$key]) || isset($this->mData[Crypt::hash($this->mPrefix.$key)]));
    }

    /** @ignore */
    /*Overwrite: Bundle*/
    public function offsetGet(/*string*/ $key) /*mixed*/ {
        if (!is_string($key)) {
            throw new LogicException("Map keys can only be of the type 'string', invalid type '".gettype($key)."'");
        }

        return $this->get($key);
    }

    /** @ignore */
    /*Overwrite: Bundle*/
    public function offsetSet(/*string*/ $key, /*mixed*/ $value ) /*void*/ {
        if (!is_string($key)) {
            throw new LogicException("Map keys can only be of the type 'string', invalid type '".gettype($key)."'");
        }

        $this->set($key, $value);
    }

    /** @ignore */
    /*Overwrite: Bundle*/
    public function offsetUnset(/*string*/ $key) /*void*/ {
        if (!is_string($key)) {
            throw new LogicException("Map keys can only be of the type 'string', invalid type '".gettype($key)."'");

        } else {
            $this->remove($key);
        }
    }
}
