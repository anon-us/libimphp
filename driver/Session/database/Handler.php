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

namespace driver\Session\database;

use api\libraries\Session\Handler as BaseHandler;
use api\libraries\Database;
use core\Runtime;

class Handler extends BaseHandler {

    /** @ignore */
    private /*bool*/ $mUpdate = false;

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function read(): string {
        $data = null;

        if (Database::isConnected()) {
            $result = Database::select("sessions")->field("cData")->cond("cSessId", "s", $this->mSessId)->enquire();

            if ($result !== null) {
                if ($result->numRows() > 0) {
                    $row = $result->fetch();
                    $data = $row[0];

                    $this->mUpdate = true;
                }

                $result->destroy();
            }
        }

        Runtime::addLock("database");

        return (string) $data;
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function write(string $data): bool {
        $status = false;

        if (Database::isConnected()) {
            $time = time();

            if ($this->mUpdate) {
                $status = Database::update("sessions")
                    ->field("cData", "s", $data)
                    ->field("cTime", "i", time())
                    ->cond("cSessId", "s", $this->mSessId)
                    ->execute() > 0;

            } else {
                $status = Database::insert("sessions")
                    ->field("cData", "s", $data)
                    ->field("cTime", "i", time())
                    ->field("cSessId", "s", $this->mSessId)
                    ->execute() > 0;
            }
        }

        Runtime::removeLock("database");

        return $status;
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function gc() /*void*/ {
        if (Database::isConnected()) {
            $time = (time() - $this->mMaxLifeTime);

            Database::delete("sessions")->cond("cTime", "i", $time, "<")->cond("cSessId", "s", $this->mSessId, "!=")->execute();
        }
    }
}
