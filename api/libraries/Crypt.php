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

use driver\Crypt\mcrypt\Encrypter as McryptEncrypter;
use driver\Crypt\openssl\Encrypter as OpensslEncrypter;
use driver\Crypt\dummy\Encrypter as DummyEncrypter;
use api\exceptions\ConnectionException;
use core\Runtime;

/**
 * Base class for working with the default encrypter
 *
 * @package api\libraries
 */
class Crypt {

    /** @ignore */
    protected static /*Encrypter*/ $oEncrypter = null;

    /**
     * Get the default Encrypter instance
     *
     * @api
     * @see api\libraries\Crypt\Encrypter
     *
     * @return Encrypter
     *      The default encrypter
     */
    public static function getInstance() /*Encrypter*/ {
        if (static::$oEncrypter === null) {
            $driver = Runtime::$SETTINGS->getString("CRYPT_DRIVER", "dummy");
            $cipher = Runtime::$SETTINGS->getString("CRYPT_CIPHER");
            $hash = Runtime::$SETTINGS->getString("CRYPT_HASH");
            $mode = Runtime::$SETTINGS->getString("CRYPT_MODE");
            $twoStep = Runtime::$SETTINGS->getBoolean("CRYPT_TWO_STEP_AUTH");

            static::$oEncrypter = static::newInstance($driver, $cipher, $hash, $mode, $twoStep);
		}

		return static::$oEncrypter;
    }

    /**
     * Create a new encrypter instance
     *
     * @api
     * @see api\libraries\Crypt\Encrypter
     *
     * @param string $driver
     *      The encrypter driver to use
     *
     * @param string $cipher
     *      Cipher to use (needs to be supported by the driver)
     *
     * @param string $hash
     *      Hash to use
     *
     * @param string $mode
     *      Block mode to use (needs to be supported by the driver)
     *
     * @param string $twoStep
     *      Enable 2-step authentication (needs to be supported by the driver)
     *
     * @return Encrypter
     *      The new encrypter
     */
    public static function newInstance(string $driver=null, string $cipher=null, string $hash=null, string $mode=null, bool $twoStep=true) /*Encrypter*/ {
		$encrypter = null;

        if ($driver === null) {
            $driver = "dummy";
        }

		switch ($driver) {
			case "openssl":
				$encrypter = new OpensslEncrypter($cipher, $hash, $mode, $twoStep); break;

            case "mcrypt":
                $encrypter = new McryptEncrypter($cipher, $hash, $mode, $twoStep); break;

            case "dummy":
                $encrypter = new DummyEncrypter($cipher, $hash, $mode, $twoStep); break;

			default:
                /*
                 * Let modules add namespace hooks for missing drivers
                 */
                $class = "driver\Crypt\\".$driver."\Encrypter";

                if (Runtime::loadClassFile($class)) {
                    $encrypter = (new ReflectionClass($class))->newInstance($cipher, $hash, $mode, $twoStep);
                }
		}

		return $encrypter;
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#password()
     */
    public static function password(int $size=32, bool $special=true): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->password($size, $special);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#driver()
     */
    public static function driver(): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->driver();
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#hash()
     */
    public static function hash(string $data, string $key=null): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->hash($data, $key);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#encrypt()
     */
    public static function encrypt(string $data, string $key, bool $encode=true): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->encrypt($data, $key, $encode);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#decrypt()
     */
    public static function decrypt(string $data, string $key, bool $decode=true): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->decrypt($data, $key, $decode);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#mask()
     */
    public static function mask(string $data, string $key, bool $encode=true): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->mask($data, $key, $encode);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#unmask()
     */
    public static function unmask(string $data, string $key, bool $decode=true): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->unmask($data, $key, $decode);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#encode()
     */
    public static function encode(string $data): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->encode($data);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#decode()
     */
    public function decode(string $data): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->decode($data);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#pad()
     */
    public function pad(string $data, int $blocksize): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->pad($data, $blocksize);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#unpad()
     */
    public static function unpad(string $data): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->unpad($data);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#sign()
     */
    public static function sign(string $data, string $key, string $salt=null): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->sign($data, $key, $salt);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#unsign()
     */
    public static function unsign(string $data, string $key, string &$salt=""): string {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->unsign($data, $key, $salt);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#getSignatureSalt()
     */
    public static function getSignatureSalt(string $data) /*string*/ {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->getSignatureSalt($data);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }

    /**
     * @api
     * @see api\libraries\Crypt\Encrypter#checkSignature()
     */
    public static function checkSignature(string $data, string $key): bool {
        $instance = static::getInstance();

        if ($instance !== null) {
    		return $instance->checkSignature($data, $key);
        }

        throw new ConnectionException("Attempt to make call on dead resource");
    }
}
