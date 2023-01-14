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
		$page = new View();
		$page->setName("index");
		$page->setPath("users/index.html");
		$page->setTemplate("main");
		$page->setTitle("Gestione degli utenti");
		$page->setId("home");
		$page->addBreadcrumb("Home", null, "lang=\"en\"");
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$page->render();
	}

	public function profileAction($id)
	{
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
		$page->addDictionary("ChangePassword", FrontController::getUrl("auth", "changepwd"));
		$page->addDictionary("ProfileLibrary", FrontController::getUrl("profile", "library"));

		$page->render();
	}

	public function listAction()
	{
		// TODO
	}

	public function viewAction($id)
	{
		// TODO
	}

	public function deleteAction()
	{
		// TODO
	}
}