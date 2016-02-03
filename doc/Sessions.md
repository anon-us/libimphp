[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Sessions

libimphp provides a specific session class that is derived from IMPHP's `Bundle` collection class. The class is automatically instantiated during a request and is linked to both `$_SESSION` and `Runtime::$SESSION`. The class does not read/write session data from storage until it needs to and there is no need for any type of session start call to initiate it.

#### PHP Internal Session Handling

What is a session? A session is nothing more than data being written to a storage and a cookie with an id used to identify that specific storage section, so that the data can be read back from storage whenever the client with that id makes a request. This makes it possible to link persistent data to specific clients and create the illusion of a sort of logged-in state.

PHP has a feature allowing sites to create custom handlers for it's internal session system. PHP will call upon these callbacks whenever they should read or write to the storage or perform garbage cleanup. Since we just determined that a session is nothing more than storing data and linking to it using an id'ed cookie, what is left for PHP to handle if we do all of the writing, reading and cleaning? Simply handling the cookie.

Since handling a cookie is a pretty simple task and libimphp even has a great cookie class, libimphp does not make use of these callbacks. PHP's session handling has been left completely out of the equation. The session system in libimphp is a 100% custom and self controlled system. Because of this, session_start() should never be called. Since libimphp uses the global `$_SESSION` variable to keep consistency with PHP, starting PHP's sessions will make sure that data is written to two different locations at the same time, since both libimphp and PHP would write to each of their locations during shutdown.

Session autostart should of cause also be turned off, but libimphp will handle this if detected.

#### Security

The libimphp Session system offers two types of encryption. Cookie encryption and Storage encryption. The first will use the encryption feature in the cookie system to encrypt the session id cookie. The second will encrypt session data before writing it to storage. Why? Well...

* `Cookie Encryption`: A cookie with a hashed session id might seam safe, especially if using a 512bit hash like libimphp's default. But no mater how long, a hash value is not safe from being manipulated. The value contains nothing more than letters from A-F and numbers from 0-9. Switching one character at a time and trying every combination might seam like a drag to a human, but to a machine it's an easy and quite speedy task. If an admin or one with similar right has just been logged on, or still is and has an active session laying around in the storage, hitting that correct id would give someone those same rights, without even attempting to do password brute force or similar actions. The encryption part in this example is not the important thing, because the id itself does not really mater. What's important is that libimphp's Crypt library also makes authentication checks, which means that any form of tampering with the cookie will make it invalid and libimphp will refuse it. The only way to get another session id than the one assigned by visiting the site, would be to get the actual cookie file from the person of whom they are trying to impersonate.

* `Session Encryption`: This depends on where your site is hosted. Some hosting sites has been known to use unsafe virtual environments where multiple sites are hosted on the same environment. Sharing the same database storage, disk storage etc. with lack of proper security, that some times results in other sites being able to gain access to your side of the fens. In such situations it would not be a bad idea to encrypt data before storing it, although switching hosting company would be a better option. Especially since there is a chance that the encryption key is leaking as well, if your session storage is. Often it's stored on the same servers. So again, it all depends on your hosting situation. Encryption does not always mean that something is actually safe.

The recommended approach is to enable encryption on cookies. The rest is something you will have to decide for yourself.


#### Example

```php
// Regular way to access session data
$value = $_SESSION["key"];

// Using IMPHP's colletion tools
$value = $_SESSION->getString("key", "Default Value");

// Or the more proper IMPHP way
$value = Runtime::$SESSION->getString("key", "Default Value");
```
Session data will not be read from storage until the first time it is being accessed. All requests where session data is not being accessed will produce no read/write.


#### Configuring

The following configurations are available for the session system. This is regular IMPHP Settings values.

* `SESSION_ENCRYPT_DATA`: A boolean value to enable storage encryption. It requires that the shared `SECURITY_PASSWD` has been set.
* `SESSION_ENCRYPT_COOKIE`: A boolean value to enable cookie encryption. It requires that the shared `SECURITY_PASSWD` has been set.
* `SESSION_EXPIRES`: An integer value defining the lifetime of a session in seconds. Defaults to 24 hours.
* `SESSION_DRIVER`: A string containing the name of the storage driver to use. Available are `database` for storing session data in the shared database `DATABASE` or `file` for storing session data in files at the `php.ini` specified session path. Last you have the `cache` driver that uses libimphp's cache system to store data. That means that you also has every available options of the cache system, like selecting a secondary database server, memcached or a different folder to store session data.

#### Drivers

Additional drivers can be added by creating a class `driver\Session\[driver name]\Handler` which extends from `api\libraries\Session\Handler`. Then register the class with IMPHP and add the name of the new driver to `SESSION_DRIVER`.
