<?php
/**
 * AuthController.php
 * 
 * The AuthController manages the authentication actions and the registration actions.
 * 
 */

class AuthController
{
	public static function isLogged()
	{
		return isset($_SESSION["LoggedUser"]) && $_SESSION["LoggedUser"] != null; // && $_SESSION["LoggedUser"] instanceof UserProfile;
	}

	public static function isAdmin()
	{
		if(AuthController::isLogged())
		{
			$user = unserialize($_SESSION["LoggedUser"]);
			if($user->getType() == User::TYPE_ADMIN)
				return true;
		}
		return false;
	}

	public function loginAction($username, $password)
	{
		// Check if user already logged in
		if(AuthController::isLogged())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("index", "home", null));

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Login"]))
		{
			$username = Application::cleanInput($_POST["username"]);
			if($username == "")
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Lo username non pu&ograve; essere vuoto."
				);
			}

			$password = Application::cleanInput($_POST["password"]);
			if($password == "")
			{
				$errors[] = array(
					"field" => "password",
					"message" => "La password &egrave; necessaria."
				);
			}

			if(count($errors) == 0)
			{
				$user = FrontController::DbManager()->getUserByLogin($username, $password);
				if($user != null)
				{
					$_SESSION["LoggedUser"] = serialize($user);
					FrontController::DbManager()->userUpdateLastLogin($user);
					// TODO return FrontController::getFrontController()->redirect("/home/");
					return FrontController::getFrontController()->redirect(FrontController::getUrl("users", "profile", array("id"=>$user->getId())));
				}else
				{
					$errors[] = array(
						"field" => "username",
						"message" => "Username o password errati."
					);
				}
			}
		}

		$page = new View();
		$page->setName("login");
		$page->setPath("auth/login.html");
		$page->setTemplate("main");
		$page->setTitle("Autenticazione dell'utente");
		$page->setId("login");
		$page->setFormAction(FrontController::getUrl("auth", "login", null));
		$page->addBreadcrumb("Home", FrontController::getUrl("index", "home", null), "lang=\"en\"");
		$page->addBreadcrumb("Accedi", null);

		if(count($errors) > 0)
		{
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}
		$page->render();
	}

	public function logoutAction()
	{
		session_unset();
		session_destroy();
		session_start();
		return FrontController::getFrontController()->redirect(FrontController::getUrl("index", "home", null));
	}

	public function registerAction()
	{
		// First page load
		$page = new View();
		$page->setName("registration");
		$page->setPath("auth/registration.html");
		$page->setTemplate("main");
		$page->setTitle("Registra il tuo account " . Application::PROJECT_TITLE);
		$page->setId("registration");
		$page->setFormAction(FrontController::getUrl("auth", "register", null));
		$page->addBreadcrumb("Home", FrontController::getUrl("index", "home", null), "lang=\"en\"");
		$page->addBreadcrumb("Registrazione nuovo account", null);

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Register"]))
		{
			// Postback management
			$username = Application::cleanInput($_POST["username"]);
			if($username == "")
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Attenzione: Lo username &egrave; obbligatorio."
				);
			}else if(strlen($username) < 5 || strlen($username) > 20)
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Attenzione: lo <span lang=\"en\">Username</span> non rispetta i requisiti di lunghezza."
				);
			}else if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9]+$/', $username))
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Attenzione: Lo username deve iniziare con una lettera e pu&ograve; contenere solamente caratteri alfanumerici."
				);
			}else if(FrontController::DbManager()->checkUsernameExists($username))
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Attenzione: Lo username inserito &egrave; gi&agrave; in uso; per cortesia, sceglierne uno differente."
				);
			}

			$email_raw = Application::cleanInput($_POST["email"]);
			if($email_raw == "")
			{
				$errors[] = array(
					"field" => "email",
					"message" => "Attenzione: L'email &egrave; obbligatoria."
				);
			}else
			{
				$email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
				if($email === false)
				{
					$errors[] = array(
						"field" => "email",
						"message" => "Attenzione: L'indirizzo email inserito non &egrave; un indirizzo email valido."
					);
				}else if(FrontController::DbManager()->checkUserEmailExists($email))
				{
					$errors[] = array(
						"field" => "email",
						"message" => "Attenzione: L'indirizzo email inserito &egrave; gi&agrave; associato ad un account. Impossibile utilizzarlo nuovamente. Per cortesia, inserire un altro indirizzo email; se ritieni che l'indirizzo email inserito sia corretto, prova a recuperare la password del tuo account cliccando su <a href=\"/auth/reset/\">Recupero password</a>."
					);
				}
			}
			
			$password = Application::cleanInput($_POST["password"]);
			if($password == "")
			{
				$errors[] = array(
					"field" => "password",
					"message" => "Attenzione: La password &egrave; obbligatoria."
				);
			}else if(strlen($password) < 5 || strlen($password) > 16)
			{
				$errors[] = array(
					"field" => "password",
					"message" => "Attenzione: la <span lang=\"en\">Password</span> non rispetta i criteri richiesti."
				);
			}

			$confermapassword = Application::cleanInput($_POST["confermapassword"]);
			if($confermapassword != $password)
			{
				$errors[] = array(
					"field" => "confermapassword",
					"message" => "Attenzione: la <span lang=\"en\">password</span> inserita come conferma non coincide con la <span lang=\"en\">password</span> precedente."
				);
			}

			$nome = Application::cleanInput($_POST["name"]);
			if($nome == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $nome))
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			$cognome = Application::cleanInput($_POST["surname"]);
			if($cognome == "")
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il cognome &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $cognome))
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il cognome deve contenere solamente lettere."
				);
			}

			$datanascita_raw = Application::cleanInput($_POST["birthdate"]);
			if($datanascita_raw == "")
			{
				$errors[] = array(
					"field" => "birthdate",
					"message" => "Attenzione: La data di nascita &egrave; obbligatoria."
				);
			}else
			{
				$datanascita = DateTime::createFromFormat("Y-m-d", $datanascita_raw);
				if ($datanascita === false)
				{
					$errors[] = array(
						"field" => "birthdate",
						"message" => "Attenzione: La data di nascita non &egrave; valida."
					);
				}else
				{
					$currentdate = new DateTime();
					if($datanascita > $currentdate)
					{
						$errors[] = array(
							"field" => "birthdate",
							"message" => "Attenzione: &Egrave; stata inserita una data futura come data di nascita, questo valore non è ammesso."
						);
					}else
					{
						$age = $currentdate->diff($datanascita);
						if($age->y < 18)
						{
							$errors[] = array(
								"field" => "birthdate",
								"message" => "Attenzione: &Egrave; necessario essere almeno maggiorenni; la data che hai inserito indica che non sei maggiorenne."
							);
						}else
						{
							$datanascita->setTime(0,0,0,0);
						}
					}
				}
			}

			$descrizione = Application::cleanInput($_POST["additionalinfo"]);
			if($descrizione == "")
			{
				$errors[] = array(
					"field" => "additionalinfo",
					"message" => "Attenzione: Il campo &egrave; obbligatorio."
				);
			}else if(strlen($descrizione) < 10)
			{
				$errors[] = array(
					"field" => "additionalinfo",
					"message" => "Attenzione: la descrizione inserita non rispetta i requisiti di lunghezza."
				);
			}

			if(!isset($_POST["privacy_agreement"]) || $_POST["privacy_agreement"] != "1")
			{
				$errors[] = array(
					"field" => "privacy_agreement",
					"message" => "Attenzione: &egrave; necessario leggere l'informativa sul trattamento dei dati personali e selezionare la relativa <span lang=\"en\">checkbox</span>."
				);
			}

			$consenso_marketing = 0;
			if(!isset($_POST["marketing_agreement"]))
			{
				$errors[] = array(
					"field" => "marketing_agreement",
					"message" => "Attenzione: Il campo &egrave; obbligatorio."
				);
			}
			if(isset($_POST["marketing_agreement"]) && intval($_POST["marketing_agreement"])==1)
				$consenso_marketing = 1;

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$newuser = new User();
				$newuser
					->setId(null)
					->setType(User::TYPE_USER)
					->setUsername($username)
					->setEmail($email)
					->setPassword(password_hash($password, PASSWORD_BCRYPT))
					->setName($nome)
					->setSurname($cognome)
					->setBirthDate($datanascita)
					->setAdditionalInfo($descrizione)
					->setPrivacy(1)
					->setMarketing($consenso_marketing);
				FrontController::DbManager()->userSave($newuser);
				$_SESSION["RegisteredUser"] = serialize($newuser);
				return $this->regsuccessAction();
			}
		}

		if(count($errors) > 0)
		{
			$page->addMessage(View::MSG_TYPE_ERROR, "Attenzione: ci sono uno o pi&ugrave; errori nel modulo; gli errori sono elencati in dettaglio vicino ai campi corrispondenti nel modulo.");
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		$page->render();
	}

	public function resetAction()
	{
		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Reset"]))
		{
			$username = Application::cleanInput($_POST["username"]);
			if($username == "")
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Lo username non pu&ograve; essere vuoto."
				);
			}

			$password = Application::cleanInput($_POST["password"]);
			if($password == "")
			{
				$errors[] = array(
					"field" => "password",
					"message" => "La password &egrave; necessaria."
				);
			}

			if(count($errors) == 0)
			{
				$user = FrontController::DbManager()->getUserByLogin($username, $password);
				if($user != null)
				{
					$_SESSION["LoggedUser"] = serialize($user);
					FrontController::DbManager()->userUpdateLastLogin($user);
					// TODO return FrontController::getFrontController()->redirect("/home/");
					return FrontController::getFrontController()->redirect(FrontController::getUrl("users", "profile", array("id"=>$user->getId())));
				}else
				{
					$errors[] = array(
						"field" => "username",
						"message" => "Username o password errati."
					);
				}
			}
		}

		$page = new View();
		$page->setName("reset");
		$page->setPath("auth/reset.html");
		$page->setTemplate("main");
		$page->setTitle("Resetta la password");
		$page->setId("reset");
		$page->setFormAction(FrontController::getUrl("auth", "reset", null));
		$page->addBreadcrumb("Profilo", FrontController::getUrl("profile", "index", null), null);
		$page->addBreadcrumb("Reset della password", null);

		if(count($errors) > 0)
		{
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}
		$page->render();
	}


	public function changepwdAction()
	{
		// Check if user already logged in
		if(AuthController::isLogged())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("index", "home", null));

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Reset"]))
		{
			$username = Application::cleanInput($_POST["username"]);
			if($username == "")
			{
				$errors[] = array(
					"field" => "username",
					"message" => "Lo username non pu&ograve; essere vuoto."
				);
			}

			$password = Application::cleanInput($_POST["password"]);
			if($password == "")
			{
				$errors[] = array(
					"field" => "password",
					"message" => "La password &egrave; necessaria."
				);
			}

			if(count($errors) == 0)
			{
				$user = FrontController::DbManager()->getUserByLogin($username, $password);
				if($user != null)
				{
					$_SESSION["LoggedUser"] = serialize($user);
					FrontController::DbManager()->userUpdateLastLogin($user);
					// TODO return FrontController::getFrontController()->redirect("/home/");
					return FrontController::getFrontController()->redirect(FrontController::getUrl("users", "profile", array("id"=>$user->getId())));
				}else
				{
					$errors[] = array(
						"field" => "username",
						"message" => "Username o password errati."
					);
				}
			}
		}

		$page = new View();
		$page->setName("reset");
		$page->setPath("auth/reset.html");
		$page->setTemplate("main");
		$page->setTitle("Resetta la password");
		$page->setId("reset");
		$page->setFormAction(FrontController::getUrl("auth", "reset", null));
		$page->addBreadcrumb("Profilo", FrontController::getUrl("profile", "index", null), null);
		$page->addBreadcrumb("Reset della password", null);

		if(count($errors) > 0)
		{
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}
		$page->render();
	}

	public function regsuccessAction()
	{
		$page = new View();
		$page->setName("registration_successfull");
		$page->setPath("auth/registration_successfull.html");
		$page->setTemplate("main");
		$page->setTitle("Account registrato con successo!");
		$page->setId("registration");
		$page->addBreadcrumb("Home", FrontController::getUrl("index", "home", null), "lang=\"en\"");
		$page->addBreadcrumb("Registrazione nuovo account", null);

		$page->addDictionary("username", unserialize($_SESSION["RegisteredUser"])->getUsername());
		$page->addDictionary("email", unserialize($_SESSION["RegisteredUser"])->getEmail());

		$page->render();
	}
}