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

namespace api\libraries\Crypt;

use api\exceptions\CryptException;
use Exception;

if (!function_exists("mb_strlen")) {
    function mb_strlen(string $str, string $ignore=null) {
        return strlen($str);
    }
}

if (!function_exists("mb_substr")) {
    function mb_substr(string $str, int $start, int $length=null, string $ignore=null) {
        return $length !== null ? substr($str, $start, $length) : substr($str, $start);
    }
}

/**
 * Abstract encrypter class
 *
 * Drivers should extend from this class
 *
 * @package api\libraries\Crypt
 */
abstract class Encrypter {

    /** @ignore */
    protected /*string*/ $mCipher;

    /** @ignore */
    protected /*string*/ $mHash;

    /** @ignore */
    protected /*string*/ $mMode;

    /** @ignore */
    protected /*int*/ $mHashSize;

    /** @ignore */
    protected /*bool*/ $mTwoStep;

    /**
     * Get a new Encrypter Instance.
     *
     * @api
     *
     * @param string $cipher=null
     *      Cipher to use, or NULL to use the driver default
     *
     * @param string $hash=null
     *      Cipher to use, or NULL to use the driver default
     *
     * @param string $mode=null
     *      Cipher to use, or NULL to use the driver default
     *
     * @param bool $twoStep=false
     *      Cipher to use, or NULL to use the driver default
     */
    public function __construct(string $cipher=null, string $hash=null, string $mode=null, bool $twoStep=false) {
        $this->mCipher = $cipher ?? "rijndael-256";
        $this->mHash = $hash ?? "sha512";
        $this->mMode = $mode ?? "cbc";
        $this->mTwoStep = $twoStep;

        if (in_array($this->mHash, hash_algos())) {
            $this->mHashSize = strlen(hash($this->mHash, ""))/2;

        } else {
            throw new Exception("The hash '".$this->mHash."' is not available on this platform installation");
        }
    }

    /**
     * @api
     *
     * @return string
     *      The name of the driver
     */
    abstract public function driver(): string;

    /**
     * Encrypt data
     *
     * This method will automatically create a KDF key based on the key parsed to it.
     * It will also sign the data and mask it.
     *
     * @api
     *
     * @param string $data
     *      The data to encrypt
     *
     * @param string $key
     *      Key to use for the encryption
     *
     * @param bool $encode
     *      Run the encrypted data through the encoder before returning it
     *
     * @return string
     *      The encrypted, and possibly the encoded, data
     */
    abstract public function encrypt(string $data, string $key, bool $encode=true): string;

    /**
     * Decrypt data
     *
     * The instantiating arguments and the arguments here needs to match
     * the once used for the encryption process.
     *
     * This method will automatically re-generate the KDF key based on the key parsed to it.
     * It will also unmask and do a signature verification on the data.
     *
     * @api
     *
     * @param string $data
     *      The data to encrypt
     *
     * @param string $key
     *      Key to used for the encryption process
     *
     * @param bool $encode
     *      Run the encrypted data through the decoder. Only use this if it was selected during encryption
     *
     * @return string
     *      The decrypted data
     */
    abstract public function decrypt(string $data, string $key, bool $decode=true): string;

    /**
     * Create a hash sum
     *
     * Use the hash algorithm parsed during instantiation to create a hash of the parsed data
     *
     * @api
     *
     * @param string $data
     *      Data to hash
     *
     * @param string $key
     *      If specified, will create a keyed hash based on this key
     */
    public function hash(string $data, string $key=null, bool $raw=false): string {
        return $key !== null ? hash_hmac($this->mHash, $data, $key, $raw) : hash($this->mHash, $data, $raw);
    }

    /**
     * Generate a random password
     *
     * Generates a strong pseudo-random password that can be used for encryption, user passwords etc.
     * At least for encryption it is strongly recommented that this method be used.
     *
     * By allowing special characters, you can create a password string like: x$J\)=a3xn&P"3jk<839jZ@e:ONlJ=N4
     * By disallowing special characters, it will produce something like: dFkh3UodSIp87zIfXk7tS904spIrLW2D
     *
     * @api
     *
     * @param int $size=32
     *      The size of the password, defaults to 32 characters
     *
     * @param bool $special
     *      Allow special characters. Otherwise only numbers and letters are used.
     */
    public function password(int $size=32, bool $special=true): string {
        static $ascii = [
            [48, 57],
            [65, 90],
            [97, 122],
            [33, 64],
            [91, 126]
        ];

        if ($size < 1) {
            throw new Exception("You cannot create a password with 0 or less characters");
        }

        $password = "";
        $alpha = array_slice($ascii, 0, $special ? null : 3);
        $max = count($alpha)-1;

        /*
         * No mater how good an implementation, a computer cannot create anything random.
         * It can come close, but even something like 'random_int' has favorite numbers that are returned
         * more often than others. So to make this just a bit more random, we can at least make sure
         * to move the characters in those favorite locations around each time.
         */
        shuffle($alpha);

        for ($i=0; $i < $size; $i++) {
            $range = $alpha[random_int(0, $max)];
            $password .= chr(random_int($range[0], $range[1]));
        }

        return $password;
    }

    /**
     * Obscures data using a password and returns the obscured data as Base64 encoded
     *
     * This might seam as some sort of encryption, but it is not.
     * A clever algorithm will be able to retrive the data, it just makes it more difficult.
     * Especially if this is done on real encrypted data.
     *
     * It should only be used as an irritation together with an encryption.
     * Do not use this as the main security, because it provides very little in it self.
     *
     * @api
     *
     * @param string $data
     *      The data to encode
     *
     * @param string $key
     *      The key for masking
     *
     * @param bool $encode=true
     *      Whether to return a Base64 URI Safe string
     *
     * @return string
     *      The masked data
     */
    public function mask(string $data, string $key, bool $encode=true): string {
        $hash = hash($this->mHash, $key);
        $hashLen = strlen($hash);
        $dataLen = strlen($data);
        $masked = "";

        for ($i=0, $x=0; $i < $dataLen; ++$i, ++$x) {
            if ($x >= $hashLen) {
                $x = 0;
            }

            $masked .= chr((ord($data[$i]) + ord($hash[$x])) % 256);
        }

        return $encode ? $this->encode($masked) : $masked;
    }

    /**
     * Unmask data that has been masked using this class
     *
     * @api
     *
     * @param string $data
     *      The data to unmask
     *
     * @param string $key
     *      The key used for masking
     *
     * @param bool $decode=true
     *      Whether the data was encoded during masking
     *
     * @return string
     *      The unmasked data
     */
    public function unmask(string $data, string $key, bool $decode=true): string {
        if ($decode) {
            $data = $this->decode($data);
        }

        $hash = hash($this->mHash, $key);
        $hashLen = strlen($hash);
        $dataLen = strlen($data);
        $unmasked = "";

        for ($i=0, $x=0; $i < $dataLen; ++$i, ++$x) {
            if ($x >= $hashLen) {
                $x = 0;
            }

            $char = ord($data[$i]) - ord($hash[$x]);
            $unmasked .= $char < 0 ? chr(($char+256)) : chr($char);
        }

        return $unmasked;
    }

    /*
     * Encode data using URI Safe Base64
     *
     * @api
     *
     * @param string $data
     *      Data to encode
     */
    public function encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /*
     * Decode URI Safe Base64 encoded data
     *
     * @api
     *
     * @param string $data
     *      Data to decode
     */
    public function decode(string $data): string {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Pad data using proper PKCS#7 Padding
     *
     * @api
     *
     * @param string $data
     *      Data to pad
     *
     * @param int $blocksize
     *      Block size to pad for
     *
     * @return
     *      The PKCS#7 padded data
     */
    public function pad(string $data, int $blocksize): string {
        $pad = $blocksize - (mb_strlen($data, "8bit") % $blocksize);

            return ($data.str_repeat(chr($pad), $pad));
    }

    /**
     * Remove PKCS#7 Padding
     *
     * @api
     *
     * @param string $data
     *      Data to unpad
     *
     * @return
     *      The unpadded data
     */
    public function unpad(string $data): string {
        return substr($data, 0, -ord(substr($data, -1)));
    }

    /**
     * Create a proper encryption key
     *
     * This will generate an encryption key based on a regular user password
     *
     * The determined size is messured in raw bytes. That means that if you choose not to return
     * a raw output, you will get a hex representation of those raw bytes, which in turn means double
     * the size in real bytes as two hex equals one byte.
     *
     * @ignore
     *
     * @param string $password
     *      The password to create the key from
     *
     * @param int $size
     *      Size of the key to generate
     *
     * @param string $salt=null
     *      Salt value, recommented when possible
     *
     * @param bool $raw=false
     *      Whether to return data in hex or binary form
     *
     */
    public function kdf(string $password, int $size, string $salt=null, bool $raw=false): string {
        $rawHash = $salt !== null ? hash_hmac($this->mHash, $salt, $password, true) : hash($this->mHash, $password, true);
        $itt = ord($rawHash[0]);

        /*
         * This does not need extensive randomness.
         * We just don't want complete static itterations.
         */
        while (($itt = (int) ($itt/2)) > 10) {}

        if ($raw) {
            return hash_pbkdf2($this->mHash, $password, $rawHash, ($itt > 0 ? $itt : 2), $size, true);

        } else {
            return bin2hex(hash_pbkdf2($this->mHash, $password, $rawHash, ($itt > 0 ? $itt : 2), $size, true));
        }
    }

    /*
     * Adds signature to data
     *
     * The $salt parameter is not used to add salt value to the signature, no point.
     * It is used to provide the option of storing a non-secret salt value along with the signature
     * and have that validated along with the rest.
     *
     * @api
     *
     * @param string $data
     *      Data to sign
     *
     * @param string $key
     *      Key used for signing
     *
     * @param $salt=null
     *      Store some additional information for later use
     */
    public function sign(string $data, string $key, string $salt=null): string {
        $salt = $salt ?? "";
        $len = strlen($salt) > 0 ? strval(strlen($salt)) : "";

        if (strlen($len) > 255) {
            throw new CryptException("Salt exceeds is max length");
        }

        $lenlen = chr(strlen($len));

        return $lenlen.$len.$salt.hash_hmac($this->mHash, $salt.$data, $key, true).$data;
    }

    /*
     * Removes signature and return the original data
     *
     * Throws CryptException if the signature check fails
     *
     * @api
     *
     * @param string $data
     *      Data to unsign
     *
     * @param string $key
     *      Key used for signing
     *
     * @param string &$salt=""
     *      This is a reference parameter where the salt value will be written to
     */
    public function unsign(string $data, string $key, string &$salt=""): string {
        if ($this->mHashSize >= strlen($data)) {
            throw new CryptException("Data size is invalid");
        }

        $dataLenth = mb_strlen($data, "8bit");
        $saltLength = 1;

        if (mb_strlen($data, "8bit") > 0) {
            $lenlen = ord(mb_substr($data, 0, 1, "8bit"));

            if ($lenlen > 0) {
                if (($lenlen+1) < $dataLenth) {
                    $saltLength += $lenlen;
                    $len = intval(mb_substr($data, 1, $lenlen, "8bit"));

                    if (($len+$lenlen+1) < $dataLenth) {
                        $saltLength += $len;
                        $salt = mb_substr($data, $lenlen+1, $len, "8bit");

                    } else {
                        throw new CryptException("Data size is invalid");
                    }

                } else {
                    throw new CryptException("Data size is invalid");
                }
            }
        }

        if ($this->mHashSize+$saltLength >= strlen($data)) {
            throw new CryptException("Data size is invalid");
        }

        $hmac = mb_substr($data, $saltLength, $this->mHashSize, "8bit");
        $data = mb_substr($data, $this->mHashSize+$saltLength, null, "8bit");

        if (hash_equals($hmac, hash_hmac($this->mHash, $salt.$data, $key, true))) {
            return $data;

        } else {
            throw new CryptException("HMAC Check failed");
        }
    }

    /**
     * Get the salt value that was added with the signature
     *
     * Remember to do a signature validation before trusting the returned value
     *
     * @api
     *
     * @param string $data
     *      The data containing the salt value
     */
    public function getSignatureSalt(string $data) /*string*/ {
        $dataLenth = mb_strlen($data, "8bit");

        if (mb_strlen($data, "8bit") > 0) {
            $lenlen = ord(mb_substr($data, 0, 1, "8bit"));

            if ($lenlen > 0) {
                if (($lenlen+1) < $dataLenth) {
                    $len = intval(mb_substr($data, 1, $lenlen, "8bit"));

                    if (($len+$lenlen+1) < $dataLenth) {
                        return mb_substr($data, $lenlen+1, $len, "8bit");
                    }
                }

                throw new CryptException("Data size is invalid");
            }
        }

        return null;
    }

    /*
     * Performs a signature validation on data
     *
     * @api
     *
     * @param string $data
     *      Data to verify
     *
     * @param string $key
     *      Key used for signing
     *
     * @return bool
     *      True if the signature matches, False otherwise
     */
    public function checkSignature(string $data, string $key): bool {
        try {
            $this->unsign($data, $key);

        } catch (CryptException $e) {
            return false;
        }

        return true;
    }
}
