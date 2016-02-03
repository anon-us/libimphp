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

use api\libraries\Crypt;
use api\libraries\collections\Bundle;
use api\libraries\collections\Vector;
use driver\Session\file\Handler as FileHandler;
use driver\Session\database\Handler as DBHandler;
use driver\Session\cache\Handler as CacheHandler;
use core\Runtime;
use Traversable;
use Exception;

/**
 * A Session collection class
 *
 * This should not be used as a regular collection class.
 * It is used by the core of this library to create a Session class
 * that can work both as a Session tool and at the same time act as
 * a collection and a regular array for Session data.
 *
 * The class is made to reduce resources by not loading any session data
 * until it is needed. During requests where no session data is accessed,
 * this class will do almost nothing. No reading or writing to the session storage.
 *
 * At the same time it is made to autoload data, meaning that no session start
 * call is required before attempting to read or store data.
 *
 * @package core
 */
class Session extends Bundle {

    /** @ignore */
    protected /*string*/ $mSessId;

    /** @ignore */
    protected /*Handler*/ $mSessHandler = null;

    /** @ignore */
    protected /*bool*/ $mSessReady = false;

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function __construct(Traversable $data=null) {
        $this->mData = [];

        /*
         * We don't need/want PHP's session system. We do however need the $_SESSION variable,
         * and we don't need PHP saving it's content where it should not be saved. So we close it
         * if it is set to autostart.
         */
        if(in_array(strtolower(ini_get("session.auto_start")), ["on", "true", "1"])) {
            trigger_error("You should disable Session Auto Start while running this library", E_USER_NOTICE);

            if ($_SESSION == $this) {
                $_SESSION = [];

                session_unset();
                session_destroy();

                $_SESSION = $this;

            } else {
                session_unset();
                session_destroy();
            }

            /*
             * This function removes the first header in the list that matches the name.
             * As session_autostart is executed before anything else, it's session cookie
             * should be the first in the list.
             */
            header_remove("Set-Cookie");
        }

        /*
         * Register a receiver used to write data back to session storage
         */
         Runtime::addShutdownReceiver([$this, "writeBack"]);

         $useSSL = Runtime::$SETTINGS->getBoolean("SECURITY_SSL", false);
         $isSSL = Runtime::$SYSTEM->getBoolean("CONNECTION_SSL", false);
         $cookieName = $useSSL && $isSSL ? "IMPHP_SESSID_SSL" : "IMPHP_SESSID";
         $cryptKey = null;

         if (Runtime::$SETTINGS->getBoolean("SESSION_ENCRYPT_COOKIE")) {
             $cryptKey = Runtime::$SETTINGS->getString("SECURITY_PASSWD");
         }

         try {
             $this->mSessId = Runtime::$COOKIE->get($cookieName, null, $cryptKey);

         /*
          * If anything is wrong, start a new session
          */
         } catch (Exception $e) {}

         if ($this->mSessId == null) {
             $this->mSessId = Crypt::hash(Crypt::password().time());

             Runtime::$COOKIE->set($cookieName, $this->mSessId, 0, ($useSSL && $isSSL), null, null, $cryptKey);
         }
    }

    /** @ignore */
    public function readBack() /*void*/ {
        if (!$this->mSessReady && !in_array(Runtime::$SYSTEM["REQUEST_CLIENT"], ["terminal", "crawler"])) {
            $driver = Runtime::$SETTINGS->getString("SESSION_DRIVER", "file");
            $maxlife = Runtime::$SETTINGS->getInt("SESSION_EXPIRES", (24*60*60));

            switch ($driver) {
                case "file":
                    $this->mSessHandler = new FileHandler($this->mSessId, $maxlife); break;

                case "database":
                    $this->mSessHandler = new DBHandler($this->mSessId, $maxlife); break;

                case "cache":
                    $this->mSessHandler = new CacheHandler($this->mSessId, $maxlife); break;

                default:
                    $class = "driver\Session\\".$driver."\Handler";

                    if (Runtime::loadClassFile($class)) {
                        $this->mSessHandler = (new ReflectionClass($class))->newInstance($this->mSessId, $maxlife);
                    }
            }

            if ($this->mSessHandler === null) {
                throw new Exception("Could find the Session Driver '".$driver."'");
            }

            if (mt_rand(0, 100) == 1) {
                $this->mSessHandler->gc();
            }

            $data = $this->mSessHandler->read();
            $this->mData = !empty($data) ? @unserialize($data) : [];

            if (!is_array($this->mData)) {
                if (Runtime::$SETTINGS->getBoolean("SESSION_ENCRYPT_DATA")) {
                    $cryptKey = Runtime::$SETTINGS->getString("SECURITY_PASSWD");

                    if (!empty($cryptKey)) {
                        $this->mData = @unserialize(Crypt::decrypt($data, $cryptKey, true));

                        if (!is_array($this->mData)) {
                            $this->mData = [];
                        }
                    }

                } else {
                    $this->mData = [];
                }
            }
        }

        $this->mSessReady = true;
    }

    /** @ignore */
    public function writeBack() /*void*/ {
        if (!Runtime::hasLock("session")) {
            if ($this->mSessReady && !in_array(Runtime::$SYSTEM["REQUEST_CLIENT"], ["terminal", "crawler"])) {
                $data = serialize($this->mData);

                if (Runtime::$SETTINGS->getBoolean("SESSION_ENCRYPT_DATA")) {
                    $cryptKey = Runtime::$SETTINGS->getString("SECURITY_PASSWD");

                    if (!empty($cryptKey)) {
                        $data = Crypt::encrypt($data, $cryptKey, true);
                    }
                }

                $this->mSessHandler->write($data);
            }

            $this->mSessReady = false;

        } else {
            Runtime::addLockCallback("session", [$this, "writeBack"]);
        }
    }

    /**
     * Get the current session id
     *
     * @api
     *
     * @return string
     *      The session id
     */
    public function getSessId(): string {
        return $this->mSessId;
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function set(string $key, /*mixed*/ $value) /*void*/ {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        $this->mData[$key] = $value;
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function get(string $key, /*mixed*/ $default=null) /*mixed*/ {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return $this->mData[$key] ?? $default;
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function remove(string $key) /*void*/ {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        if (isset($this->mData[$key])) {
            unset($this->mData[$key]);
        }
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function getIterator(): Traversable {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return new ArrayIterator($this->mData);
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function contains(/*mixed*/ $value): bool {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return in_array($value, $this->mData[$key], true);
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function containsKey(string $key): bool {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return isset($this->mData[$key]);
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function getValues(): Vector {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return new Vector(array_values($this->mData));
    }

    /** @inheritdoc */
    /*Overwrite: Bundle*/
    public function getKeys(): Vector {
        if (!$this->mSessReady) {
            $this->readBack();
        }

        return new Vector(array_keys($this->mData));
    }

    /** @ignore */
    /*Overwrite: Bundle*/
    public function offsetExists(/*mixed*/ $key): bool {
        return $this->containsKey($key);
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
