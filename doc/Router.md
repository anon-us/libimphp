[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Router

The Router's task is to route a uri to the proper controller for generating the requested content or performing a requested task. It's similar to just enter a specific file in the url of the browser, only using libimphp's router, every request goes through the instantiation of IMPHP and the core of this library first. When the request reaches the proper controller, everything is setup and configured properly.

Another great thing about using controllers, is that it is easy to redirect users internally without them even knowing about it. It can be done without as well, but requires that the page "redirecting" knows the page to include. Using routers this works dynamically.

It all comes together an creates a nice consistent flow.

#### URI

Router Requests uses IMPHP's `$` querystring to define the path. It looks and works like a regular uri, but is actually just a `GET` value. The great thing about this structure is that it can be transformed into real looking uri's when used in combination with server uri rewrite rules.

#### Routes

In order to guide a request to the proper controller, you must first bind locations to controllers. By location we mean the part from `$`. This is done by defining an array `ROUTER` in the IMPHP settings. The array uses the location as key and the controller, namespace and class name, as value. Controllers must reside within the `page` namespace, but this part should be left out from the route definition. It will be added by the router.

```php
'ROUTER' => [
    '/login' => 'user\Login'
]
```
If you access the site with the uri `http://domain/?$=/login`, the router will invoke the controller `page\user\Login`. The controller class then has to be placed within `data/page/user/Login.php` and contain a class named `Login` that extends `api\libraries\Router\Controller`.

You can also use regular expressions to define unknown values.
```php
'ROUTER' => [
    '/user/[0-9]+' => 'user\Profile'
]
```
This will allow something like `http://domain/?$=/user/10`. Or you can use the built-in prefixes.

* `:num:` - The segment must contain numbers
* `:alpha:` - The segment may contain alphanumeric (A-Za-z 0-9 _ and -)
* `:all:` - Anything goes, even multiple segments _(Almost anything, IMPHP as some security uri restrictions)_

You can also redirect one location to another from within the routing information.
```php
'ROUTER' => [
    '/login' => ':/user/login',
    '/user/login' => 'user\Login'
]
```
The above will redirect `http://domain/?$=/login` to `http://domain/?$=/user/login`, but only internally. It will not affect the client. This is done by adding `:` to the beginning followed by the new location. You can combine regexp with this as well.
```php
'ROUTER' => [
    '/user/(:num:)' => ':/user/profile/$1',
    '/user/profile/(:num:)' => 'user\Profile'
]
```
The above will redirect `http://domain/?$=/user/10` to `http://domain/?$=/user/profile/10`. Again this is done internally.

#### Examples

```php
if ( /* Some Condition */ ) {
    Router::request("/redirect/to/this/location");
}
```
The above will do the same as redirecting from within the route array, but can be done from inside an already loaded controller. The condition could for example be a login test and the redirect could be to a login controller in case the user is not logged-on. Not that if this is called from inside a controller scope, it will be set as pending and then executed ones the working controller finishes.
```php
// Check against the `$` auerystring value
if ( Runtime::$SYSTEM["URI_LOCATION"] == "/user/10" ) {

}

// Check against the router request
if ( Router::getRequest() == "/user/10" ) {

}

// Check against the second segment in the `$` auerystring value
if ( Runtime::$SYSTEM["REQUEST_LOCATION"]->get(1) == "10" ) {

}

// Check against the second segment of the router request
if ( Router::getSegment(1) == "10" ) {

}

// Check against the last segment in the `$` auerystring value
if ( Runtime::$SYSTEM["REQUEST_LOCATION"]->get(-1) == "10" ) {

}

// Check against the last segment of the router request
if ( Router::getSegment(-1) == "10" ) {

}
```
There are two ways to check a request. The actual request or the one the router is using _(the actual page being used)_. In most cases these will be the same, but if the system has made a redirect at some point, the actual request and the content being generated will not match. So it's important to know when to check which value. In most cases when using a router system, it is the routers value that is important. That's the one reflection what action/content has been set in motion

#### Configuring

* `ROUTER`: An array with all the available routes.
* `ROUTER_DRIVER`: The routing resolver driver to use. Available and default is `imphp`.
* `ROUTER_ENABLED`: Allows you to disable the router. If the router is not to be used, for example if you use something else for launching some sort of page file, you can set this to false to avoid the libimphp router starting and using resources.
* `ROUTER_[0-9]+`: This will register a special page with the router. these cannot be accessed from the uri by the client, it can only be manually redirected to. The Router already redirects 404 responses to "ROUTER_404" if this is set.

#### Drivers

Additional drivers can be added by creating a class `driver\Router\[driver name]\Resolver` which extends from `api\libraries\Router\Resolver`. Then register the class with IMPHP and add the name of the new driver to `ROUTER_DRIVER`.
