<?php
/**
 * BooksController.php
 * 
 * The BooksController manages the books.
 * 
 */

class BooksController
{
	public function indexAction()
	{
	}

	public function listAction()
	{
		$page = new View();
		$page->setName("list");
		$page->setPath("books/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_list");
		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		$page->addBreadcrumb("Gestione libri", null, null);

		$page->render();
	}
}