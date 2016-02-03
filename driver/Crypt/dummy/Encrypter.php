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

namespace driver\Crypt\dummy;

use api\libraries\Crypt\Encrypter as BaseEncrypter;

/**
 * An dummy encrypter class
 *
 * This driver allows you to use the other features of the encrypter class
 * such as hashing, encoding/decoding etc without having a crypt module
 * such as MCrypt or OpenSSL installed.
 *
 * The encrypt/decrypt methods will still handle integrety signing and signature check,
 * but the data will not be encrypted.
 *
 * @package driver\Crypt\dummy
 */
class Encrypter extends BaseEncrypter {

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function driver(): string {
        return "dummy";
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function encrypt(string $data, string $key, bool $encode=true): string {
        $kdf = $this->kdf($key, $this->mHashSize, null, true);
        $data = $this->sign($data, $kdf);
        $data = $this->mask($data, $key);

        return $encode ? $this->encode($data) : $data;
    }

    /** @inheritdoc */
    /*Overwrite: BaseEncrypter*/
    public function decrypt(string $data, string $key, bool $decode=true): string {
        if ($decode) {
            $data = $this->decode($data);
        }

        $kdf = $this->kdf($key, $this->mHashSize, null, true);
        $data = $this->unmask($data, $key);
        $data = $this->unsign($data, $kdf);

        return $data;
    }
}
