[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Database Part 2: QueryBuilder

I will be honest, I really dislike query builders. We already use a lot of database resources interpreting SQL, with query builders we also use server resources on creating the SQL. The best part is that it could all be avoided if every database had a shared standard to some degree, but databases is like web browsers in 1995, they all want to do things their own way. Because of this we need to spend hour precious resources on building support for them, just like we did with CSS/JavaScript on older browsers.

Never the less, libimphp of cause comes with a query builder for it's database library. It's still in early stages, but it has proven quite flexible while still being as lightweight as possible. Let's take a look at some of the features. The way it was created, was by creating a stack of small feature sets along with a common abstraction. Each query class then implements the common abstraction and adds each of the feature sets that this particular query type is capable of. I will provide a walk through of the feature sets and which query class adds it to it's collection.


### General

All the `Query` classes are accessible through the `Connection` class. This class is accessible through the main `Database` class using `getInstance()` for the main connection or `newInstance()` for new connections. Here is a list of the `Query` classes.

* `SelectQuery`: Accessible using `Connection::select(table, alias)`. Used to collect data from the database. Parameters are optional.
* `InsertQuery`: Accessible using `Connection::insert(table, alias)`. Used to insert data into the database. Alias is optional.
* `UpdateQuery`: Accessible using `Connection::update(table, alias)`. Used to update data within the database. Parameters are optional.
* `DeleteQuery`: Accessible using `Connection::delete(table, alias)`. Used to delete data from the database. Parameters are optional.
* `ConditionQuery`: Accessible using `Connection::condition(operator)`. Used to create separate conditions for multi-level conditional checks. Operator is optional, should be either `AND` or `OR` where `AND` is default.


#### Table Feature Set

Add additional tables to your query.

* `table(name, alias)`: Parse a table name and an optional alias
* `tableQuery(builder, alias)`: Parse another `QueryBuilder` and optional alias as a sub-query to be used as table set

**This is included in `SelectQuery`, `DeleteQuery`, `UpdateQuery`.**


#### Conditional Feature Set (WHERE Clause)

Add conditional options to your query

* `cond(field, type, value, operator)`: Normal condition with a field compared against a value using `operator`.
* `condExpr(field, expr, operator, types, input ...)`: Conditional Expression. The `expr` can be another field name, or something else. There is no limit to this method, although it should not be used for anything other than second field names if portability is a concern. Any input being used must be accompanied with a placeholder prefix `?` in the same order as the input along with a type definition for each input.
* `condQuery(field, builder, operator)`: Sub-query as value by parsing a `QueryBuilder`. The output of the sub-query is compared against the field using `operator`.
* `condIn(field, type, array, include)`: Compare the field against an `array` of possible values. The array must contain the same datatype of values and therefore only one type definition is needed. If the optional `include` is set to false, `NOT IN` is used instead of `IN`.
* `condGroup(builder)`: Add an additional `ConditionQuery` to be used as sub-condition. That means that every condition within the parsed `ConditionBuilder` is wrapped in `( )` before added to the existing conditions within the current `QueryBuilder`.

You can also change the type of conditions that you have like `AND` or `OR`.

* `setCondOR()`: Change the current `QueryBuilder` to use `OR` as condition separator.
* `setCondAND()`: Change the current `QueryBuilder` to use `AND` as condition separator.

Each level of conditions can only be either `AND` or `OR` separated. To mix them you need to add groups using `condGroup`.

**This is included in `ConditionQuery`, `SelectQuery`, `DeleteQuery` and `UpdateQuery`.**


#### Field Selectable Feature Set

Add field/column selection to your query

* `field(name, alias)`: Select a field with optional alias.
* `fields(name ...)`: Select a range of fields, one field per parameter. Alias is not possible.
* `fieldExpr(expr, alias, types, input ...)`: Select an expression with optional alias and input. This should not be used unless it can't bbe avoided. Like conditional expressions, this does not grantee portability depending on the content. Also like the conditional expressions, any input being used must be accompanied with a placeholder prefix `?` in the same order as the input along with a type definition for each input.

**This is included in `SelectQuery`.**


#### Field Updatable Feature Set

Add field/column value assignment to your query

* `field(field, type, value)`: Assign a value to a field.
* `fieldExpr(field, expr, types, input ...)`: Assign a value to a field using an expression. Same warning and same rules as with field expressions and conditional expressions.
* `fieldQuery(field, builder)`: Assign a value to a field using output from a sub-query by parsing another `QueryBuilder`.

**This is included in `InsertQuery` and `UpdateQuery`.**


#### Join Feature Set

* `join(table, alias, field1, field2, operator)`: Join a second table to the query using `INNER JOIN`. If only `field1` is specified, this method will assume that you join on the master table _(The first table to be defined)_ using the same column name and will generate `ON master.field1 = table.field1`.
* `joinLeft(table, alias, field1, field2, operator)`: Same as `join()`, only this makes a `LEFT JOIN` instead of `INNER JOIN`.
* `joinRight(table, alias, field1, field2, operator)`: Same as `join()`, only this makes a `RIGHT JOIN` instead of `INNER JOIN`.
* `joinQuery(builder, alias, field1, field2, operator)`: This is the same as `join` only you parse another `QueryBuilder` to join on a sub-query.
* `joinQueryLeft(builder, alias, field1, field2, operator)`: Same as `joinQuery()`, only this makes a `LEFT JOIN` instead of `INNER JOIN`.
* `joinQueryRight(builder, alias, field1, field2, operator)`: Same as `joinQuery()`, only this makes a `RIGHT JOIN` instead of `INNER JOIN`.

**This is included in `InsertQuery`, `DeleteQuery` and `UpdateQuery`.**


#### QueryBuilder

The `QueryBuilder`, which all query classes extends from, also has a set of features.

* `compile()`: Compiles all current options and creates the SQL string, types information and input collection in the correct order.
* `isCompiled`: Check whether the `QueryBuilder` object has already been compiled ones.
* `getCompiledSQL()`: Returns the compiled SQL string.
* `getCompiledData()`: Returns the compiled Input data array by reference.
* `getCompiledTypes()`: Return the compiled string with all type definitions for all Input data in correct order.

Because placeholder prefixes `?` and associated Input data needs to be in the correct order, which can differ from each driver depending on how the SQL is compiled, it is not adviced to print out the SQL string and re-use it manually. It's therefore not possible to store the compiled SQL in a storage, but it is possible to re-use the object within the same request and thereby only create and compile it ones. To help with this, `QueryBuilder` has tools that let you alter the Input data in a safe way.

_(Do not serialize `QueryBuilder` objects. Most of them have references to the database connection which will not be restored upon unserialize)_

* `addSegmentIds(id ...)`: In order to identify which Input data you wish to replace/update, you need to assign an ID. This method let's you assign as many id's as you wish which will be used one by one for each new segment that you add. Note that anything you add, `conditions`, `fields`, `joins` etc. are all segments. So you need to call this right before whatever segment you wish to have access to. It is worth mentioning that this method does not use resources on tracking and checking for conflicting ID's that are used more than once. It's called an ID, so it already makes sense that there should only be one and it is up to you to make sure that you don't add the same one twice, also in nested query situations.

```php
// Save the query
$query = $db->select("table", tb1)
    ->addSegmentIds("myId")                 // Assigns one ID to be used
    ->cond("field1", "s", "MyValue")        // The first call to a segment method get's the ID
    ->cond("field2", "s", "AnotherValue")   // No more ID's, auto-generated ID will be used instead
    ...
```

* `setSegmentInput(segmentId, input ...`: Assign a new value to this segment. Note that some segments, for example expressions, allows you to parse multiple Input. This method allows the same, but you can only parse the same number of Input that was parsed when you added the segment. The number must match the number of type definitions that was added to the segment.

```php
// Update the query
$query->setSegmentInput("myId", "MyNewValue")   // Replace "MyValue" with "MyNewValue"
```
You don't need to re-compile the query when you update data, so you can just store it in compiled form, update any Input and re-execute it as many time as you wish.

* `clearSegmentInput()`: Set all Input data values to `NULL`. No real purpose for this, but if one is found the option should be there.


#### InsertQuery & UpdateQuery

These two have some specific tools that is not part of any of the feature sets.

* `ignore()`: If set the query will ignore if you try to update a table, or insert a table, that some how conflicts with another one. The `ignore` part does not mean that they will perform the actions regardless, it means that no errors will be raised and the query will simply quit normally. You can then use the number of affected rows status, which is returned by `execute()`, to determine whether or not another action should be performed.
* `resolve(builder)`: Add a second `QueryExecuter` that will be used whenever the current returns with 0 affected rows. This also automatically enabled the `ignore` option. Note that this is only put into storage to be used when you execute your current query. You cannot change Input values on it by using the current query, this needs to be done directly on the resolve object. It will also not combine it's output when using tools like `getCompiledSQL`. It will however be automatically compiled along with the current object.


#### SelectQuery

This class also has it's own separate features that is not in any of the feature sets.

`sortAsc(field ...)`: Add fields that rows should be sorted by in ascending order.
`sortDesc(field ...)`: Add fields that rows should be sorted by in descending order.
`range(max, offset)`: Define a specific range of rows to output, offset is optional.
`distinct()`: Calling this will enable a `DISTINCT` selection on your query.
`count`: Calling this will enable counting on your query and only return the number of rows found by the query. This feature makes a sub-query count, it does not simply add `COUNT(*)`. This is in most cases a bit slower, but it ensures potability with multiple drivers without the need to spend even more server resources re-structering the entire SQL based on what a specific database supports. You can always add `COUNT(*)` using `fieldExpr()`, but if you want insurance of potability then this is not advised. Also it does make the `count` much more flexible.
`enquire()`: This is similar to `execute()` on `QueryExecuter`'s like `DeleteQuery`, `InsertQuery` and `UpdateQuery`. Difference it that `enquire()` will return a result set instead of a number of affected rows.

```php
$db->select()
    ->distinct()
    ->count()
    ->table("table1")
    ->field("column")
    ->enquire();
```
Similar to: `SELECT COUNT(*) FROM (SELECT DISTINCT column FROM table1)`
