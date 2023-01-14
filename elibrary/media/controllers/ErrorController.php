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

		if(DEBUG_MODE != DEBUG_MODE_DISABLE)
		{
			$html = "";
			$exceptions = FrontController::getFrontController()->getExceptions();
			foreach($exceptions as $e)
			{
				$html .= "<section>";
				$html .= $e->getMessage();
				$html .= "<pre>";
				$html .= $e->getTraceAsString();
				$html .= "</pre></section>";
			}
			$page->addDictionary("Debug", $html);
		}

		$page->render();
	}
}