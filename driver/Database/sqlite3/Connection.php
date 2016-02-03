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

namespace driver\Database\sqlite3;

use SQLite3;
use api\libraries\Database\Connection as BaseConnection;

/**
 * SQLite3 driver connection class
 *
 * @package api\libraries\Database\drv_sqlite3
 */
class Connection extends BaseConnection {

    /*
     * $_ESCAPE and $_REPLACE is being overwritten because
     * double quotes should not be escaped in sqlite3. It stores values within
     * single quotes and as such a double quote has no special meaning and can do no harm.
     */

    /** @ignore */
    /*Overwrite: BaseConnection*/
    protected static /*regexp[]*/ $_ESCAPE = ['\\', "\0", "\n", "\r", "'", "\x1a"];

    /** @ignore */
    /*Overwrite: BaseConnection*/
    protected static /*regexp[]*/ $_REPLACE = ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\Z'];

    /** @ignore */
    protected /*SQLite3*/ $mDatabase;

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
     * sqlite3://path/to/database.db
     * sqlite3://file::memory
     * sqlite3://file::memory:?cache=shared
     * sqlite3://file:[tmp name]?mode=memory&cache=shared
     * ```
     *
     * @api
     *
     * @param string $protocol
     *      A protocol with the information needed to establish the connection
     */
    /*Overwrite: BaseConnection*/
    public function __construct(string $protocol) {
        $file = substr($protocol, strpos($protocol, ":")+3); // Remove sqlite3://
        $this->mDatabase = new SQLite3($file, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

        if($this->mDatabase->lasterrorcode() == 0) {
			$this->mDatabase->busytimeout(20000);

		} else {
			$this->mConnectError = $this->mDatabase->lasterrormsg();
			$this->mConnectErrno = $this->mDatabase->lasterrorcode();
		}

        $this->mIsConnected = $this->mConnectErrno == 0;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function enquire(string $sql, string $types=null, /*mixed...*/ &...$data) /*Result*/ {
        $action = strtolower(substr($sql, 0, strpos($sql, " ")));
		$numRows = 0;
        $result = false;
        $stmt = null;

        if ($types !== null) {
            if (($stmt = $this->mDatabase->prepare($sql)) !== false) {
                for ($i=0,$y=1; $i < strlen($types); $i++,$y++) {
                    switch ($types[$i]) {
                        case "s": $stmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_TEXT)); break;
                        case "i": $stmt->bindParam($y, $data[$i], SQLITE3_INTEGER); break;
                        case "f": $stmt->bindParam($y, $data[$i], SQLITE3_FLOAT); break;
                        case "b": $stmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_BLOB)); break;

                        default: throw new SecurityException("Cannot bind unknown param '".$types[$i]."'");
                    }
                }

                if (($result = $stmt->execute()) === false) {
                    $stmt->close();
                }
            }

        } else {
            $result = $this->mDatabase->query($sql);
        }

        $this->mQueryError = $result !== false ? null : $this->mDatabase->lasterrormsg();
        $this->mQueryErrno = $result !== false ? 0 : $this->mDatabase->lasterrorcode();

		if (is_object($result)) {
			$matches = null;

			/*
			 * TODO: Add the row count to the original query for speed-up
			 */
			if ($action == "select" && preg_match("/^select\s+(?:all\s+|distinct\s+)?(?:.*?)\s+from\s+(.*)$/i", $sql, $matches) && $matches !== null) {
                /*
                 * SQLite does not support num rows count,
                 * so we get this our self and make a small
                 * change to the result object that our num rows
                 */
                if ($types !== null) {
                    if (($countStmt = $this->mDatabase->prepare("SELECT COUNT(*) FROM ".$matches[1]."")) !== false) {
                        for ($i=0,$y=1; $i < strlen($types); $i++,$y++) {
                            switch ($types[$i]) {
                                case "s": $countStmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_TEXT)); break;
                                case "i": $countStmt->bindParam($y, $data[$i], SQLITE3_INTEGER); break;
                                case "f": $countStmt->bindParam($y, $data[$i], SQLITE3_FLOAT); break;
                                case "b": $countStmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_BLOB)); break;

                                default: throw new SecurityException("Cannot bind unknown param '".$types[$i]."'");
                            }
                        }

                        $countResult = $countStmt->execute();
                        $countColumn = $countResult->fetchArray(SQLITE3_NUM);
        				$numRows = (int) $countColumn[0];
        				$countResult->finalize();
                        $countStmt->close();

                    } else {
                        while($result->fetchArray()) {
                            $numRows += 1;
                        }
                        $result->reset();
                    }

                } elseif (($countResult = $this->mDatabase->query($matches[1])) !== false) {
                    $countColumn = $countResult->fetchArray(SQLITE3_NUM);
                    $numRows = (int) $countColumn[0];
                    $countResult->finalize();

                } else {
                    while($result->fetchArray()) {
                        $numRows += 1;
                    }
                    $result->reset();
                }

			} elseif ($action == "pragma") {
				/* Cant use count on PRAGMA, so we do this instead. It's still compatible with the custom num_rows().
				 * This is a bit slower if the query returns many rows, but PRAGMA never returns that many
				 */
				while($result->fetchArray()) {
					$numRows += 1;
				}
				$result->reset();
			}
		}

		return is_object($result) ? new Result($result, $stmt, $numRows) : null;
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function execute(string $sql, string $types=null, /*mixed...*/ &...$data): int {
        $result = false;

        if ($types !== null) {
            if (($stmt = $this->mDatabase->prepare($sql)) !== false) {
                for ($i=0,$y=1; $i < strlen($types); $i++,$y++) {
                    switch ($types[$i]) {
                        case "s": $stmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_TEXT)); break;
                        case "i": $stmt->bindParam($y, $data[$i], SQLITE3_INTEGER); break;
                        case "f": $stmt->bindParam($y, $data[$i], SQLITE3_FLOAT); break;
                        case "b": $stmt->bindParam($y, $data[$i], ($data[$i] === null ? SQLITE3_NULL : SQLITE3_BLOB)); break;

                        default: throw new SecurityException("Cannot bind unknown param '".$types[$i]."'");
                    }
                }

                if ($stmt->execute() !== false) {
                    $result = true;
                }

                $stmt->close();
            }

        } elseif ($this->mDatabase->exec($sql) !== false) {
            $result = true;
        }

        $this->mQueryError = $result ? null : $this->mDatabase->lasterrormsg();
        $this->mQueryErrno = $result ? 0 : $this->mDatabase->lasterrorcode();

        return $result ? $this->mDatabase->changes() : 0;
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
        return "SQLite3";
    }

    /** @inheritdoc */
    /*Overwrite: BaseConnection*/
    public function driver(): string {
        return "sqlite3";
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
