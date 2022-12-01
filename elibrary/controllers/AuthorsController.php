<?php
/**
 * AuthorsController.php
 * 
 * The AuthorsController manages the authors for the books.
 * 
 */

class AuthorsController
{
	public function indexAction()
	{
	}

	public function listAction()
	{
		$page = new View();
		$page->setName("list");
		$page->setPath("authors/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_list");
		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		$page->addBreadcrumb("Gestione autori", null, null);

		$page->render();
	}
}