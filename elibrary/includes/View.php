<?php
/**
 * View.php
 * 
 * This class contains the logic to build and show the HTML view.
 */

class View
{
	const MARK_START = "##-";
	const MARK_END = "-##";
	const VIEW_PATH = "views/";
	const TEMPLATE_PATH = "views/template/";
	const VIEW_NOTFOUND_PATH = "views/notfound.html";

	/**
	 * Full constructor
	 * 
	 * @param $name string for the internal name of the view
	 * @param $title string for the HTML title of the page
	 * @param $id string for the HTML id attribute of the page
	 * @param $template string for calling the correct HTML template for view showing
	 */
	public function __construct($name = null, $title = null, $id = null, $template = null)
	{
		$this->setName($name);
		$this->setTitle($title);
		$this->setId($id);
		$this->setTemplate($template);
	}

	/** 
	 * Property Name for the view
	 * This property is used to naming this view.
	 */
	private $_Name;

	public function setName($name)
	{
		$this->_Name = $name;
		return $this;
	}

	public function getName()
	{
		return $this->_Name;
	}

	/** 
	 * Property Path for the view
	 * This property is used to find the html view related to this view.
	 */
	private $_Path;

	public function setPath($path)
	{
		$this->_Path = $path;
		return $this;
	}

	public function getPath()
	{
		return $this->_Path;
	}

	/** 
	 * Property Title for the view
	 */
	private $_Title;

	public function setTitle($title)
	{
		$this->_Title = $title;
		return $this;
	}

	public function getTitle()
	{
		return $this->_Title;
	}

	/**
	 * Property ID for the view
	 */
	private $_Id;

	public function setId($id)
	{
		$this->_Id = $id;
		return $this;
	}

	public function getId()
	{
		return $this->_Id;
	}

	/**
	 * Property Template for the view
	 * This property is used to find the html template for the view.
	 */
	private $_Template;

	public function setTemplate($template)
	{
		$this->_Template = $template;
		return $this;
	}

	public function getTemplate()
	{
		return $this->_Template;
	}

	/**
	 * Property Authors for the view
	 * This property store the Authors information about a specific view.
	 */
	private $_Authors;

	public function setAuthors($authors)
	{
		$this->_Authors = $authors;
		return $this;
	}

	public function getAuthors()
	{
		return $this->_Authors;
	}

	/**
	 * Property Keywords for the view
	 * This property store the Keywords information about a specific view.
	 */
	private $_Keywords;

	public function setKeywords($keywords)
	{
		$this->_Keywords = $keywords;
		return $this;
	}

	public function getKeywords()
	{
		return $this->_Keywords;
	}

	/**
	 * Property Description for the view
	 * This property store the Description information about a specific view, useful for SEO.
	 */
	private $_Description;

	public function setDescription($description)
	{
		$this->_Description = $description;
		return $this;
	}

	public function getDescription()
	{
		return $this->_Description;
	}

	/**
	 * Property Content for the view
	 * This property is a runtime property that contains the HTML view content.
	 */
	private $_Content;

	private function setContent($content)
	{
		$this->_Content = $content;
		return $this;
	}

	private function getContent()
	{
		return $this->_Content;
	}

	/**
	 * Property Breadcrumbs for the view
	 * This property is used to manage an array of links for the Breadcrumbs.
	 */
	private $_Breadcrumbs = array();

	public function addBreadcrumb($title, $href, $attrs = "")
	{
		$this->_Breadcrumbs[] = array(
			"title" => $title,
			"href" => $href,
			"attrs" => $attrs
		);
		return $this;
	}

	public function emptyBreadcrumb()
	{
		$this->_Breadcrumbs = array();
	}

	public function getBreadcrumbs()
	{
		$html = "";
		if(count($this->_Breadcrumbs) > 0)
		{
			/* 
			Riferimento: 
			https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/
			https://www.w3.org/WAI/ARIA/apg/example-index/breadcrumb/index.html
			*/
			/* TODO teniamo il link sull'elemento corrente?! anche considerando che è markato con ARIA? */
			$html .= "<nav id=\"breadcrumbs\" aria-label=\"Breadcrumbs\">
			<span id=\"breadcrumbs_prefix\">Ti trovi in:</span>
			<ol>";
			$bc_size = count($this->_Breadcrumbs);
			for($i=0; $i<$bc_size; $i++)
			{
				$bd = $this->_Breadcrumbs[$i];
				$html .= "<li>";
				if($bd["href"] != null)
				{
					$html .= "<a href=\"" . BASE_DIR.$bd["href"] . "\" " . $bd["attrs"];
					if($i == $bc_size-1)
						$html .= "aria-current=\"page\"";
					$html .= ">";
				}
				$html .= $bd["title"];
				if($bd["href"] != null)
					$html .= "</a>";
				$html .= "</li>";
			}
			$html .= "</ol>
			</nav>";
		}
		return $html;
	}

	/**
	 * DIctionary property allows to pass an associative array to the view for mark replacement.
	 * 
	 * @param $key string for the key
	 * @param $value string for the value content for $key
	 */
	private $_Dictionary = array();
	public function addDictionary($key, $value)
	{
		$this->_Dictionary[strval($key)] = strval($value);
	}
	public function getDictionary($key)
	{
		if(array_key_exists(strval($key), $this->_Dictionary))
			return $this->_Dictionary[strval($key)];
		else
			return "";
	}
	/**
	 * Only for debug purpose
	 */
	public function printDictionary()
	{
		if(DEBUG_MODE == DEBUG_MODE_STD || DEBUG_MODE == DEBUG_MODE_DEEP)
			print_r($this->_Dictionary);
	}

	/**
	 * Property Messages saves error messages and normal messages to show on view rendering.
	 * Each message is an associative array with this structure:
	 * msg = array(
	 * 		"message" => string describing the error,
	 * 		"ref" => string identifying the object, field or component affected,
	 * 		"type" => int type of message, see View::MSG_TYPE_XX constants for description
	 * )
	 */
	public const MSG_TYPE_INFO = 1;
	public const MSG_TYPE_SUCCESS = 2;
	public const MSG_TYPE_ERROR = 3; // Error in rendering or in processing data that stop the logic flow
	public const MSG_TYPE_WARNING = 4; // Warning about something miss like form mandatory data
	public const MSG_TYPE_NOTICE = 5; // Notice about something usefull for the user
	private $_Messages = array(
		self::MSG_TYPE_INFO => array(),
		self::MSG_TYPE_SUCCESS => array(),
		self::MSG_TYPE_ERROR => array(),
		self::MSG_TYPE_WARNING => array(),
		self::MSG_TYPE_NOTICE => array()
	);

	public function addMessage($type, $message, $ref = null)
	{
		switch($type)
		{
			case self::MSG_TYPE_INFO:
			case self::MSG_TYPE_SUCCESS:
			case self::MSG_TYPE_ERROR:
			case self::MSG_TYPE_WARNING:
			case self::MSG_TYPE_NOTICE:
				break;
			
			default:
				$type = self::MSG_TYPE_INFO;
		}
		$this->_Messages[$type][] = array("type" => $type, "message" => $message, "ref" => $ref);
		return true;
	}

	protected function printMessageDiv($type, $title)
	{
		$html = "";
		if(count($this->_Messages[$type]) > 0)
		{
			$html .= "<div class=\"message ";
			switch($type)
			{
				default:
				case self::MSG_TYPE_INFO:
					$html .= "info ";
					break;
				case self::MSG_TYPE_SUCCESS:
					$html .= "success ";
					break;
				case self::MSG_TYPE_ERROR:
					$html .= "error ";
					break;
				case self::MSG_TYPE_WARNING:
					$html .= "warning ";
					break;
				case self::MSG_TYPE_NOTICE:
					$html .= "info  ";
					break;
			}
			$html .= " float\">";
			$html .= "<div class=\"message_title\">" . $title . "</div>
			<div class=\"message_content\">";

			$list = count($this->_Messages[$type]) > 1;

			if($list)
				$html .= "<ul>";
			foreach($this->_Messages[$type] as $msg)
			{
				if($list)
					$html .= "<li>";
				$html .= $msg["message"];
				if($msg["ref"] != null && $msg["ref"] != "")
					$html .= " - riferito a " . $msg["ref"];
				if($list)
					$html .= "</li>";
			}
			if($list)
				$html .= "</ul>";
			$html .= "</div>
		</div>";
		}
		return $html;
	}

	public function getInfoMsg()
	{
		return $this->printMessageDiv(self::MSG_TYPE_INFO, "Informazioni");
	}

	public function getSuccessMsg()
	{
		return $this->printMessageDiv(self::MSG_TYPE_SUCCESS, "Operazione completata con successo");
	}

	public function getErrorsCritical()
	{
		return $this->printMessageDiv(self::MSG_TYPE_ERROR, "Errori");
	}

	public function getWarnings()
	{
		return $this->printMessageDiv(self::MSG_TYPE_WARNING, "Attenzione");
	}

	public function getNotices()
	{
		return $this->printMessageDiv(self::MSG_TYPE_NOTICE, "Avvisi");
	}

	public function getErrors($errortype = null)
	{
		switch($errortype)
		{
			case self::MSG_TYPE_ERROR:
			case self::MSG_TYPE_WARNING:
			case self::MSG_TYPE_NOTICE:
				return $this->getMessages($errortype);

			default:
				return $this->getErrorsCritical() . $this->getWarnings() . $this->getNotices();
		}
	}

	public function getMessages($msgtype = null)
	{
		switch($msgtype)
		{
			case self::MSG_TYPE_INFO:
				return $this->getInfoMsg();

			case self::MSG_TYPE_SUCCESS:
				return $this->getSuccessMsg();

			case self::MSG_TYPE_ERROR:
				return $this->getErrorsCritical();

			case self::MSG_TYPE_WARNING:
				return $this->getWarnings();
			
			case self::MSG_TYPE_NOTICE:
				return $this->getNotices();

			default:
				return $this->getErrorsCritical() . $this->getWarnings() . $this->getNotices() . $this->getInfoMsg() . $this->getSuccessMsg();
		}
	}



	/**
	 * Property FormAction contains the action for the form included into the View, if any
	 */
	private $_FormAction;

	public function setFormAction($actionpath)
	{
		$this->_FormAction = BASE_DIR.$actionpath;
		return $this;
	}

	public function getFormAction()
	{
		return $this->_FormAction;
	}

	/**
	 * Property FormErrors contains the report for errors in user input for specific fields.
	 * The structure use the field name as key for which there is ad array of detected errors in string form.
	 */
	private $_FormErrors = array();

	public function addFormError($fieldname, $message)
	{
		if(!isset($this->_FormErrors[$fieldname]))
			$this->_FormErrors[$fieldname] = array();
		$this->_FormErrors[$fieldname][] = $message;
		return true;
	}

	public function getFieldErrors($fieldname)
	{
		$html = "";
		if(isset($this->_FormErrors[$fieldname]) && count($this->_FormErrors[$fieldname]) > 0)
		{
			$html .= "<ul class=\"fielderrors\">";
			foreach($this->_FormErrors[$fieldname] as $error)
			{
				$html .= "<li>" . $error . "</li>";
			}
			$html .= "</ul>";
		}
		return $html;
	}


	/**
	 * This method build the HTML for the view showing.
	 */
	public function render()
	{
		// Get the View Content
		$viewpath = self::VIEW_PATH . $this->getPath();
		if(file_exists($viewpath) && !is_dir($viewpath))
			$this->setContent(file_get_contents($viewpath));
		else
		{
			Application::Debug(DEBUG_MODE_DEEP, "View file " . $viewpath . " not found!");
			$this->setContent(file_get_contents(self::VIEW_NOTFOUND_PATH));
		}

		$html = "";
		$templatepath = self::TEMPLATE_PATH . $this->getTemplate() . ".html";
		if(file_exists($templatepath))
			$html = file_get_contents($templatepath);
		else 
			Application::Debug(DEBUG_MODE_DEEP, "Template " . $templatepath . " not found!");
		$this->replaceMarks($html);
		echo $html;
	}

	private function replaceMark(&$html, $mark, $value)
	{
		$html = substr($html, 0, $mark["start"]) . $value . substr($html, $mark["end"]);
	}

	private function findMark(&$html, $offset = 0)
	{
		$start = strpos($html, self::MARK_START, $offset);
		if($start === false)
			return null;
		$start_l = $start + strlen(self::MARK_START);
		$end_l = strpos($html, self::MARK_END, $start_l);
		if($end_l === false)
			return null;
		$end = $end_l + strlen(self::MARK_END);
		$variable = substr($html, $start_l, $end_l - $start_l);
		$mark = array(
			"variable" => $variable,
			"start" => $start,
			"end" => $end
		);
		return $mark;
	}

	private function replaceMarks(&$html)
	{
		$pos = 0;
		do
		{
			$mark = $this->findMark($html, $pos);
			if($mark != null)
			{
				$pos = $mark["start"];
				$variable = explode("::", $mark["variable"]);
				switch($variable[0])
				{
					case "Global":
						$this->replaceMark($html, $mark, constant("Application::".$variable[1]));
						break;

					case "Error":

						switch($variable[1])
						{
							case "Info":
								if(DEBUG_MODE != DEBUG_MODE_DISABLE)
								{
									$fc = FrontController::getFrontController();
									$errmsg = "";
									foreach($fc->getExceptions() as $ex)
									{
										$errmsg .= "<div class=\"exception\">" . $ex . "</div>";
									}
									$this->replaceMark($html, $mark, $errmsg);
								}else
									$this->replaceMark($html, $mark, "");
								break;

							default:
								$this->replaceMark($html, $mark, "");
						}
						break;

					case "FieldError":
						$fieldname = trim($variable[1]);
						$markrepl = "";
						if($fieldname != "")
							$markrepl = $this->getFieldErrors($fieldname);
						$this->replaceMark($html, $mark, $markrepl);
						break;

					case "View":
						$method = "get" . $variable[1];
						$this->replaceMark($html, $mark, $this->$method());
						break;

					case "Post":
						if(isset($_POST[$variable[1]]))
							$this->replaceMark($html, $mark, $_POST[$variable[1]]);
						else
							$this->replaceMark($html, $mark, "");
						break;

					case "Dict":
						$this->replaceMark($html, $mark, $this->getDictionary($variable[1]));
						break;

					case "Menu":
						$this->replaceMark($html, $mark, $this->getMenu($variable[1]));
						break;

					default:
						// Default case is a variable not managed, we shrink it
						$this->replaceMark($html, $mark, "");
				}
			}else
				break;
		}while($pos < strlen($html));
		return $html;
	}

	public function getMenu($menuid)
	{
		return $this->getMainMenu();
	}

	public function getMainMenu()
	{
		/*
		Riferimento:
		https://www.w3.org/WAI/tutorials/menus/
		*/

		$html = "<nav role=\"navigation\" id=\"mainmenu\" aria-labelledby=\"menu_heading\">
			<h2 id=\"menu_heading\" class=\"hideable\">Menu</h2>
			<ol>";

		if(AuthController::isAdmin())
		{
			$html .= $this->getMenuItemByController("Profilo", "profile", "index");
			$html .= $this->getMenuItemByController("Gestione categorie", "categories", "list");
			$html .= $this->getMenuItemByController("Gestione autori", "authors", "list");
			$html .= $this->getMenuItemByController("Gestione libri", "books", "list");
			$html .= $this->getMenuItemByController("Logout", "auth", "logout");
		}else
		{
			$html .= $this->getMenuItemByController("<span lang=\"en\">Home</span>", "index", "home");
			$html .= $this->getMenuItemByController("Chi siamo", "index", "chisiamo");
			$html .= $this->renderCategoriesMenu();
			if(AuthController::isLogged())
			{
				$html .= $this->getMenuItemByController("Carrello", "profile", "cart");
				$html .= $this->getMenuItemByController("Profilo", "profile", "index");
				$html .= $this->getMenuItemByController("I miei acquisti", "profile", "mylibrary");
				$html .= $this->getMenuItemByController("Logout", "auth", "logout");
			}else
			{
				$html .= $this->getMenuItemByController("Accedi", "auth", "login");
				$html .= $this->getMenuItemByController("Registrati", "auth", "register");
			}
		}

		$html .= "</ol> 
		</nav>";
		return $html;
	}

	function getMenuItemByController($label, $controller, $action, $params = null, $childs = "")
	{
		$currentpage = false;
		if(
			FrontController::getFrontController()->getControllerShort() == strtolower($controller)
			&& FrontController::getFrontController()->getActionShort() == strtolower($action)
			)
			$currentpage = true;

		$html = "<li";
		if($currentpage)
			$html .= " class=\"current_page\" aria-current=\"page\"";
		$html .= "><a href=\"" . FrontController::getUrl(strtolower($controller), strtolower($action), $params) . "\">".$label."</a>" . $childs . "</li>";
		return $html;
	}

	private function renderCategoriesMenu($parentcategory = null, $hiderootitem = false)
	{
		$html = "";
		if($parentcategory == null && !$hiderootitem)
			$html .= "<li><a href=\"" . FrontController::getUrl("categories", "list", null) . "\">Categorie</a>";
		$categories = FrontController::DbManager()->categoriesTree($parentcategory);
		if(count($categories)>0)
		{
			$html .= "<ul>";
			foreach($categories as $category)
			{
				$params = array();
				$params["id"] = $category->getId();
				$childs_categories = $this->renderCategoriesMenu($category->getId(), true);
				$html .= $this->getMenuItemByController($category->getName(), "books", "category", $params, $childs_categories);
			}
			$html .= "</ul>";
		}
		if($parentcategory == null && !$hiderootitem)
			$html .= "</li>";
		return $html;
	}
}