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

namespace driver\Session\file;

use api\libraries\Session\Handler as BaseHandler;

class Handler extends BaseHandler {

    /** @ignore */
    protected /*string*/ $mDir;
    protected /*string*/ $mFile;

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function __construct(string $sessId, int $maxlifetime) {
        parent::__construct($sessId, $maxlifetime);

        $path = session_save_path();
        $this->mDir = rtrim($path, "\\/").DIRECTORY_SEPARATOR;
        $this->mFile = $this->mDir."sess_$sessId";

        if (!is_dir($path)) {
            mkdir($path, 0770);
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function read(): string {
        return (string) @file_get_contents($this->mFile);
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function write(string $data): bool {
        return file_put_contents($this->mFile, $data) === false ? false : true;
    }

    /** @inheritdoc */
    /*Overwrite: BaseHandler*/
    public function gc() /*void*/ {
        $dir();

        if ($handle = opendir($this->mDir)) {
            $time = time();

            while ($file = readdir($handle)) {
                if (substr($file, 0, 5) == "sess_") {
                    $file = $this->mDir.$file;

                    if ($file != $this->mFile && (filemtime($file) + $this->mMaxLifeTime) < $time) {
                        unlink($file);
                    }
                }
            }

            closedir($handle);
        }
    }
}
