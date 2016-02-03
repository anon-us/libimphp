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

namespace driver\Cache\database;

use api\libraries\Database;
use api\libraries\Crypt;
use api\libraries\Cache\Storage as BaseStorage;
use Exception;

/**
 * Memcached based cache driver
 *
 * @package driver\Cache\database
 */
class Storage extends BaseStorage {

    protected /*Connection*/ $mConnection;

    protected /*QueryBuilder*/ $mQueryReplace = null;
    protected /*QueryBuilder*/ $mQuerySelect = null;

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
        if ($protocol !== null) {
            $this->mConnection = Database::newInstance($protocol);

        } else {
            $this->mConnection = Database::getInstance();
        }

        if ($this->mConnection === null || !$this->mConnection->isConnected()) {
            throw new Exception("Could not connect to the database '$protocol'");
        }

        if (mt_rand(0, 100) == 100) {
            $time = time();

            $this->mConnection->delete("cache")->cond("cExpires", "i", time(), "<")->cond("cExpires", "i", 0, ">")->execute();
        }
    }

    /**
     * Cache and reuse SQL Generated Query
     *
     * @ignore
     */
    protected function replaceCache(string $key, string $value, int $expires): int {
        if ($this->mQueryReplace === null) {
            /*
             * Cache values is often updated, and multiple values might be
             * updated during the same request, so we store the query for re-use
             */
            $this->mQueryReplace = $this->mConnection->update("cache")
                ->ignore() // Do not produce errors if the row does not exist
                ->addSegmentIds("expires", "data", "key")
                ->field("cExpires", "i", null)
                ->field("cData", "s", null)
                ->cond("cKey", "s", null)
                ->compile();
        }

        $tatus = $this->mQueryReplace
            ->setSegmentInput("key", $key)
            ->setSegmentInput("data", $value)
            ->setSegmentInput("expires", $expires)
            ->execute();

        /*
         * If this is a new key, simply insert it.
         */
        if ($tatus == 0) {
            $tatus = $this->mConnection->insert("cache")
                ->field("cExpires", "i", $expires)
                ->field("cData", "s", $value)
                ->cond("cKey", "s", $key)
                ->execute();
        }

        return $tatus;
    }

    /**
     * Cache and reuse SQL Generated Query
     *
     * @ignore
     */
    protected function selectCache(string $key) /*Result*/ {
        if ($this->mQuerySelect === null) {
            /*
             * Retrieving cache values if one of the operations used most often.
             * So we store the query for re-use across the request.
             */
            $this->mQuerySelect = $this->mConnection->select("cache")
                ->field("cData")
                ->addSegmentIds("data")
                ->cond("cKey", "s", null)
                ->compile();
        }

        return $this->mQuerySelect->setSegmentInput("data", $key)->enquire();
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function set(string $key, /*mixed*/ $value, int $expires=0, string $encKey=null): bool {
        if ($encKey !== null) {
            $value = Crypt::encrypt(serialize($value), $encKey, true);

        } else {
            $value = serialize($value);
        }

        if ($expires > 0) {
            $expires = time()+$expires;
        }

        return $this->replaceCache($key, $value, $expires) > 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function setRaw(string $key, string $value, int $expires=0, string $encKey=null): bool {
        if ($encKey !== null) {
            $value = Crypt::encrypt($value, $encKey, true);
        }

        if ($expires > 0) {
            $expires = time()+$expires;
        }

        return $this->replaceCache($key, $value, $expires) > 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function get(string $key, /*mixed*/ $default=null, string $encKey=null) /*mixed*/ {
        $result = $this->selectCache($key);

        if ($result !== null && $result->numRows() > 0) {
            $row = $result->fetch();

            if ($encKey !== null) {
                $data = unserialize(Crypt::decrypt($row[0], $encKey, true));

            } else {
                //echo "Row: ".$row[0]; die();
                $data = unserialize($row[0]);
            }
        }

        if ($result !== null) {
            $result->destroy();
        }

        return $data ?? $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function getRaw(string $key, string $default=null, string $encKey=null) /*string*/ {
        $result = $this->selectCache($key);

        if ($result !== null && $result->numRows() > 0) {
            $row = $result->fetch();

            if ($encKey !== null) {
                $data = Crypt::decrypt($row[0], $encKey, true);

            } else {
                $data = $row[0];
            }
        }

        if ($result !== null) {
            $result->destroy();
        }

        return $data ?? $default;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function remove(string $key): bool {
        $this->mConnection->delete("cache")->cond("cKey", "s", $key)->execute();

        return true;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function flush(): bool {
        $this->mConnection->delete("cache")->execute();

        return true;
    }

    /** @inheritdoc */
    /*Overwrite: BaseStorage*/
    public function close() /*void*/ {
        $this->mConnection->close();
    }
}
