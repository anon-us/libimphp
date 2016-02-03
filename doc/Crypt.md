[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Crypt

Crypt is a library that let's you encrypt/decrypt data as well as create hash values and more. Algorithms, modes etc can be altered easily without changing anything in your code. Crypt is driver driven using a generic class. Drivers, algorithms, modes and so fourth can be changed from within a single configuration file and can thereby be easily adapted with newer technologies and/or server setups.

By default libimphp has 3 drivers available for Crypt.

* `mcrypt`: A very popular, yet half outdated, encryption tool.
* `openssl`: A better and faster alternative to mcrypt.
* `dummy`: A dummy encryption driver. Allows compatibility with code written for Crypt, even if the server running it does not have mcrypt nor openssl installed. It does not provide much security when it comes to encryption/decryption, but hash and decode/encode tools etc. will still work properly. Data is also still signed and authenticated when "encrypted"/"decrypted" with the dummy driver.

When using Crypt you have two options. You can use the default Crypt driver that allows global configurations via IMPHP's settings, or you can create a new instance of a particular Crypt driver with custom configurations.

#### Password

Crypt does not encrypt data using the parsed key. Instead the key/password is used along with random byte generator and various other techniques to create a proper encryption key for each specific encryption module. You should avoid parsing hash sums as password to the encryption method as those use a very limited character set. Hash values does not contain uppercase letters, they don't contain special characters nor is the lower case character set very wide.

Unless you find a better choice, use Crypt's `Crypt::password()` to generate a significant password.

## Data Encryption

#### Encrypting Data

```php
use api\libraries\Crypt;

// Default driver
$encrypted = Crypt::encrypt($data, $passwd);

// Specific Driver
$crypt = Crypt::newEncrypter("openssl", "aes-256", "sha512", "cbc");
$encrypted = $crypt->encrypt($data, $passwd);
```

#### Decrypting Data

```php
use api\libraries\Crypt;

// Default driver
$data = Crypt::decrypt($encrypted, $passwd);

// Specific Driver
$crypt = Crypt::newEncrypter("openssl", "aes-256", "sha512", "cbc");
$data = $crypt->decrypt($encrypted, $passwd);
```

#### Hashing Data

```php
use api\libraries\Crypt;

// Default driver
$hash = Crypt::hash($data); // Regular Hash
$hmac = Crypt::hash($data, $passwd); // Keyed Hash

// Specific Driver
$crypt = Crypt::newEncrypter(null, null, "sha512", null); // No specific driver will create from the dummy driver
$hash = $crypt->hash($data); // Regular Hash
$hmac = $crypt->hash($data, $passwd); // Keyed Hash
```

## Configuring

The default Crypt driver uses configurations from IMPHP's settings. Below are the configs that can be set.

* `CRYPT_DRIVER`: One of the 3 built-in Crypt drivers, `openssl`, `mcrypt`, `dummy` or one from a 3'rd party module if available.
* `CRYPT_CIPHER`: The cipher to use. Note that these are driver specific. The `openssl` uses `AES-256` by default, the `mcrypt` uses `rijndael-256` while `dummy` does not need any.
* `CRYPT_HASH`: The hash algorithm to use for encryption key verification and for the `hash()` method.
* `CRYPT_MODE`: The encryption block mode to use. Both `openssl` and `mcrypt` uses `cbc` by default.
* `CRYPT_TWO_STEP_AUTH`: A boolean indicating whether or not to use 2-step verification on encryptions. This is off by default and should stay that way unless for some very specific reason. In most cases it will not be worth the additional load during decryption.

## Drivers

Additional drivers can be easily added. All it takes is to create a module with a class that extends from `api\libraries\Crypt\Encrypter`, which already contains most of the things the driver needs. The class needs to be in the `driver\Crypt\[module name]` namespace and be named `Encrypter`. Then simply tell IMPHP where to find this class, which you can read how in the IMPHP docs.
