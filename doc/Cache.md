[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Cache

Every once in awhile you will want to cache some data. The libimphp has a cache library that can help, with a few different drivers that can be used depending on your server possibilities. It's very basic with `set`, `get` and `remove` options, and has of cause the option of assigning expire time.

#### Security

Like with many other libraries in libimphp, the Cache library can also encrypt data for you. It uses the libimphp Crypt library for encryption and all that you have to do, is parse an encryption password when you add data.

#### Examples

```php
Cache::set("key", "A Value", (24*60*60));
```
The above will add a new value to the cache which expires within 24 hours.
```php
Cache::set("key", "A Value", 0, $password);
```
The above will add a new value that will be encrypted using the password parsed in the last argument. Expire time is set to 0 which means that this will never expire. We can retrieve data again using the `get` method.
```php
$data = Cache::get("key", "Default Value", $password);
```
Note that if you chose to encrypt the data, you will need to parse the password again when you retrieve the data.

There are two more methods that can be useful in some cases, namely `setRaw` and `getRaw`. The regular `set` and `get` method might serialize/unserialize data depending on the driver being used. If you have data that does not need this, for example pure strings or data that you yourself has already encoded in some way, `setRaw` and `getRaw` will not do any encoding/decoding of any sort no mater what driver is being used. It stores the data as it is _(Unless the storage for security reasons needs to manage the data in some way)_. It does not really mater for developers one way or another, but if data is already properly prepared for storage, there is no need to spend resources on doing more preparation.

#### Object Access

If you want to use a different cache than what has been defined in the default setup, you can easily create a new instance that points to a different cache and driver.

```php
$storage = Cache::newInstance("driver", "protocol");
$storage->set("key", "A Value");
$storage->close();
```
Closing does not always have an affect. For example a file cache has nothing to close and memcached handles it automatically. But there might be drivers in use that should be closed such as the database driver, so calling close is always the best option and let the driver decide what to do.

Remember not to call close on the default connection if you used `getInstance` to get the default storage.

#### Configuring

* `CACHE_DRIVER`: The driver to use. Built-in options are `file`, `database` or `memcached` _(notice the 'd' in memcached)_
* `CACHE_PROTOCOL`: This is specific to each driver. For `file` this should contain a path to a writable directory, if not set the session dir from php.ini will be used. For `memcached` this should contain '[host[:port][#connId]]', if not set '127.0.0.1:11211' is used. For `database` this should be a protocol for the libimphp database library, if not set the default database connection is used.

#### Drivers

Additional drivers can be added by creating a class `driver\Cache\[driver name]\Storage` which extends from `api\libraries\Cache\Storage`. Then register the class with IMPHP and add the name of the new driver to `CACHE_DRIVER` and supported protocol to `CACHE_PROTOCOL`.
