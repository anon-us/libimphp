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

namespace api\libraries\Database\builder;

/**
 * Adds field selction to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Field_Selectable {

    protected /*string*/ $mFieldDivider = ", ";

    public function field(string $field, string $alias=null) /*this|string*/ {
        return $this->addSegment("field", $this->mFieldDivider, $this->prepareFieldSQL($field, $alias));
    }

    public function fields(string ...$fields) /*this*/ {
        foreach ($fields as $field) {
            $this->addSegment("field", $this->mFieldDivider, $this->prepareFieldSQL($field, null));
        }

        return $this;
    }

    public function fieldExpr(string $expr, string $alias=null, string $types=null, /*mixed*/ ...$input) /*this|string*/ {
        return $this->addSegment("field", $this->mFieldDivider, $this->prepareFieldSQL($expr, $alias), $types, ...$input);
    }

    protected function prepareFieldSQL(string $field, string $alias=null): string {
        return $field.($alias !== null ? " as ${alias}" : "");
    }
}

/**
 * Adds field value assignment to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Field_Changeable {

    protected /*string*/ $mFieldDivider = ", ";

    public function field(string $field, string $type, /*mixed*/ $value) /*this|string*/ {
        return $this->addSegment("field", $this->mFieldDivider, $this->prepareFieldSQL($field, "?"), $type, $value);
    }

    public function fieldExpr(string $field, string $expr, string $types=null, /*mixed*/ ...$input) /*this|string*/ {
        return $this->addSegment("field", $this->mFieldDivider, $this->prepareFieldSQL($field, $expr), $types, ...$input);
    }

    public function fieldQuery(string $field, QueryBuilder $builder) /*this|string*/ {
        return $this->addQuerySegment("field", $this->mFieldDivider, $this->prepareFieldSQL($field, "(%s)"), $builder);
    }

    protected function prepareFieldSQL(string $field, string $value): string {
        return "${field} = ${value}";
    }
}

/**
 * Adds conditionals to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Conditional {

    protected /*string*/ $mCondDivider = " AND ";

    public function setCondAND() /*this*/ {
        $this->mCondDivider = " AND "; return $this;
    }

    public function setCondOR() /*this*/ {
        $this->mCondDivider = " OR "; return $this;
    }

    public function cond(string $field, string $type, /*mixed*/ $value, string $operator=null) /*this|string*/ {
        return $this->addSegment("conditional", $this->mCondDivider, $this->prepareCondSQL($field, "?", $operator), $type, $value);
    }

    public function condIn(string $field, string $type, array $value, bool $include=true) /*this|string*/ {
        $size = count($value);

        return $this->addSegment("conditional", $this->mCondDivider, $this->prepareCondSQL($field, "(".ltrim(str_repeat(",?", $size),",").")", ($include ? "IN" : "NOT IN")), str_repeat($type, $size), ...$value);
    }

    public function condExpr(string $field, string $expr, string $operator=null, string $types=null, /*mixed*/ ...$input) /*this|string*/ {
        return $this->addSegment("conditional", $this->mCondDivider, $this->prepareCondSQL($field, $expr, $operator), $types, ...$input);
    }

    public function condQuery(string $field, QueryBuilder $builder, string $operator=null) /*this|string*/ {
        return $this->addQuerySegment("conditional", $this->mCondDivider, $this->prepareCondSQL($field, "(%s)", $operator), $builder);
    }

    public function condGroup(QueryBuilder $builder) /*this|string*/ {
        return $this->addQuerySegment("conditional", $this->mCondDivider, "(%s)", $builder);
    }

    protected function prepareCondSQL(string $field, string $value, string $operator=null): string {
        if ($operator === null) {
            $operator = "=";
        }

        return "${field} ${operator} ${value}";
    }
}

/**
 * Adds join options to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Join {

    protected /*string*/ $mJoinDivider = " ";

    public function join(string $table, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addSegment("join", $this->mJoinDivider, $this->prepareJoinSQL("INNER", $expr, $alias, $field1, $field2, $operator));
    }

    public function joinLeft(string $table, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addSegment("join", $this->mJoinDivider, $this->prepareJoinSQL("LEFT", $expr, $alias, $field1, $field2, $operator));
    }

    public function joinRight(string $table, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addSegment("join", $this->mJoinDivider, $this->prepareJoinSQL("RIGHT", $expr, $alias, $field1, $field2, $operator));
    }

    public function joinQuery(QueryBuilder $builder, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addQuerySegment("join", $this->mJoinDivider, $this->prepareJoinSQL("INNER", "(%s)", $alias, $field1, $field2, $operator), $builder);
    }

    public function joinLeftQuery(QueryBuilder $builder, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addQuerySegment("join", $this->mJoinDivider, $this->prepareJoinSQL("LEFT", "(%s)", $alias, $field1, $field2, $operator), $builder);
    }

    public function joinRightQuery(QueryBuilder $builder, string $alias, string $field1, string $field2=null, string $operator=null) /*this|string*/ {
        return $this->addQuerySegment("join", $this->mJoinDivider, $this->prepareJoinSQL("RIGHT", "(%s)", $alias, $field1, $field2, $operator), $builder);
    }

    protected function prepareJoinSQL(string $type, string $table, string $alias, string $field1, string $field2=null, string $operator=null): string {
        $sql = "${type} JOIN ${table} ${alias} ON ";

        if ($operator === null) {
            $operator = "=";
        }

        if ($field2 === null) {
            $sql .= $this->mMasterTbl.".${field1} ${operator} ${alias}.${field1}";

        } else {
            $sql .= "${field1} ${operator} ${field2}";
        }

        return $sql;
    }
}

/**
 * Adds limimted table selection to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Table_Limited {

    protected /*string*/ $mTableDivider = ", ";
    protected /*string*/ $mMasterTbl = null;

    protected function table(string $table, string $alias=null) /*this|string*/ {
        return $this->addSegment("table", $this->mTableDivider, $this->prepareTableSQL($table, $alias));
    }

    protected function prepareTableSQL(string $table, string $alias=null): string {
        if ($this->mMasterTbl === null) {
            $this->mMasterTbl = $alias !== null ? $alias : $table;
        }

        return "${table}".($alias !== null ? " ${alias}" : "");
    }
}

/**
 * Adds full table selection to QueryBuilder classes
 *
 * @ignore
 */
trait QueryBuilder_Table_Extended {
    use QueryBuilder_Table_Limited {
        table as public;
    }

    public function tableQuery(QueryBuilder $builder, string $alias=null) /*this|string*/ {
        return $this->addQuerySegment("table", $this->mTableDivider, $this->prepareTableSQL("(%s)", $alias), $builder);
    }
}

/**
 * Abstract base class for building SQL
 *
 * @package api\libraries\Database\builder
 */
abstract class QueryBuilder {

    /** @ignore */
    private static /*int*/ $INSTANCE_COUNTER = 0;

    /** @ignore */
    private /*int*/ $curInstanceId = 0;

    /** @ignore */
    private /*int*/ $curFieldId = 0;

    /** @ignore */
    private /*array*/ $mFieldIds = [];

    /** @ignore */
    protected /*string*/ $mCompiledSQL = null;

    /** @ignore */
    protected /*string*/ $mCompiledTypes = null;

    /** @ignore */
    protected /*array*/ $mCompiledData = null;

    /** @ignore */
    protected /*array*/ $mSegments = [];

    /** @ignore */
    protected /*array*/ $mSegmentSizes = [];

    /** @ignore */
    protected /*bool*/ $mReturnId = false;

    /**
     * Get a new auto generated id for segments
     *
     * @ignore
     */
    protected function _fieldId(): string {
        if (empty($this->mFieldIds)) {
            if ($this->curInstanceId == 0) {
                $this->curInstanceId = (++self::$INSTANCE_COUNTER);
            }

            return $this->curInstanceId."_".(++$this->curFieldId);

        } else {
            return array_pop($this->mFieldIds);
        }
    }

    /**
     * Get the auto generated id that will be used on the next segment
     *
     * @ignore
     */
    protected function _nextFieldId(): string {
        if (empty($this->mFieldIds)) {
            if ($this->curInstanceId == 0) {
                $this->curInstanceId = (++self::$INSTANCE_COUNTER);
            }

            $iid = $this->curInstanceId == 0;

            if ($this->curInstanceId == 0) {
                $iid++;
            }

            return $iid."_".($this->curFieldId + 1);

        } else {
            $id = end($this->mFieldIds);
            reset($this->mFieldIds);

            return $id;
        }
    }

    /**
     * Compile a segment group
     *
     * This does not return anything. It compiles SQL, Types and Input
     * into the properties `mCompiledSQL`, `mCompiledTypes` and `mCompiledData`.
     *
     * @ignore
     *
     * @param string $key
     *      Key of the group
     */
    protected function compileSegments(string $key) /*void*/ {
        $this->mCompiledSQL .= empty($this->mCompiledSQL) ? "" : " ";

        if (!empty($this->mSegments[$key])) {
            $i=0;
            foreach ($this->mSegments[$key] as $id => &$seg) {
                if (($i++) > 0) {
                    $this->mCompiledSQL .= ($divider ?? $seg["divider"]);
                }

                if ($seg["builder"] !== null) {
                    $seg["builder"]->compile();

                    $this->mSegments[$key][$id]["sql"] = sprintf($seg["sql"], $seg["builder"]->getCompiledSQL());
                    $this->mSegments[$key][$id]["types"] = $seg["builder"]->getCompiledTypes();
                    $this->mSegments[$key][$id]["values"] = $seg["builder"]->getCompiledData();
                }

                $this->mCompiledSQL .= $seg["sql"];
                $this->mCompiledTypes .= $seg["types"];

                $size = count($this->mSegments[$key][$id]["values"]);
                for ($y=0; $y < $size; $y++) {
                    $this->mCompiledData[] =& $this->mSegments[$key][$id]["values"][$y];
                }
            }
        }
    }

    /**
     * Add a new QueryBuilder segment to a group
     *
     * @ignore
     *
     * @param string $key
     *      Key of the group
     *
     * @param string $divider
     *      String used as a divider between compiled segments
     *
     * @param string $sql
     *      The partial SQL for the segment
     *
     * @param QueryBuilder &$builder
     *      The builder for the segment
     *
     * @return string|this
     *      The id used to store the segment if `enableSegmentId()` is enabled, or the object itself
     */
    protected function addQuerySegment(string $key, string $divider, string $sql, QueryBuilder &$builder) /*this|string*/ {
        if (!isset($this->mSegments[$key])) {
            $this->mSegments[$key] = [];
            $this->mSegmentSizes[$key] = 0;
        }

        $id = $this->_fieldId();

        $this->mSegmentSizes[$key]++;
        $this->mSegments[$key][$id] = [
            "builder" => $builder,
            "divider" => $divider,
            "sql" => $sql,
            "types" => null,
            "values" => null
        ];

        return $this->mReturnId ? $id : $this;
    }

    /**
     * Add a new segment to a group
     *
     * @ignore
     *
     * @param string $key
     *      Key of the group
     *
     * @param string $divider
     *      String used as a divider between compiled segments
     *
     * @param string $sql
     *      The partial SQL for the segment
     *
     * @param string $types=null
     *      Type definitions for $input
     *
     * @param mixed &...$input
     *      Input values for the segment
     *
     * @return string|this
     *      The id used to store the segment if `enableSegmentId()` is enabled, or the object itself
     */
    protected function addSegment(string $key, string $divider, string $sql, string $types=null, /*mixed*/ &...$input) /*this|string*/ {
        if ($types !== null && strlen($types) != count($input)) {
            throw new SecurityException("The argument \$types does not match the input data");
        }

        if (!isset($this->mSegments[$key])) {
            $this->mSegments[$key] = [];
            $this->mSegmentSizes[$key] = 0;
        }

        $id = $this->_fieldId();

        $this->mSegmentSizes[$key]++;
        $this->mSegments[$key][$id] = [
            "builder" => null,
            "divider" => $divider,
            "sql" => $sql,
            "types" => $types !== null ? str_replace(" ", "", trim($types)) : "",
            "values" => $input
        ];

        return $this->mReturnId ? $id : $this;
    }

    /**
     * Get all segments within a group
     *
     * @ignore
     *
     * @param string $key
     *      Key of the group
     */
    protected function &getsegments(string $key) /*array*/ {
        static $null = null;

        return $this->mSegments[$key] ?? $null;
    }

    /**
     * Get the number of segments within a group
     *
     * @ignore
     *
     * @param string $key
     *      Key of the group
     */
    protected function countSegments(string $key): int {
        return $this->mSegmentSizes[$key] ?? 0;
    }

    /**
     * Return auto generated id's when adding segments
     *
     * By default all methods for adding segment will return
     * the object being called. If this is enabled, segment methods
     * will instead return the id that they are assigned.
     *
     * The id can later be used to change input data on segments.
     * However a better option for this is to use `addSegmentIds()`.
     *
     * @api
     *
     * @param bool $return
     *      Enable/Disable segment id return
     */
    public function enableSegmentId(bool $return) /*this*/ {
        $this->mReturnId = $return; return $this;
    }

    /**
     * Add id's that will be added to every new segment
     *
     * The first id in the list is added to the next segment being added.
     * Then the second id to the second segment and so on.
     * Calling this again to add more id's will push the new ones to the back
     * of the stack.
     *
     * @api
     *
     * @param string ...$ids
     *      One or more id's to add
     */
    public function addSegmentIds(string ...$ids) /*this*/ {
        $this->mFieldIds = array_merge(array_reverse($ids), $this->mFieldIds); return $this;
    }

    /**
     * Change the value on one or more input data
     *
     * You can only change the same number of data
     * as the number of type definitions for the segment
     *
     * @api
     *
     * @param string $segmentId
     *      The id of the segment containing the input data
     *
     * @param mixed ...$input
     *      new values to set
     */
    public function setSegmentInput(string $segmentId, /*mixed*/ ...$input) /*this*/ {
        foreach ($this->mSegments as $key => &$segGroup) {
            if (isset($segGroup[$segmentId])) {
                /*
                 * We can't change input on sub query builder until it has been compiled.
                 */
                if ($segGroup[$segmentId]["values"] !== null) {
                    $size = count($segGroup[$segmentId]["values"]);

                    /*
                     * Do not allow to set more input than defined when the segment was added.
                     * This number matches the SQL string as well as the types difinition string
                     */
                    for ($i=0; $i < $size; $i++) {
                        if (!isset($input[$i])) {
                            break;
                        }

                        $this->mSegments[$key][$segmentId]["values"][$i] = $input[$i];
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set's all input data to `NULL`
     *
     * @api
     */
    public function clearSegmentInput() /*this*/ {
        foreach ($this->mSegments as $key => &$segGroup) {
            foreach ($segGroup as $id => &$seg) {
                if ($seg["values"] !== null) {
                    $size = count($seg["values"]);

                    /*
                     * The input number should always match what was added.
                     * Also we don't want to destroy references to the compiled data.
                     */
                    for ($i=0; $i < $size; $i++) {
                        $this->mSegments[$key][$id]["values"][$i] = null;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get the compiled SQL string
     *
     * @api
     */
    public function getCompiledSQL() /*string*/ {
        return $this->mCompiledSQL;
    }

    /**
     * Get all compiled input data
     *
     * @api
     *
     * @return [mixed]
     *      The input data by reference
     */
    public function &getCompiledData() /*array*/ {
        return $this->mCompiledData;
    }

    /**
     * Get the type definition for all current input data
     *
     * The order of each character matches the order of the
     * compiled data.
     *
     * @api
     */
    public function getCompiledTypes() /*string*/ {
        return $this->mCompiledTypes;
    }

    /**
     * Check if the object has been compiled
     *
     * @api
     */
    abstract public function isCompiled(): bool;

    /**
     * Compile the object
     *
     * Runs through all segments and compiles them into
     * proper SQL as well as re-arranging types information
     * and input data.
     *
     * @api
     */
    abstract public function compile() /*this*/;
}
