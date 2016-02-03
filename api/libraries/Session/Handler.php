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

namespace api\libraries\Session;

/**
 * Abstract class for Session drivers
 *
 * The libimphp session system is not actually added as a library.
 * It works as part of the core, but it has still been given driver
 * features in order to support different storage options.
 *
 * Additional drivers should extend from this class.
 *
 * @package api\libraries\Session
 */
abstract class Handler {

    /** @ignore */
    protected /*string*/ $mSessId;

    /** @ignore */
    protected /*string*/ $mMaxLifeTime;

    /**
     * Instantiate driver
     *
     * @param string $sessId
     *      The session id to prepare
     */
    public function __construct(string $sessId, int $maxlifetime) {
        $this->mSessId = $sessId;
        $this->mMaxLifeTime = $maxlifetime;
    }

    /**
     * Read session data from storage and return it
     *
     * @return string
     *      Should return the session data in pure form, not unserialized or other manipulations.
     *      The main library might have encrypted it, manipulating with it will break the signature.
     */
    abstract public function read(): string;

    /**
     * Write session data to storage
     *
     * @param string $data
     *      The session data in serialized or encrypted form
     *
     * @return bool
     *      True if the data was written successfully or False otherwise
     */
    abstract public function write(string $data): bool;

    /**
     * Request a garbage cleanup
     *
     * @param int $maxlifetime
     *      The current max lifetime for sessions in seconds
     */
    abstract public function gc() /*void*/;
}
