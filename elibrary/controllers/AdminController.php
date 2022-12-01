<?php
/**
 * AdminController.php
 * 
 * The AdminController is the frontend of the administration panel.
 * 
 */

class AdminController
{
	public function indexAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized");

		$page = new View();
		$page->setName("admin");
		$page->setPath("admin/index.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito");
		$page->setId("admin_index");
		$page->addBreadcrumb("Amministrazione sito", null, null);
		$page->render();
	}

	public function unauthorizedAction()
	{
		$page = new View();
		$page->setName("unauthorized");
		$page->setPath("admin/unauthorized.html");
		$page->setTemplate("main");
		$page->setTitle("Accesso non autorizzato");
		$page->setId("unauthorized");
		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		$page->addBreadcrumb("Accesso non autorizzato", null);
		$page->render();
	}
}