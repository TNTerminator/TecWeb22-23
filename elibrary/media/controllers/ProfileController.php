<?php
/**
 * ProfileController.php
 * 
 * The ProfileController manage the current user profile and info.
 * 
 */

class ProfileController
{
	public function indexAction()
	{
		$page = new View();
		$page->setName("profile");
		$page->setPath("profile/index.html");
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

	public function libraryAction()
	{
		$page = new View();
		$page->setName("library");
		$page->setPath("profile/library.html");
		$page->setTemplate("main");
		$page->setTitle("La tua libreria degli acquisti");
		$page->setId("library");
		$page->addBreadcrumb("Profilo utente", FrontController::getUrl("profile", "index"), null);
		$page->addBreadcrumb("Libreria degli acquisti", null, null);
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
}