<?php
/* *******************
 * index.php
 * *******************
 * 
 * This page works as start up page for the project.
 * The project implements the FrontController Design Pattern.
 * 
 * For references:
 * https://webdevetc.com/blog/the-front-controller-design-pattern-in-php
 *  * 
 * PRO:
 * - a unique point of access for the PHP project
 * 
 * VERSUS:
 * - the links point on a single page (index.php), this can be less understanding for search engines
 * 
 * ROUTING CONTROL
 * In order to resolve the Versus explained, we use an addictional .htaccess configuration for rules 
 * the Apache web server to route every call to this index.php file.
 */


/* *****
 * DEBUG MODE AND PHP ERROR MANAGEMENT
 * 
 * To set the correct error display mode, the project works with a flag (DEBUG_MODE) that manages the DEBUG MODE.
 * DEBUG_MODE is int, and can use these values:
 * - DEBUG_MODE_DEEP = 2, for all the debug information
 * - DEBUG_MODE_STD = 1, for PHP errors
 * - DEBUG_MODE_DISABLE = 0, for no debug information
 * 
 * A second flag (UNIPD_DELIVER) is used to switch from developer server parameters to the UniPD server parameters.
 * */
define("UNIPD_DELIVER", false);
define("DEBUG_MODE_DEEP", 2);
define("DEBUG_MODE_STD", 1);
define("DEBUG_MODE_DISABLE", 0);
if(UNIPD_DELIVER)
	define("DEBUG_MODE", DEBUG_MODE_DISABLE);
else
	define("DEBUG_MODE", DEBUG_MODE_DEEP);

/** */
if(DEBUG_MODE != DEBUG_MODE_DISABLE)
{
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}else
{
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
}

/**
 * PROJECT INITIALIZATION
 */

/* ROOT_DIR (const) is used to get the real path of the index.php, which is also the root folder of the project.
 * This is needed in order to map correctly the links used by the project with the FrontController. */
define('ROOT_DIR', __DIR__);

/* BASE_DIR (const) is initialized with the virtual web root folder path.
 * This is needed in order to map correctly the links used by the project with the FrontController. */
$basedir = dirname($_SERVER['PHP_SELF']);
if($basedir == "/")
	$basedir = "";
define("BASE_DIR", $basedir);
define("BASE_PATH", substr(BASE_DIR, 1));

/* Inclusion of all requires libraries */
require_once("includes/init.php");
require_once("includes/Application.php");

Application::current()->run();
