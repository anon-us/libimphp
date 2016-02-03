[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Authorization

The libimphp Auth Library is a authorization library that manages users login state and their roles. It is based on the principle of groups/roles rather than titles, which means that users are not simply user, admin etc. Instead users are added to multiple groups each of which determines a specific role for the user. However due to the flexible nature of group/role systems, it is quite easy to add the old fashion title based system by creating an admin group and just keep normal users group-less.

Note that this is an authorization library only. It does not handle creation/deletion or alterations of users and groups. Administration does not belong here.

#### Root/Guest

The Auth library treats everyone as a user, not just the once that are logged-in. Any visitor that is not currently logged-in is considered a member of Guest. This user is not a member of any groups, it returns false when checking login state and always has the user id `0`. But is does have an id and a name. It is also reserved and cannot be used to login with, even if created with a password in the database.

Another reserved user is `root`. This is a built-in administrator that can be accessed without database connection. It's password is stored in the IMPHP settings `AUTH_ROOT_PASSWD`. If this password is not set, this user is disabled and cannot be used to login with. Another feature of the root user is that it is considered a member of every group. It holds the absolute power and therefore, if enabled, should not use a weak password. The root user has the user id `-1`.

#### Password Storing

The Auth library uses PHP's `password_verify()` to compare a password with that in the database. Therefore passwords stored in the database, and also `AUTH_ROOT_PASSWD`, should be hashed using PHP's `password_hash`.

#### Example

```php
$username = Runtime::$POST["username"];
$password = Runtime::$POST["password"];

if (Auth::login($username, $password)) {
    // Successfully logged in

} else {
    // Authorization failed
}
```
It's easy to create a login system using Auth as it takes care of most of it.
```php
if (Auth::isLoggedIn()) {
    // User is logged-in
}
```
Checking login state is just as easy
```php
if (Auth::inGroup("group1", "group2", ...)) {
    // User is part of at least one of these groups
}
```
Access check using user roles, which of cause works without first checking `isLoggedIn()` since Guest is not part of any group.

#### Terminal Access

You might want to execute something from a terminal, it could be some cron jobs for example. In any case the Auth library allows you to do login using terminal arguments. Since terminals does not hold cookies, this is a per request login.

```sh
php index.php --user root --passwd mypassword --location /some/cron/request/controller
```
If Auth detects the presence of either `--user`, `--passwd` or both, it will start a login procedure and return with error if it fails.

#### Configuring

The following configurations are available for the Auth Library. This is regular IMPHP Settings values.

* `AUTH_DRIVER`: The driver to use. Available and default are `imphp`. Since there is only one built-in, this can just be kept unset.
* `AUTH_ROOT_PASSWD`: If set, enables the built-in administrator. Password should be hashed using PHP's `password_hash`.
* `AUTH_VERIFY_ALGO`: Algorithm used in passwords, read PHP's docs for `password_hash`.
* `AUTH_VERIFY_OPTIONS`: Options used in passwords, read PHP's docs for `password_hash`.
* `AUTH_ALLOW_CLI`: Boolean indicating whether or not to allow login from terminal arguments. This is false by default.

It is important that `AUTH_VERIFY_ALGO` and `AUTH_VERIFY_OPTIONS` be set if you do not use default configurations for `password_hash` when creating password. If not, Auth will still be able to match passwords, but it will also re-hash any password not hashed using those configurations. This is a feature that is meant to automatically update passwords when configurations are changed, for example if PHP makes security changes in the future. Best option is to hash passwords using PHP's defaults and leave these settings be unset. That ensures proper future security updates.

#### Drivers

Additional drivers can be added by creating a class `driver\Auth\[driver name]\User` which extends from `api\Auth\Session\User`. Then register the class with IMPHP and add the name of the new driver to `AUTH_DRIVER`.
