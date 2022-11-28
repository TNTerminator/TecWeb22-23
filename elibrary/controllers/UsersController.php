<?php
/**
 * UsersController.php
 * 
 * The UsersController manages the users-related pages and actions.
 * 
 */

class UsersController
{
	public function indexAction()
	{
		/*$thumbWidth = 300;
		$thumbHeight = 200;

		$page = new View();
		$page->setName("index");
		$page->setPath("index/index.html");
		$page->setTemplate("main");
		$page->setTitle("Entra in libreria da dovunque nel mondo");
		$page->setId("home");
		$page->addBreadcrumb("Home", null, "lang=\"en\"");
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$page->render();*/
	}

	public function profileAction($id)
	{
		$thumbWidth = 300;
		$thumbHeight = 200;

		$page = new View();
		$page->setName("profile");
		$page->setPath("users/profile.html");
		$page->setTemplate("main");
		$page->setTitle("Il tuo profilo utente su " . Application::PROJECT_TITLE);
		$page->setId("profile");
		$page->addBreadcrumb("Profilo utente", null, null); // TODO: vogliamo inserire il nome utente nel breadcrumbs?
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$user = unserialize($_SESSION["LoggedUser"]);

		$page->addDictionary("Username", $user->getUsername());
		$page->addDictionary("Email", $user->getEmail());
		$page->addDictionary("BirthDate", $user->getBirthDate()->format("d/m/y"));
		$page->addDictionary("PreferredPlace", $user->getAdditionalInfo());

		$page->render();
	}
}