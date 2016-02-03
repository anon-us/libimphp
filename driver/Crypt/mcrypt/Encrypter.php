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

namespace driver\Crypt\mcrypt;

use api\libraries\Crypt\Encrypter as BaseEncrypter;
use api\exceptions\CryptException;
use Exception;

/**
 * An encrypter class that uses MCrypt
 *
 * If possible, you should consider using OpenSSL instead.
 * MCrypt is both slow and not developed much anymore.
 *
 * @package driver\Crypt\mcrypt
 */
class Encrypter extends BaseEncrypter {

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function __construct(string $cipher=null, string $hash=null, string $mode=null, bool $twoStep=true) {
        parent::__construct($cipher, $hash, $mode, $twoStep);

        if (!function_exists("mcrypt_list_algorithms")) {
            throw new Exception("Could not find the MCrypt module");

        } elseif (!in_array($this->mCipher, mcrypt_list_algorithms())) {
            throw new Exception("The cipher '".$this->mCipher."' is not supported by this platform installation");

        } elseif (!in_array($this->mMode, mcrypt_list_modes())) {
            throw new Exception("The block mode '".$this->mMode."' is not supported by this platform installation");
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function driver(): string {
        return "mcrypt";
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function encrypt(string $data, string $key, bool $encode=true): string {
        if (strlen($key) == 0) {
            throw new CryptException("You need to supply a password for the encryption");
        }

        $fd = mcrypt_module_open($this->mCipher, "", $this->mMode, "");

        if (is_resource($fd)) {
            $ivSize = mcrypt_enc_get_iv_size($fd);
            $keySize = mcrypt_enc_get_key_size($fd);
            $blocksize = mcrypt_enc_get_block_size($fd);

            /*
             * The chosen algorithm might not always want the IV, but we still need one for our own checks and key generation
             */
            if ($keySize <= 0) {
                throw new CryptException("Key Size is to small");

            } elseif ($ivSize <= 0) {
                $ivSize = $keySize;
            }

            if ($blocksize <= 0) {
                throw new CryptException("Invalid block size");
            }

            $iv = random_bytes($ivSize);
            $kdf = $this->kdf($key, $keySize, $iv, true);

            if ($this->mTwoStep) {
                $data = $this->sign($data, $key);

            } else {
                $data = "raw:$data";
            }

            $result = mcrypt_generic_init($fd, $kdf, $iv);

            if ($result !== 0) {
                throw new CryptException("Initiation error ($result)");
            }

            $data = mcrypt_generic($fd, $this->pad($data, $blocksize));
            $data = $this->sign($data, $kdf, $iv);

            mcrypt_generic_deinit($fd);
            mcrypt_module_close($fd);

            return $this->mask($data, $key, $encode);

        } else {
            throw new Exception("Could not open the MCrypt module");
        }

        return null;
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function decrypt(string $data, string $key, bool $decode=true): string {
        if (strlen($key) == 0) {
            throw new CryptException("You need to supply a password for the decryption");
        }

        $fd = mcrypt_module_open($this->mCipher, "", $this->mMode, "");

        if (is_resource($fd)) {
            $ivSize = mcrypt_enc_get_iv_size($fd);
            $keySize = mcrypt_enc_get_key_size($fd);

            if ($keySize <= 0) {
                throw new CryptException("Key Size is to small");

            } elseif ($ivSize <= 0) {
                $ivSize = $keySize;
            }

            if (mb_strlen($data) <= ($ivSize+$this->mHashSize)) {
                throw new CryptException("Data size is invalid");
            }

            $data = $this->unmask($data, $key, $decode);
            $iv = $this->getSignatureSalt($data);
            $kdf = $this->kdf($key, $keySize, $iv, true);
            $data = $this->unsign($data, $kdf);

            $result = mcrypt_generic_init($fd, $kdf, $iv);

            if ($result !== 0) {
                throw new CryptException("Initiation error ($result)");
            }

            $data = $this->unpad(mdecrypt_generic($fd, $data));

            mcrypt_generic_deinit($fd);
            mcrypt_module_close($fd);

            if ($this->mTwoStep) {
                return $this->unsign($data, $key);

            } elseif (strcmp("raw:", mb_substr($data, 0, 4, "8bit")) === 0) {
                return mb_substr($data, 4, null, "8bit");
            }

            throw new CryptException("Veryfication failed");

        } else {
            throw new Exception("Could not open the MCrypt module");
        }
    }
}
