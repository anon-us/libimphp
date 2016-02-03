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

namespace driver\Database\mysqli;

use mysqli;
use api\libraries\Database\Connection as BaseConnection;

/**
 * MySQLi driver connection class
 *
 * @package driver\Database\mysqli
 */
class Connection extends BaseConnection {

    /** @ignore */
    protected /*mysqli*/ $mDatabase;

    /** @ignore */
    protected /*string*/ $mConnectError;

    /** @ignore */
    protected /*int*/ $mConnectErrno;

    /** @ignore */
    protected /*string*/ $mQueryError = null;

    /** @ignore */
    protected /*int*/ $mQueryErrno = 0;

    /** @ignore */
    protected /*bool*/ $mIsConnected = false;

    /**
     * Construct a new connection to the database
     *
     * __Protocol__
     * ```
     * mysqli://[user[:password]@]host[:port]#database
     * ```
     *
     * @api
     *
     * @param string $protocol
     *      A protocol with the information needed to establish the connection
     */
    /*Overwrite: BaseConnection*/
    public function __construct(string $protocol) {
        $parsed = parse_url($protocol);
        $user = $parsed["user"] ?? null;
        $pass = $parsed["pass"] ?? null;
        $host = ($parsed["host"] ?? "").($parsed["path"] ?? "");
        $port = $uri["port"] ?? 3306;
        $db = $uri["fragment"] ?? null;

        $this->mConnectError = mysqli_connect_error();
        $this->mConnectErrno = mysqli_connect_errno();

        if ($this->mConnectErrno == 0) {
            $this->mDatabase->query("SET NAMES 'utf8'");

        } elseif ($db !== null) {
            $this->mDatabase = new mysqli($host, $user, $pass, null, $port);

            if (mysqli_connect_errno() == 0) {
                $this->mDatabase->set_charset("utf8");
                $this->mDatabase->query("CREATE DATABASE IF NOT EXISTS " . $this->mDatabase->escape_string($db) . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

                if ($this->mDatabase->errno == 0) {
                    $this->mDatabase->select_db($db);

                } else {
                    $this->mConnectError = $this->mDatabase->error;
                    $this->mConnectErrno = $this->mDatabase->errno;

                    $this->mDatabase->close();
                }
            }
        }

        $this->mIsConnected = $this->mConnectErrno == 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function enquire(string $sql, string $types=null, /*mixed...*/ &...$data) /*Result*/ {
        $result = false;
        $stmt = null;

        if ($types !== null) {
            if (($stmt = $this->mDatabase->prepare($sql)) !== false) {
                for ($i=0; $i < strlen($types), $i++) {
                    switch ($types[$i]) {
                        case "s": $stmt->bind_param("s", $data[$i]); break;
                        case "i": $stmt->bind_param("i", $data[$i]); break;
                        case "f": $stmt->bind_param("d", $data[$i]); break;
                        case "b": $stmt->bind_param("b", $data[$i]); break;

                        default: throw new SecurityException("Cannot bind unknown param '".$types[$i]."'");
                    }
                }

                if($stmt->execute() !== false) {
                    $result = $stmt->get_result();

                } else {
                    $stmt->close();
                }
            }

        } else {
            $result = $this->mDatabase->query($sql);
        }

        $this->mQueryError = $result !== false ? null : $this->mDatabase->error;
        $this->mQueryErrno = $result !== false ? 0 : $this->mDatabase->errno;

        return is_object($result) ? new Result($result, $stmt) : null;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function execute(string $sql, string $types=null, /*mixed...*/ &...$data): int {
        $result = false;

        if ($types !== null) {
            if (($stmt = $this->mDatabase->prepare($sql)) !== false) {
                for ($i=0; $i < strlen($types), $i++) {
                    switch ($types[$i]) {
                        case "s": $stmt->bind_param("s", $data[$i]); break;
                        case "i": $stmt->bind_param("i", $data[$i]); break;
                        case "f": $stmt->bind_param("d", $data[$i]); break;
                        case "b": $stmt->bind_param("b", $data[$i]); break;

                        default: throw new SecurityException("Cannot bind unknown param '".$types[$i]."'");
                    }
                }

                if ($stmt->execute() !== false) {
                    $result = true;
                }

                $stmt->close();
            }

        } else {
            if ($this->mDatabase->real_query($sql) !== false) {
                $result = true;
            }
        }

        $this->mQueryError = $result ? null : $this->mDatabase->error;
        $this->mQueryErrno = $result ? 0 : $this->mDatabase->errno;

        return $result ? $this->mDatabase->affected_rows : 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function connectError() /*string*/ {
        return $this->mConnectError;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function connectErrNo(): int {
        return $this->mConnectErrno;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function queryError() /*string*/ {
        return $this->mQueryError;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function queryErrNo(): int {
        return $this->mQueryErrno;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function isConnected(): bool {
        return $this->mIsConnected;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function platform(): string {
        return "MySQL";
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function driver(): string {
        return "mysqli";
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function close() /*void*/ {
        if ($this->mIsConnected) {
            $this->mDatabase->close();
            $this->mIsConnected = false;
        }
    }
}
