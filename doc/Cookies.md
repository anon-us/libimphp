[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Cookies

libimphp provides a specific cookie class that is derives from IMPHP's `Bundle` collection class. This class has been extended with specific tools to help make it easier to work with cookies. The class is automatically instantiated during a request and is linked to both `$_COOKIE` and `Runtime::$COOKIE`.

#### Security

Since any data that is stored at the client can in fact be manipulated by the client, security should not be a main focus on this side of the line. Most of the security should always be implemented on the server side. That however does not mean that you can't do some effort to at least make it more difficult for people to do what they are not supposed to do. For this reason the libimphp cookie class has added hashed cookie names with prefixes. This does not provide all that much in terms of security, but it does make it more difficult for someone to make named searched for specific content. A session id for example is quite easy to find if the cookie is called `SESSID`. If it however is prefixed and hashed with a sha512 and looks more like this `0bb1630b796397070767....`, it's not as obvious as to what it contains.

Another way to locate specific cookies, is by checking the content of the cookies. To avoid this, and in general to protect the data from being read, libimphp allows you to encrypt cookie content using it's Crypt library. It is as easy as simply adding a key when you set a new cookie. And since the cookie class uses the generic Crypt class in libimphp, the configurations for the encryptions can be configured using the regular generic Crypt settings. Encrypting cookies also makes sure that someone or something on the client side does not attempt to tamper with the content. If this is the case, Crypt would find out and refuse to decrypt it.

## Examples

#### Set a Cookie

```php
/*
 * This will both set/change the value within this class,
 * but it will also send the cookie to the client.
 */
$_COOKIE["key"] = "value";

/*
 * This is the same as the above
 */
$_COOKIE->set("key", "value");

/*
 * Set expires on it as well
 */
$_COOKIE->set("key", "value", time()+3600);

/*
 * Set domain and path
 */
$_COOKIE->set("key", "value", time()+3600, $secure, $domain, $path);

/*
 * Encrypt the content
 */
 $_COOKIE->set("key", "value", time()+3600, $secure, $domain, $path, $password);
```

#### Get a Cookie value

```php
/*
 * Just like it has always been in PHP
 */
$value = $_COOKIE["key"];

/*
 * The OOP way
 */
$value = $_COOKIE->get("key");

/*
 * Or more specific
 */
$value = $_COOKIE->getString("key", "My Default Value");

/*
 * If the cookie is encrypted
 */
 $value = $_COOKIE->get("key", "My Default Value", $password);
```

#### Remove a Cookie

```php
/*
 * Will unset the value in the class and also send a delete notice to the client/browser
 */
unset($_COOKIE["key"]);

/*
 * This is the same as the above
 */
$_COOKIE->remove("key");

/*
 * Or if you added it with specific settings
 */
$_COOKIE->remove("key", $secure, $domain, $path);
```

## Configuring

There are a few configurations that you can make by adding the following to the IMPHP settings.

* `COOKIE_DOMAIN`: Default cookie domain that will be used whenever this is not specified when adding new cookies.
* `COOKIE_PATH`: Default cookie path that will be used whenever this is not specified when adding new cookies.
* `COOKIE_PREFIX`: A prefix that is added to cookie names before hashing them. By default `IMPHP_` is used.
