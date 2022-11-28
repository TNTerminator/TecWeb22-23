<?php
/**
 * ErrorController.php
 * 
 * The ErrorController is the error management controller.
 * 
 */

class ErrorController
{
	public function indexAction()
	{
		return $this->generalAction();
	}

	public function generalAction()
	{
		$page = new View();
		$page->setName("error");
		$page->setPath("error.html");
		$page->setTemplate("main");
		$page->setTitle("Errore generale");
		$page->setId("error");
		$page->render();
	}
}