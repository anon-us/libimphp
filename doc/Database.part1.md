[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Database Part 1: General

_This doc has been split into sections, there is a whole builder part of the database library which is covered in Part 2. This part will focus on some of the general aspects of the library._

Let's start by saying that this is not built on top of PHP's PDO. The database library uses it's own abstraction, so there is no point. The only thing PDO would provide, was a few additional databases being able to connect, but without any specific SQL setup, they would most likely fail anyways as most databases uses their own form of SQL. By default libimphp supports MySQL and SQLite, which means one server based and one file based. That should be enough to get started.

The goal of this library is to make a consistent abstraction for working with databases. This does not just mean to allow databases to connect successfully, but also have them provide the same features using standard tools.

One of the most important things about database libraries, is to provide a lot of security in a simple way _(simple for those using it)_. This library has done that to a degree, but there are circumstances where it can become to simple. For starters the library does not add input data into your SQL String, escaped or not, that is just a bad idea. Only time this should be done, is if the driver does not support anything else in which case I would recommend switching database. There are other libraries that does the same, but what this library does not do, is auto detect data types. When you parse input to this library you will need to specify the expected data type as well. It's been done quite easy as each data type is nothing more than a single character. The reason for this is quite simple, nothing is left to chance. Parsing unknown data types that does not match whatever your column is configured for, could create a lot of unknown security issues. All original database drivers requires this to be done, and for good reason. If not, then they would all provide auto detection.


#### Connections

Everything in the library is run through the `Connection` class, but there is a `Database` class used as a helper. From this you can create new `Connection` instances using `newConnection(string protocol)`, but you can also use it to work directly on the default `Connection`. Every method within `Connection` can be accessed using the static `Database` on the default instance. You can also get the actual object for the default `Connection` using `getInstance()`.


#### Queries

There are two methods that can be used to make queries.

* `Connection#enquire(sql, types, data ...)`: Used to gather data from the database and return a result set.
* `Connection#execute(sql, types, data ...)`: Used to execute an operation on the database, this only returns the number of rows that was affected by the operation.

**Types**

The `types` parameter is used to define the data types of everything parsed in `data`. Each data parameter must have a type definition in the form of a character within `types` in the same order. Also for each data parameter you need to add a placeholder prefix `?` to your SQL. Again in the same order.

Following types are available.

* `s`: String
* `i`: Integer
* `f`: Floating Point
* `b`: Blob

```php
$conn->execute("UPDATE ... SET c1 = ?, c2 = ? WHERE id = ?", "sbi", $text, $data, $id)
```
In the above example you are parsing a string `s` to c1, a blob `b` to c2 and an integer `i` to your id condition.


#### Result

Like mentioned before, the `enquire` method will return a result set.

There are 2 different fetch methods within the Result object
* `fetch`: Returns a PHP Indexed Array where keys are in the order you defined the columns
* `fetchAssoc`: Returns a PHP Associated Array with column names as key

```php
$result = $conn->enquire("SELECT ...");

if ($result !== null) {
    while ($row = $result->fetchAssoc()) {
        echo $row["key"] . "\n";
    }

    /*
     * Destroys the result set and STMT connection
     */
    $result->destroy();
}
```
**Remember to destroy the result after you are done with it**

Two other useful methods within the Result object are:
* `numRows`: Returns the number of rows within the result
* `seek`: Move the pointer to a specific location within the result set. You can parse negative numbers to work from right to left.

```php
$result = $conn->enquire("SELECT ...");

if ($result !== null) {
    /*
     * Move the pointer to the last row
     */
    $result->seek(-1);

    ...
}
```


#### Errors

The `Connection` class has two different errors, connection error and query error. The connection error is set when the database produces error while connecting. The query error is set on each query to the database and is reset each time. The containing error and error number will always be from the last query, which is set to `NULL` and `0` if a query was executed successfully.

```php
$conn->execute("...");

if ($conn->queryErrno() > 0) {
    echo $conn->queryError();
}
```


#### Configuring

Configurations for the default connection.

* `DATABASE`: String containing the protocol to use. This is driver specific. For mysqli use `mysqli://[user[:password]@]host[/path][:port]#database` and for sqlite3 use `sqlite3://[path]/database.db`.


#### Drivers

Additional drivers can be added by creating the classes `driver\Database\[driver name]\Connection` and `driver\Database\[driver name]\Result` which extends from `api\libraries\Database\Connection` and `api\libraries\Database\Result`. Then register the class with IMPHP and add the protocol of the new driver to `DATABASE`.
