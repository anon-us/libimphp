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

namespace driver\Crypt\openssl;

use api\libraries\Crypt\Encrypter as BaseEncrypter;
use api\exceptions\CryptException;
use Exception;

/**
 * An encrypter class that uses OpenSSL
 *
 * @package driver\Crypt\openssl
 */
class Encrypter extends BaseEncrypter {

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function __construct(string $cipher=null, string $hash=null, string $mode=null, bool $twoStep=false) {
        parent::__construct($cipher, $hash, $mode, $twoStep);

        $this->mCipher = $cipher ?? "AES-256";
        $this->mMode = $mode ?? "CBC";

        if (!function_exists("openssl_get_cipher_methods")) {
            throw new Exception("Could not find the OpenSSL module");

        } elseif (!in_array(strtoupper($this->mCipher."-".$this->mMode), openssl_get_cipher_methods())) {
            throw new Exception("The cipher '".strtoupper($this->mCipher."-".$this->mMode)."' is not supported by this platform installation");
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function driver(): string {
        return "openssl";
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function encrypt(string $data, string $key, bool $encode=true): string {
        if (strlen($key) == 0) {
            throw new CryptException("You need to supply a password for the encryption");
        }

        $method = strtoupper($this->mCipher."-".$this->mMode);
        $ivSize = openssl_cipher_iv_length($method);

        if ($ivSize <= 0) {
            $ivSize = $this->mHashSize;
        }

        $iv = random_bytes($ivSize);
        $kdf = $this->kdf($key, $this->mHashSize, $iv, true);

        if ($this->mTwoStep) {
            $data = $this->sign($data, $key);

        } else {
            $data = "raw:$data";
        }

        $data = openssl_encrypt($data, $method, $kdf, OPENSSL_RAW_DATA, $iv);

        if ($data === false) {
            throw new CryptException("Error during encryption: " . openssl_error_string());
        }

        $data = $this->sign($data, $kdf, $iv);

        return $this->mask($data, $key, $encode);
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function decrypt(string $data, string $key, bool $decode=true): string {
        if (strlen($key) == 0) {
            throw new CryptException("You need to supply a password for the decryption");
        }

        $method = strtoupper($this->mCipher."-".$this->mMode);
        $data = $this->unmask($data, $key, $decode);
        $iv = $this->getSignatureSalt($data);
        $kdf = $this->kdf($key, $this->mHashSize, $iv, true);
        $data = $this->unsign($data, $kdf);
        $data = openssl_decrypt($data, $method, $kdf, OPENSSL_RAW_DATA, $iv);

        if ($data === false) {
            throw new CryptException("Error during decryption: " . openssl_error_string());
        }

        if ($this->mTwoStep) {
            return $this->unsign($data, $key);

        } elseif (strcmp("raw:", mb_substr($data, 0, 4, "8bit")) === 0) {
            return mb_substr($data, 4, null, "8bit");
        }

        throw new CryptException("Veryfication failed");
    }
}
