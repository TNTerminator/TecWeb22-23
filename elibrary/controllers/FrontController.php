<?php
/**
 * FrontController class acts as a Front Controller for the project.
 * It parses the URI in order to determine the route for the user request.
 */

class FrontController
{
    const DEFAULT_CONTROLLER_SHORT = "index";
    const DEFAULT_CONTROLLER = "IndexController";
    const DEFAULT_ACTION_SHORT = "index";
    const DEFAULT_ACTION = "indexAction";
	const DEFAULT_ERROR_CONTROLLER_SHORT = "error";
	const DEFAULT_ERROR_CONTROLLER = "ErrorController";
	const DEFAULT_ERROR_ACTION_SHORT = "general";
	const DEFAULT_ERROR_ACTION = "generalAction";
    
	/**
	 * Controller property contains the destination Controller of the user call, or of the FrontController execution (f.e. ErroController)
	 */
    protected $_Controller = self::DEFAULT_CONTROLLER;
	protected $_ControllerShort = self::DEFAULT_CONTROLLER_SHORT;

	/**
	 * Action property contains the destination Action to call on destination Controller
	 */
    protected $_Action = self::DEFAULT_ACTION;
	protected $_ActionShort = self::DEFAULT_ACTION_SHORT;

	/**
	 * Params property contains the list of params passed by the user call
	 */
    protected $_Params = array();

	/**
	 * DbManager static property contains a DbManager object in singleton pattern, in order to interact with database.
	 */
	private static $_DbManager = null;
	public static function DbManager()
	{
		if(self::$_DbManager == null)
			self::$_DbManager = new DbManager();
		return self::$_DbManager;
	}

	/**
	 * Exceptions property contains the collection of exceptions activated and unmanaged at the present moment, for error management and visualization at global level.
	 */
	private $_Exceptions = array();
	public function addException($exception)
	{
		$this->_Exceptions[] = $exception;
		return $this;
	}
	public function getExceptions()
	{
		return $this->_Exceptions;
	}

	/**
	 * FrontController static property contains a reference to the unique frontcontroller object. Singleton pattern.
	 */
	private static $_FrontController;
	public static function getFrontController()
	{
		return self::$_FrontController;
	}

	/**
	 * The Front Controller needs to know the route info.
	 * The constructor call the URI parsing to obtain these.
	 * The routes table is passed as constructor argument
	 * 
	 * @param $routestable an associative array that map the available routes
	 */
    public function __construct() 
	{
		self::$_FrontController = $this;
		set_exception_handler(array($this, 'exceptionHandler'));

        $this->parseUri();
    }

	/**
	 * This method manages all the exceptions as global exception handler.
	 * It catches all the unmanaged exceptions in order to show a kind and smart error view.
	 * 
	 * @param $exception The exception object catched by PHP
	 */
	public function exceptionHandler($exception)
	{
		/* The exception handler does a full reinitialization of parameters, controller and action, then set the controller and action to error managements, save all the exception information and call the error management. */
		$this->parseUri();
		$this->_Controller = self::DEFAULT_ERROR_CONTROLLER;
		$this->_Action = self::DEFAULT_ERROR_ACTION;
		$this->addException($exception);
		$this->run();
	}
    
	/**
	 * The URI consists of a route with a predefined form.
	 * 
	 * The full form explicit the controller and the action to call:
	 * www.site.com/<controller>/<action>/<param1>/<value1/<optional next param>/<optional next value>/...
	 * 
	 * Any other form call the default controller and default action.
	 * 
	 * Controllers and actions are defined into the routes table.
	 */
    protected function parseUri() 
	{
        $path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
        if($path == BASE_PATH)
		{
        	// No action required: default controller and action will be called
        }else
		{
			if(BASE_PATH != '')
			{
				$path = trim(str_replace(BASE_PATH, "", $path), "/");
			}

			$paramsraw = explode("/", $path);
			$ctrl_found = count($paramsraw) > 0 ? $this->setController($paramsraw[0]) : false;
        	if($ctrl_found)
				array_shift($paramsraw);
			$action_found = count($paramsraw) > 0 ? $this->setAction($paramsraw[0]) : false;
			if($action_found)
			{
				if(!$ctrl_found)
					Application::Debug(DEBUG_MODE_DEEP, "Instead, found action " . $paramsraw[0] . " into the default controller, so it's all ok!");
				array_shift($paramsraw);
			}
			// If no controller and no action found, we are in page not found context
			if(!$ctrl_found && !$action_found)
			{
				$this->setAction("notfound");
			}

			$this->_Params = array();
			for($i = 0; $i < count($paramsraw); $i = $i + 2)
			{
				if($i + 1 < count($paramsraw))
					$this->_Params[$paramsraw[$i]] = $paramsraw[$i + 1];
				else
					$this->_Params[$paramsraw[$i]] = "";
			}
        } 
    }
    
    protected function setController($controller) 
	{
		$short = strtolower($controller);
        $controller = ucfirst($short) . "Controller";
        if (class_exists($controller))
		{
			$this->_Controller = $controller;
			$this->_ControllerShort = $short;
			return true;
		}
		else
		{
			Application::Debug(DEBUG_MODE_DEEP, "Controller class " . $controller . " not found!");
			return false;
		}
    }
	public function getController()
	{
		return $this->_Controller;
	}
	public function getControllerShort()
	{
		return $this->_ControllerShort;
	}
    
    protected function setAction($action) 
	{
		$short = strtolower($action);
		$action = $short . "Action";
        $reflector = new ReflectionClass($this->_Controller);
        if ($reflector->hasMethod($action))
		{
		    $this->_Action = $action;
			$this->_ActionShort = $short;
			return true;
		}
		else
		{	
			Application::Debug(DEBUG_MODE_DEEP, "Controller: " . $this->_Controller . " - Action " . $action . " not found!");
			return false;
		}
    }
	public function getAction()
	{
		return $this->_Action;
	}
	public function getActionShort()
	{
		return $this->_ActionShort;
	}
    
    public function run() 
	{
		$controller = new $this->_Controller;
		$reflector = new ReflectionMethod($controller, $this->_Action);
		$args = $reflector->getParameters();
		$params = array();
		foreach($args as $i => $arg)
		{
			if(array_key_exists($arg->name, $this->_Params))
				$params[$i] = $this->_Params[$arg->name];
			else
				$params[$i] = null;
		}
        call_user_func_array(array(new $controller, $this->_Action), $params);
    }

	public function redirect($path)
	{
		echo "<html><head><meta http-equiv=\"refresh\" content=\"0; URL='" . BASE_DIR.$path . "'\" /></head></html>";
		die();
	}

	public static function getUrl($controller = null, $action = null, $params = null)
	{
		if($action == null || $action == "")
			$action = self::DEFAULT_ACTION_SHORT;
		if($controller == null || $controller == "")
			$controller = self::DEFAULT_CONTROLLER_SHORT;

		$paramsstr = "";
		if($params != null && is_array($params))
			foreach($params as $key => $value)
				$paramsstr .= $key . "/" . $value . "/";

		return BASE_DIR . "/" . $controller . "/" . $action . "/" . $paramsstr;
	}
}