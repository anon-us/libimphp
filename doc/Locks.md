[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

#### Locks

If have not yet read the IMPHP docs, it contains more information on locks. In short, Locks is a small feature in IMPHP allowing you to easily create small locks to better control when certain code should be executed. The libimphp has 4 locks that you can use to ensure that certain libraries stay active until your shutdown callbacks _(if you have registered some)_ have done their jobs. For example the database session handler within libimphp uses the database library to store session data. For this reason it would be bad if the default database connection closes before it has written the data back to the database at the end of the request. Therefore this handler adds a database lock that ensures that the handler has an active database connection when it's shutdown function is called, no mater the order in which these functions are executed. After session has been written to the database, the lock is lifted, and the database is called allowing it to finish.

The following locks are available in libimphp:

* `session`: Session data is not written to storage until the lock has been removed
* `database`: The default connection is not closed until the lock has been removed
* `cache`: Cache stays available until the lock has been removed
* `auth`: Locks both session and database which is used by the library

If you need to use one these libraries in a shutdown function, it's a good idea to lock them when you register the function. Just remember to remove the locks at the end of your shutdown function call.

```php
class MyClass implements IStaticConstruct {

    public static function onCreate() /*void*/ {
        /*
         * Add a lock at the beginning
         */
        Runtime::addLock("session");
    }

    public static function onDestroy() /*void*/ {
        /*
         * Add your session data at the end
         */
        Runtime::$SESSION["key"] = "A Value";

        /*
         * Remove the lock allowing sessions to be written and closed
         */
        Runtime::removeLock("session");
    }
}
```
