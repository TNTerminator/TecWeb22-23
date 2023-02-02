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
		$page->addDictionary("ProfileLibrary", FrontController::getUrl("profile", "mylibrary"));

		$page->render();
	}

	public function mylibraryAction()
	{
		return $this->libraryAction();
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
		$html = "";
		$orders = FrontController::DbManager()->ordersList($user->getId());

		foreach($orders as $order)
		{
			$html .= "<section>";
			$html .= "<h3>Ordine nr. " . $order->getId() . " del <time datetime=\"" . $order->getTsCreate()->format("Y-m-d")  . "\">" . $order->getTsCreate()->format("d/m/Y") . "</time></h3>";
			$details = $order->getDetails();
			foreach($details as $detail)
			{
				$book = FrontController::DbManager()->bookSelect($detail["idbook"]);
				$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
				$html .= BooksController::printBookSmallBox($book, $authors, false);
			}
			$html .= "</section>";
		}

		$page->addDictionary("MyLibrary", $html);

		$page->render();
	}
}
