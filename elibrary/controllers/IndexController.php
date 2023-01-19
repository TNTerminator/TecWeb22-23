<?php
/**
 * IndexController.php
 * 
 * The IndexController manages the home page and the simple content pages of the project.
 * 
 */

class IndexController
{
	public function indexAction()
	{
		$thumbWidth = 300;
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

		$page->addDictionary("books_latest", self::printBooksLatest(6));
		$page->addDictionary("books_bestseller", self::printBooksBestSeller(5));
		$page->addDictionary("books_toprating", self::printBooksTopRating(4));

		$page->render();
	}

	public function homeAction()
	{
		$this->indexAction();
	}

	public function chisiamoAction()
	{
		$page = new View();
		$page->setName("chisiamo");
		$page->setPath("index/chisiamo.html");
		$page->setTemplate("main");
		$page->setTitle("Chi siamo");
		$page->setId("chisiamo");
		$page->addBreadcrumb("Home", "/index/home/", "lang=\"en\"");
		$page->addBreadcrumb("Chi siamo", null);
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$page->render();
	}

	public function notfoundAction()
	{
		$page = new View();
		$page->setName("notfound");
		$page->setPath("notfound.html");
		$page->setTemplate("main");
		$page->setTitle("Pagina non trovata");
		$page->setId("notfound");
		$page->addBreadcrumb("Pagina non trovata", null);
		$page->render();
	}

	public function privacyAction()
	{
		$page = new View();
		$page->setName("privacy");
		$page->setPath("index/privacy.html");
		$page->setTemplate("main");
		$page->setTitle("Informativa sul trattamento dei dati personali");
		$page->setId("privacy");
		$page->addBreadcrumb("Home", "/home/", "lang=\"en\"");
		$page->addBreadcrumb("Informativa sul trattamento dei dati personali", null);
		$page->render();
	}

	public static function printBooksLatest($num = 6)
	{
		$books = FrontController::DbManager()->getBooksLatest($num);
		$html = "";
		foreach($books as $book)
		{
			$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
			$html .= BooksController::printBookSmallBox($book, $authors);
		}
		return $html;
	}

	public static function printBooksBestSeller($num = 6)
	{
		$books = FrontController::DbManager()->getBooksBestSeller($num);
		$html = "";
		foreach($books as $book)
		{
			$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
			$html .= BooksController::printBookSmallBox($book, $authors);
		}
		return $html;
	}

	public static function printBooksTopRating($num = 6)
	{
		$books = FrontController::DbManager()->getBooksTopRating($num);
		$html = "";
		foreach($books as $book)
		{
			$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
			$html .= BooksController::printBookSmallBox($book, $authors);
		}
		return $html;
	}
}