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

namespace driver\Session\cache;

use api\libraries\Session\Handler as BaseHandler;
use api\libraries\Cache;
use core\Runtime;

class Handler extends BaseHandler {

    /** @ignore */
    private /*bool*/ $mUpdate = false;

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function read(): string {
        /*
         * If cache uses things like database etc.
         * this will lock those things from disconnection during shutdown
         * until sessions has been re-cached.
         */
        Runtime::addLock("cache");

        return (string) Cache::getRaw($this->mSessId);
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function write(string $data): bool {
        $status = Cache::setRaw($this->mSessId, $data, (time() + $this->mMaxLifeTime));

        /*
         * Allow cache to lift it's own locks
         */
        Runtime::removeLock("cache");

        return $status;
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function gc() /*void*/ {
        /*
         * Cache is handling lifetimes itself
         */
    }
}
