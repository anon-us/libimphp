[Back to Home](https://github.com/IMPHP/libimphp)

______________________________

## FileImport

If you are using the `Router` library, or one compatible with it's abstraction, you can make use of the `FileImport` library as well. This library can help you include client side files from multiple sources without having to keep track of whether a file has already been included. It's stack feature also allows you to include multiple files at once while preserving the correct order regardless if some of the files has already been included. You can pre-define stacked groups within the IMPHP settings and include them based on their group name.

The library makes use of the `imAddImport` within the routers `Controller` class to include all files at the end of the request. As such it will only work if you use the libimphp Router library to load pages.

If you build your own routing system, it is still possible to use `FileImport`. By adding `'FILE_IMPORT_DISABLE_AUTOINCLUDE'=>false` to the IMPHP settings, you will disable the auto include feature in `FileImport`. You can then implement `FileImport` by manually retrieving collected file paths by using `getStyleImports` and `getScriptImports`. The library will still handle all collecting and dependency sorting.


#### Example

__Single Import__

```php
/*
 * Include a single file. This is equal to calling 'include_once' when including PHP files
 */
FileImport::import("../myfile.css", __DIR__);

/*
 * Script files works with the same method
 */
FileImport::import("../myscript.js", __DIR__);
```

__Stacked Import__

Like mentioned above, you can also import a stack of files

```php
FileImport::import("../myfile2.css", __DIR__);

/*
 * The order of files will be preserved
 */
FileImport::importStack(["../myfile1.css", "../myfile2.css", "../myfile3.css"], __DIR__);
```
Even though we included `myfile2.css` at the top, the order in the second import will be preserved, meaning that `myfile1.css` will still be imported before `myfile2.css` and `myfile3.css` will be imported after.

__Grouped Import__

To use the group feature, you need to define group stacks in settings.

```php
"FILE_IMPORT" => [
    "mygroup" => [
        __DIR__."/path/myfile1.css",
        __DIR__."/path/myfile2.css",
        __DIR__."/path/myfile3.css",
        __DIR__."/path/myscript.js"
    ]
]
```
Then you can simply import the whole group with one call.

```php
FileImport::importGroup("mygroup");
```
Each group can be mixed with both style and script files.

#### Configuring

* `FILE_IMPORT`: An associated array containing all groups to be used with `importGroup()`. Each key should be the group name and each value should be an indexed array containing the path to all files within the group.
* `FILE_IMPORT_DISABLE_AUTOINCLUDE`: A boolean that if set to `TRUE` will disable the auto include feature. This can be used of you want to manually include the collected files in a different way, for example with a custom routing feature.
