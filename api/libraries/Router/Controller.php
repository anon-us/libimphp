<?php
/*
 * This file is part of the IMPHP Project: https://github.com/IMPHP
 *
 * Copyright (c) 2016 Daniel Bergløv
 *
 * IMPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * IMPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with IMPHP. If not, see <http://www.gnu.org/licenses/>
 */

namespace api\libraries\Router;

use core\Runtime;
use api\libraries\Path;

use ReflectionObject;

/**
 * Base controller class
 *
 * All page/controller classes should extend from this
 *
 * @package api\libraries\Router
 */
abstract class Controller {

    /** @ignore */
    private /*array*/ $mImLoadingValues = [];

    /** @ignore */
    private /*array*/ $mImObjectInfo = null;

    /** @ignore */
    private /*string*/ $mImContentCharset = "utf-8";

    /** @ignore */
    private /*string*/ $mImContentType = "text/html";

    /** @ignore */
    private /*string*/ $mImContentAttachment = null;

    /** @ignore */
    private /*int*/ $mImResponseCode = 200;

    /** @ignore */
    private /*array*/ $mImImport = [];

    /** @ignore */
    protected /*array*/ $mImResponseHeaders = [
        200	=> "OK",
        201	=> "Created",
        202	=> "Accepted",
        203	=> "Non-Authoritative Information",
        204	=> "No Content",
        205	=> "Reset Content",
        206	=> "Partial Content",
        300	=> "Multiple Choices",
        301	=> "Moved Permanently",
        302	=> "Found",
        304	=> "Not Modified",
        305	=> "Use Proxy",
        307	=> "Temporary Redirect",
        400	=> "Bad Request",
        401	=> "Unauthorized",
        403	=> "Forbidden",
        404	=> "Not Found",
        405	=> "Method Not Allowed",
        406	=> "Not Acceptable",
        407	=> "Proxy Authentication Required",
        408	=> "Request Timeout",
        409	=> "Conflict",
        410	=> "Gone",
        411	=> "Length Required",
        412	=> "Precondition Failed",
        413	=> "Request Entity Too Large",
        414	=> "Request-URI Too Long",
        415	=> "Unsupported Media Type",
        416	=> "Requested Range Not Satisfiable",
        417	=> "Expectation Failed",
        500	=> "Internal Server Error",
        501	=> "Not Implemented",
        502	=> "Bad Gateway",
        503	=> "Service Unavailable",
        504	=> "Gateway Timeout",
        505	=> "HTTP Version Not Supported"
    ];

    /**
     * Start loading controller
     *
     * This is where the controller takes over the request.
     *
     * @api
     */
    public function __construct() {

    }

    /**
     * Change the response code sent to the client
     *
     * A proper header and text will be generated based on this code
     *
     * @api
     *
     * @param int $code
     *      The response code, will only be set if matching a valid http code
     */
    public function imSetResponseCode(int $code) /*void*/ {
        if (array_key_exists($code, $this->mImResponseHeaders)) {
            $this->mImResponseCode = $code;
        }
    }

    /**
     * Get the current response code
     *
     * @api
     */
    public function imGetResponseCode(): int {
        return $this->mImResponseCode;
    }

    /**
     * Get the current content type
     *
     * @api
     */
    public function imGetContentType(): string {
        return $this->mImContentType;
    }

    /**
     * Change the current content type
     *
     * @api
     *
     * @param string $type
     *      The new content type
     *
     * @param string $charset=null
     *      Set a new character encoding than utf-8
     *
     * @param string $filename=null
     *      Attach a filename header, only if you plan on providing downloadable content
     */
    public function imSetContentType(string $type, string $charset=null, string $filename=null) /*void*/ {
        $this->mImContentType = $type;
        $this->mImContentAttachment = $filename;

        if ($charset !== null) {
            $this->mImContentCharset = $charset;
        }
    }

    /**
     * Import html to the content head
     *
     * This is not the optimal way of adding content to your page,
     * but it is a great solution if everything else fails.
     * Just consider other solutions before using this.
     *
     * This method allows you to store different types of html data
     * that will be inserted into the document just before it is sent to the client.
     *
     * __Possible Keys__
     *
     *      * 'head': Inserts the data into the head section. Use $prepend to decide if it should be at the top or bottom.
     *      * 'body': Inserts the data into the body section. Use $prepend to decide if it should be at the top or bottom.
     *      * 'script': Inserts JavaScript between a shared script tag in the head section. Use $prepend to prepend the script data to the currently stored.
     *      * 'style': Inserts CSS between a shared style tag in the head section. Use $prepend to prepend the css data to the currently stored.
     *      * 'importScript': This should simply be a URI to a JavaScript file. The tags are auto generated.
     *      * 'importStyle': This should simply be a URI to a CSS file. The tags are auto generated.
     *
     * @api
     *
     * @param string $html
     *      HTML that should be added to the document head
     */
    public function imAddImport(string $html, string $section=null, bool $prepend=false) /*void*/ {
        if ($section === null) {
            $section = "head";
        }

        if ($section == "head" || $section == "body") {
            $section = $prepend ? "<$section>" : "</$section>";
        }

        if (!isset($this->mImImport[$section])) {
            $this->mImImport[$section] = [];
        }

        if ($prepend && strpos($section, "<") === false) {
            array_unshift($this->mImImport[$section], $html);

        } else {
            $this->mImImport[$section][] = $html;
        }
    }

    /**
     * Callback from the router when output is about to be sent
     *
     * This will generate headers based on default or altered information from
     * `imSetContentType()`, `imSetResponseCode()` and so on.
     *
     * This should not be called manually.
     * And if you overwrite it, consider calling this after.
     *
     * @ignore
     *
     * @param string $output
     *      Output received by the Router
     *
     * @return string
     *      The output that should be sent
     */
    public function imOnPrepareOutput(string $output): string {
        $type = $this->mImContentType;
        $charset = $this->mImContentCharset;
        $protocol = Runtime::$SERVER->getString("SERVER_PROTOCOL", "HTTP/1.0");
        $code = $this->mImResponseCode;
        $codeTxt = $this->mImResponseHeaders[$code];

        if (!empty($this->mImImport)) {
            /*
             * TODO:
             *      Different approach?
             */
            foreach (["importStyle", "importScript", "style", "script", "<body>", "<head>", "</body>", "</head>"] as $key) {
                if (isset($this->mImImport[$key])) {
                    switch ($key) {
                        case "<body>":
                        case "<head>":
                            $output = preg_replace("#(".$key.")#", "$1\n".implode("\n", $this->mImImport[$key]), $output, 1); break;

                        case "</body>":
                        case "</head>":
                            $output = preg_replace("#(".$key.")#", implode("\n", $this->mImImport[$key])."\n$1", $output, 1); break;

                        case "importScript":
                            $output = preg_replace("#(</head>)#", "\n<script type=\"text/javascript\" src=\"".implode("\"></script>\n<script type=\"text/javascript\" src=\"", $this->mImImport[$key])."\"></script>\n$1", $output, 1); break;
                        case "importStyle":
                            $output = preg_replace("#(</head>)#", "\n<link rel=\"stylesheet\" media=\"all\" href=\"".implode("\" />\n<link rel=\"stylesheet\" media=\"all\" href=\"", $this->mImImport[$key])."\" />\n$1", $output, 1); break;

                        case "script":
                            $output = preg_replace("#(</head>)#", "\n<script type=\"text/javascript\">\n".implode("\n", $this->mImImport[$key])."\n</script>\n$1", $output, 1); break;
                        case "style":
                            $output = preg_replace("#(</head>)#", "\n<style type=\"text/css\">\n".implode("\n", $this->mImImport[$key])."\n</style>\n$1", $output, 1);
                    }
                }
            }
        }

        if ($this->mImContentAttachment !== null) {
            header("Content-Disposition: attachment; filename='".$this->mImContentAttachment."'", true);
        }

        header("$protocol $code $codeTxt", true, $code);
        header("Content-Type: $type;charset=$charset", true);

        return $output;
    }

    /**
     * Load a content file to build html, css, javascript etc.
     *
     * This will load a content file and sent output to the client,
     * or return it if requested. This should always be used to load content,
     * mostly because it might provide additional features in the future.
     *
     * File path is resolved using `Path::toAbsolute`, which means that this method has all of the benefits
     * of that tool.
     *
     * If $__im_from is not defined, the location will be resolved relative to the location of
     * the extending from this.
     *
     * @param string $__im_path
     *      A file path that can be resolved via `Path::toAbsolute`
     *
     * @param array $__im_values=null
     *      An array of data where keys will be converted to variables
     *
     * @param string $__im_from=null
     *      An optional path that $__im_path will be resolved relative to
     *
     * @param bool $__im_return=false
     *      If true, output will be returned instead of sent to the client
     */
    public function imLoadContent(string $__im_path, array $__im_values=null, string $__im_from=null, bool $__im_return=false) /*string*/ {
        if ($this->mImObjectInfo === null) {
            $__im_reflect = new ReflectionObject($this);
            $__im_object_file = $__im_reflect->getFileName();

            if ($__im_object_file !== false) {
                $this->mImObjectInfo = [
                    "DIRECTORY" => dirname($__im_object_file)
                ];
            }
        }

        ob_start();

        $__im_path = Path::toAbsolute($__im_path, ($__im_from ?? $this->mImObjectInfo["DIRECTORY"] ?? null));
        $this->mImLoadingValues[] = $__im_values;

        foreach ($this->mImLoadingValues as $__im_pos => &$__im_cache) {
            if ($__im_cache != null) {
                foreach ($__im_cache as $__im_key => &$__im_value) {
                    ${$__im_key} = &$this->mImLoadingValues[$__im_pos][$__im_key];
                }
            }
        }

        include $__im_path;

        array_pop($this->mImLoadingValues);

        if ($__im_return) {
            return ob_get_clean();

        } else {
            ob_end_flush();
        }

        return null;
    }
}
