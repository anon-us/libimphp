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

namespace driver\Auth\imphp;

use api\libraries\collections\ImmVector;
use api\libraries\Auth\User as BaseUser;
use api\libraries\Database;
use core\Runtime;

/**
 * A User class for the Auth library
 *
 * @package driver\Auth\imphp
 */
class User extends BaseUser {

    /** @ignore */
    protected /*string*/ $mUserName;

    /** @ignore */
    protected /*int*/ $mUserId;

    /** @ignore */
    protected /*ImmVector*/ $mUserGroups = null;

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function __construct() {
        $this->mUserId = Runtime::$SESSION->getInt("_im_userid", 0);

        if ($this->mUserId < -1) {
            throw new SecurityException("User id '".$this->mUserId."' is lower than allowed");
        }

        switch ($this->mUserId) {
            case -1:
                $rootPasswd = Runtime::$SETTINGS["AUTH_ROOT_PASSWD"];

                if (!empty($rootPasswd)) {
                    $this->mUserName = "root";

                    break;

                } else {
                    $this->mUserId = 0;
                }

                /*
                 * GoTo 'case 0'
                 */

            case 0:
                $this->mUserName = "guest";

                /*
                 * Allows terminal to login using cli arguments.
                 * This is useful when running cron jobs that are group protected.
                 * CLI information has it's own container within Runtime and is not placed any where
                 * near input from the browser to avoid any form of outside bypassing.
                 */
                if (Runtime::$SYSTEM["REQUEST_CLIENT"] == "terminal" && Runtime::$SETTINGS->getBoolean("AUTH_ALLOW_CLI")) {
                    $password = Runtime::$CLI["passwd"];
                    $username = Runtime::$CLI["user"];

                    if ($password !== null || $username !== null) {
                        if (!$this->login($password, $username)) {
                            Runtime::quit(["Authorization Failed", "Usage: --passwd [password] --user [username]"], 1);
                        }
                    }
                }

                break;

            default:
                if (Database::isConnected()) {
                    $result = Database::select("users")->field("cName")->cond("cId", "i", $this->mUserId)->enquire();

                    if ($result !== null) {
                        if ($result->numRows() > 0) {
                            $row = $result->fetch();

                            if ($row !== null) {
                                $this->mUserName = $row[0];
                            }
                        }

                        $result->destroy();
                    }
                }
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function login(string $username, string $password): bool {
        $chkName = strtolower($username);

        if ($this->mUserId == 0 && !empty($username) && !empty($password)) {
            if ($chkName == "root") {
                $rootPasswd = Runtime::$SETTINGS["AUTH_ROOT_PASSWD"];

                if (!empty($rootPasswd) && password_verify($password, $rootPasswd)) {
                    $this->mUserId = -1;
                    $this->mUserName = "root";
                    $this->mUserGroups = null;

                    Runtime::$SESSION["_im_userid"] = $this->mUserId;

                    return true;
                }

            } elseif ($chkName != "guest") {
                if (Database::isConnected()) {
                    $result = Database::select("users")->fields("cName", "cPassword", "cId")->cond("cName", "s", $username)->enquire();

                    if ($result !== null) {
                        if ($result->numRows() > 0) {
                            $row = $result->fetchAssoc();

                            if ($row !== null && password_verify($password, $row["cPassword"])) {
                                $algo = Runtime::$SETTINGS->getInt("AUTH_VERIFY_ALGO", PASSWORD_DEFAULT);
                                $options = Runtime::$SETTINGS->getArray("AUTH_VERIFY_OPTIONS", []);

                                /*
                                 * Hash settings has changed, update password
                                 */
                                if (password_needs_rehash($row["cPassword"], $algo, $options)) {
                                    $hash = password_hash($password, $algo, $options);

                                    Database::update("users", "cPassword", "cId=?", "si", $hash, $row["cId"]);
                                }

                                $this->mUserId = $row["cId"];
                                $this->mUserName = $row["cName"];
                                $this->mUserGroups = null;

                                Runtime::$SESSION["_im_userid"] = $this->mUserId;
                            }
                        }

                        $result->destroy();
                    }

                    return $this->mUserId != 0;
                }
            }
        }

        return false;
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function logout() /*void*/ {
        if ($this->mUserId != 0) {
            Runtime::$SESSION->remove("_im_userid");

            $this->mUserId = 0;
            $this->mUserName = "guest";
            $this->mUserGroups = null;
        }
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function isLoggedIn(): bool {
        if ($this->mUserId == -1) {
            $secPasswd = Runtime::$SETTINGS["AUTH_ROOT_PASSWD"];

            if (empty($secPasswd)) {
                return false;
            }
        }

        return $this->mUserId != 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function getGroups(): ImmVector {
        if ($this->mUserGroups === null) {
            if ($this->isLoggedIn() && Database::isConnected()) {
                $result = Database::select("groups", "g")
                    ->join("usergroups", "u", "u.cGroupId", "g.cId")
                    ->field("g.cIdentifier")
                    ->cond("u.cUserId", "i", $this->mUserId)
                    ->enquire();

                if ($result !== null) {
                    $groups = [];

                    if ($result->numRows() > 0) {
                        while ($row = $result->fetch()) {
                            $groups[] = $row[0];
                        }
                    }

                    $result->destroy();

                    $this->mUserGroups = new ImmVector($groups);
                }

            } else {
                $this->mUserGroups = new ImmVector();
            }
        }

        return $this->mUserGroups;
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function getUserId(): int {
        return $this->mUserId;
    }

    /** @inheritdoc */
    /*Overwrite: BaseUser*/
    public function getUserName(): string {
        return $this->mUserName;
    }
}
