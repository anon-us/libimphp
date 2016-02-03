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

namespace driver\Cache\memcached;

use api\libraries\Crypt;
use api\libraries\Cache\Storage as BaseStorage;
use Exception;
use Memcached;

/**
 * Memcached based cache driver
 *
 * @package driver\Cache\memcached
 */
class Storage extends BaseStorage {

    protected /*Memcached*/ $mConnection;

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
    public function __construct(string $protocol=null) {
        if (!class_exists("Memcached", false)) {
            throw new Exception("Memcached is not installed or not enabled");
        }

        if ($protocol !== null) {
            if (($pos = strrpos($protocol, "#")) !== false) {
                $persistent = substr($protocol, $pos+1);
                $protocol = substr($protocol, 0, $pos);
            }

            if (($pos = strrpos($protocol, ":")) !== false) {
                $port = intval(substr($protocol, $pos+1));
                $protocol = substr($protocol, 0, $pos);
            }
        }

        $persistent = $persistent ?? null;
        $port = $port ?? 11211;
        $host = protocol ?? "127.0.0.1";

        /*
         * If host is a socket, port must be 0
         */
        if (is_file($host)) {
            $port = 0;
        }

        $this->mConnection = new Memcached($persistent);

        if ($persistent !== null) {
            $servers = $this->mConnection->getServerList();
            $found = false;

            foreach ($servers as $server) {
                if ($server["host"] == $host) {
                    $found = true; break;
                }
            }

            if (!$found) {
                if (!$this->mConnection->addServer($host, $port)) {
                    throw new Exception("Could not add memcached server '$servers:$port'");
                }
            }

        } else {
            if (!$this->mConnection->addServer($host, $port)) {
                throw new Exception("Could not add memcached server '$servers:$port'");
            }
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function set(string $key, /*mixed*/ $value, int $expires=0, string $encKey=null): bool {
        $status = false;

        if ($encKey !== null) {
            $status = $this->mConnection->set($key, Crypt::encrypt(serialize($value), $encKey, false), $expires);

        } else {
            $status = $this->mConnection->set($key, $value, $expires);
        }

        return $status !== false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function setRaw(string $key, string $value, int $expires=0, string $encKey=null): bool {
        $status = false;

        if ($encKey !== null) {
            $status = $this->mConnection->set($key, Crypt::encrypt($value, $encKey, false), $expires);

        } else {
            $status = $this->mConnection->set($key, $value, $expires);
        }

        return $status !== false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function get(string $key, /*mixed*/ $default=null, string $encKey=null) /*mixed*/ {
        $data = $this->mConnection->get($key);
        $status = $this->mConnection->getResultCode();

        if ($status !== Memcached::RES_NOTFOUND) {
            if ($encKey !== null) {
                $data = unserialize(Crypt::decrypt($data, $encKey, false));
            }

            return $data;
        }

        return $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function getRaw(string $key, string $default=null, string $encKey=null) /*string*/ {
        $data = $this->mConnection->get($key);
        $status = $this->mConnection->getResultCode();

        if ($status !== Memcached::RES_NOTFOUND) {
            if ($encKey !== null) {
                $data = Crypt::decrypt($data, $encKey, false);
            }

            return $data;
        }

        return $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function remove(string $key): bool {
        $status = $this->mConnection->delete($key);

        return $status || $this->mConnection->getResultCode() === Memcached::RES_NOTFOUND ? true : false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function flush(): bool {
        return $this->mConnection->flush();
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function close() /*void*/ {
        /*
         * Either it was chosen as persistent in which case it should not close,
         * or it will close by itself at the end of the request.
         */
    }
}
