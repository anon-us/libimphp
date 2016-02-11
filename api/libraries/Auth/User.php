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

namespace api\libraries\Auth;

use api\libraries\collections\ImmVector;

/**
 * Abstract User class
 *
 * Drivers should extend from this.
 *
 * The class is used to handle authorization of the current visitor.
 * It is used to log the visitor in/out and to handle group checks.
 *
 * The class is not meant to create/delete/modify users,
 * nor it is meant to store/collect information like first/last name,
 * sex, birthday etc. It is an authorization class only.
 * Anything beon that should be added to a different library.
 *
 * @package api\libraries\Auth
 */
abstract class User {

    /**
     * Instantiates a new User instance
     *
     * @api
     */
    public function __construct() {

    }

    /**
     * Log visitor in
     *
     * Makes a lookup on $username and then compares $password with
     * the hash from the lookup. If the username is found and the password matches,
     * visitor is considered logged-in.
     *
     * @api
     *
     * @param string $username
     *      The username to lookup
     *
     * @param string $password
     *      The password to match against
     *
     * @return bool
     *      True if login was successful or False if authorization failed
     */
    abstract public function login(string $username, string $password): bool;

    /**
     * Log visitor out
     *
     * Removes user information from current session afterwhich
     * the visitor is considered logged-out.
     *
     * @api
     */
    abstract public function logout() /*void*/;

    /**
     * Check if visitor is logged-in
     *
     * @api
     *
     * @return bool
     *      True if logged-in or False if not
     */
    abstract public function isLoggedIn(): bool;

    /**
     * Get a all groups that the user is a member of
     */
    abstract public function getGroups(): ImmVector;

    /**
     * Check if user is member of one or more groups
     *
     * @param string ...$groups
     *      One or more groups to check for
     *
     * @return int
     *      The number of groups the user is a member of
     */
    public function inGroup(string ...$groups): int {
        if ($this->isLoggedIn()) {
            /*
             * Root belongs to everything
             */
            if ($this->mUserId == -1) {
                return count($groups);
            }

            /*
             * Not Guest nor Root, let's see what power they got
             */
            $i = 0;
            $colletion = $this->getGroups();

            foreach ($groups as $group) {
                $i += $colletion->contains($group) ? 1 : 0;
            }

            return $i;
        }

        /*
         * Guest does not belong to anything
         */
        return 0;
    }

    /**
     * Get the current user id
     *
     * @api
     */
    abstract public function getUserId(): int;

    /**
     * Get the current user name
     *
     * @api
     */
    abstract public function getUserName(): string;
}
