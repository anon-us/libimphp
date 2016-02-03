[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## Path

It might not sound important, but every platform needs some good tools to work with paths, both for files on the server and for handling uri strings. libimphp of cause packs such library. It's not a large class, but it has what it needs.

#### Converting Relative/Absolute

PHP has a function that converts a relative path into an absolute one. One problem with it is that it can only convert real existing paths as it builds based on CWD. The Path library has a similar tool, but with more options. For example you can parse another path to base the relative path on. Neither of these paths actually has to exist.

```php
Path::toAbsolute(".././file", "/my/absolute/path");
```  
The above will output `/my/absolute/file`. If you don't parse the second argument, paths will be converted based on IMPHP's root path.

The other problem with PHP is that you cannot go the other way. The Path library also contains a tool for converting absolutes to relatives.

```php
Path::toRelative("/my/absolute/path/file", "/my/absolute");
```
The above will output `./path/file`.

#### Working with URI's

You are testing your site at home before uploading everything to your server. Your URI's changes between `http://localhost` and `https://domain` and you want the correct once to be in your links. No problem.

```
Path::link();
```
The above will print a complete uri for our current request, this includes protocol, port, domain, path, querystring and more. What you see in your browsers address bar is what this will print.

But that is not all it does. It can also manipulate with the output based on very little input. Let's say that your current full uri is something like `http://localhost/my/web/root/?$=/my/router/path&what=whatIsThis`.
```
Path::link("../");
```
The above will simply remove the `/path` segment and return the rest. And if you don't want any additional querystrings, just define an empty one.
```
Path::link("?");
```
The above will return the path without the additional `what` querystring. Or you can just change the value on that querystring.
```
Path::link("?what=newValue");
```

The `link()` method is very powerful and yet simple to use. You can add both relative paths that will convert the existing location, or you can add absolute paths to completely replace it. You can add/reset or alter querystrings and more.

#### File Links

One last important thing. You may want to include client side file paths such as images, scripts, styles etc. The Path library has a simple tool to help with this as well.

```
Path::fileLink("file.css", __DIR__);
```
This method works similar to `link()`. You can parse relative and absolute paths, and if the base path (second argument) is not defined, then IMPHP's root dir will be used.

What this does it create a valid uri for `file.css` that can be used to add to a html `link` tag in the document. It can also be used to link to download-able files.

The above example would print something like `http://localhost/my/web/root/sub/folder/file.css?id=1763839683`. The id is an auto generated integer that makes sure that the browser cache get's updated. It can be excluded by parsing `TRUE` as 3'rd argument, but the best option is to add a static id number to the settings _(Explained further down)_. This id can be manually changed whenever you make changes to client included files to ensure that they are updated in the cache.

#### Configuring

* `PATH_ID_TAG`: This is added as an ?id tag to file paths used for content loading like css files, javascript files, images etc. It ensures that old caches can be cleared from clients. Simply increase the number and it will make a forced reload of all cached content. If you set this to '0' it will disable the tags (they are not added). If you set this to '-1' it will enable debug mode and generate random id's on each request.
* `PATH_URL_REWRITE`: If set to true, the Path library will generate links intended for URL Rewrite. Otherwise the location string will be added to the '$' querystring.
