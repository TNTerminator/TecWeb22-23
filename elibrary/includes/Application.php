<?php
/**
 * Application.php
 * 
 * This class is the general container for application-level information and utilities.
 * 
 */

/* *****
 * GLOBAL CLASS LOADING 
 * */
require_once("includes/GeneralException.php");
require_once("includes/DbException.php");
require_once("includes/DbManager.php");
require_once("includes/View.php");
require_once("includes/createThumbnail.php");
require_once("models/Author.php");
require_once("models/Book.php");
require_once("models/Category.php");
require_once("models/Order.php");
require_once("models/User.php");
require_once("controllers/AdminController.php");
require_once("controllers/AuthController.php");
require_once("controllers/AuthorsController.php");
require_once("controllers/BooksController.php");
require_once("controllers/CategoriesController.php");
require_once("controllers/ErrorController.php");
require_once("controllers/FrontController.php");
require_once("controllers/IndexController.php");
require_once("controllers/OrdersController.php");
require_once("controllers/ProfileController.php");
require_once("controllers/UsersController.php");

class Application
{
	/** GENERIC CONSTANTS */
	const ERROR = 1;
	const WARNING = 2;
	const NOTICE = 3;
	
	/** PROJECT CONSTANTS */
	const PROJECT_TITLE = "eLibrary";
	const PROJECT_SUBTITLE = "La tua libreria online";
	const PROJECT_AUTHORS = "MM";
	const ROOT_DIR = ROOT_DIR;
	const BASE_DIR = BASE_DIR;
	const BASE_PATH = BASE_PATH;

	private static $_currentApplication;

	public static function current()
	{
		if(self::$_currentApplication == null)
			self::$_currentApplication = new Application();
		return self::$_currentApplication;
	}

	public function run()
	{
		/* *****
		* SESSION INIT 
		* */
		session_start();

		/* Starting up the FrontController. */
		$FrontController = new FrontController();
		$FrontController->run();
	}

	public static function Debug($debugmode, $warning)
	{
		if(DEBUG_MODE == $debugmode)
			echo "<div>" . $warning . "</div>";
	}

	public static function cleanInput($value, $clearHtml = true)
	{
		$value = trim($value);
		if($clearHtml)
			$value = strip_tags($value);
		$value = htmlentities($value);
		$value = str_replace("#", "&num;", $value);
		return $value;
	}

	public static function stringToFloat($str)
	{
		return floatval(str_replace(",", ".", $str));
	}

	public static function floatToString($float)
	{
		return number_format($float, 2, ",", "");
	}

	public static function priceToString($price)
	{
		return "&euro; " . self::floatToString($price);
	}
}