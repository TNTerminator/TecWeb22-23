<?php
/**
 * CartController.php
 * 
 * The CartController manages the current visitor cart.
 * 
 */

class CartController
{
	public function indexAction()
	{
		$page = new View();
		$page->setName("cart");
		$page->setPath("cart/index.html");
		$page->setTemplate("main");
		$page->setTitle("Carrello degli acquisti");
		$page->setId("cart_index");

		$page->addBreadcrumb("Carrello", null, null);

		$cart_total = 0;
		$cart_count = count($_SESSION["Cart"]);
		$cart_tot_quantity = 0;
		$html = "";
		if($cart_count > 0)
		{
			foreach($_SESSION["Cart"] as $idbook => $quantity)
			{
				$book = FrontController::DbManager()->bookSelect($idbook);
				$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
				$html .= self::printCartRow($book, $authors, $quantity);
				$cart_total += $quantity * $book->getPrice();
				$cart_tot_quantity += $quantity;
			}
			$html .= "<div class=\"cart_totals\"><p>Il totale del tuo carrello è di &euro; " . number_format($cart_total, 2, ",", ".") . " e hai " . $cart_count . " differenti libri per una quantità totale di " . $cart_tot_quantity . ".</p></div>";
			$page->addDictionary("complete_order", "<div class=\"cart_completeorder\"><a href=\"" . FrontController::getUrl("cart","makeOrder") . "\" role=\"button\">Completa l'acquisto</a></div>");
		}else
		{
			$html = "<p>Nessun elemento nel carrello degli acquisti.</p>";
		}

		$page->addDictionary("CartList", $html);
		if(count($_SESSION["Cart"]) > 0)
			$page->addDictionary("emptyLink", "<p><a href=\"" . FrontController::getUrl("cart", "empty") . "\">Svuota tutto il carrello</a></p>");
		
		$page->render();
	}

	public function addAction($id)
	{
		if(isset($_SESSION["Cart"][$id]))
			$_SESSION["Cart"][$id]++;
		else
			$_SESSION["Cart"][$id] = 1;
		return FrontController::getFrontController()->redirect(FrontController::getUrl("cart", "index", null));
	}

	public function removeAction($id)
	{
		unset($_SESSION["Cart"][$id]);
		return FrontController::getFrontController()->redirect(FrontController::getUrl("cart", "index", null));
	}

	public function updateAction($id)
	{
		$quantity = Application::cleanInput($_POST["quantity"]);
		$_SESSION["Cart"][$id] = $quantity;
		return FrontController::getFrontController()->redirect(FrontController::getUrl("cart", "index", null));
	}

	public function emptyAction()
	{
		unset($_SESSION["Cart"]);
		$_SESSION["Cart"] = array();
		return FrontController::getFrontController()->redirect(FrontController::getUrl("cart", "index", null));
	}

	public function makeorderAction()
	{
		$page = new View();
		$page->setName("order");
		$page->setPath("cart/makeorder.html");
		$page->setTemplate("main");
		$page->setTitle("Completa l'acquisto");
		$page->setId("cart_order");

		$page->addBreadcrumb("Carrello", FrontController::getUrl("cart", "index", null), null);
		$page->addBreadcrumb("Completa l'acquisto", null, null);

		$html = "";
		if(isset($_POST["CMD_Buy"]))
		{
			$cart_count = count($_SESSION["Cart"]);
			if($cart_count > 0)
			{
				$user = unserialize($_SESSION["LoggedUser"]);
				$order = new Order();
				$order->setIdUser($user->getId());
				foreach($_SESSION["Cart"] as $idbook => $quantity)
				{
					$order->addDetail($idbook, $quantity);
				}
				if(FrontController::DbManager()->orderInsert($order))
				{
					$_SESSION["Cart"] = array();
					$html .= "<p>Il tuo ordine è stato evaso; il numero d'ordine è <strong>" . $order->getId() . "</strong>.</p>";
					$html .= "<p>I tuoi acquisti sono ora visibili nella sezione \"I miei acquisti\".</p>";
					$page->addDictionary("go_to_mylibrary",
					"<p><a href=\"" . FrontController::getUrl("profile", "mylibrary") . "\">Vai alla sezione \"I miei acquisti\"</a></p>");
				}else
				{
					$html .= "<p>C'è stato un problema con la registrazione del tuo ordine, per favore riprova.</p>";
				}
			}else
			{
				$html .= "<p>Nessun elemento nel carrello degli acquisti, impossibile completare l'ordine.</p>";
			}
		}else
		{
			$html .= "<form action=\"\" method=\"post\"><label>Confermi di voler procedere all'acquisto del tuo carrello?</label>
			<input type=\"submit\" name=\"CMD_Buy\" value=\"Confermo e procedo all'acquisto\"></form>";
		}

		$page->addDictionary("OrderDetails", $html);
		$page->addDictionary("back_to_cart", FrontController::getUrl("cart", "index"));
		
		$page->render();
	}
	public static function printCartRow($book, $authors, $quantity)
	{
		$html = "<div class=\"cart_row\">";
		$html .= BooksController::printBookSmallBox($book, $authors, false);

		$html .= "<div class=\"cart_row_cmd\">
		<form class=\"cart_row_form\" method=\"post\" action=\"" . FrontController::getUrl("cart", "update", array("id"=>$book->getId())) . "\">
		<label for=\"quantity\">Quantit&agrave;:</label>
		<input type=\"text\" name=\"quantity\" id=\"quantity\" value=\"" . $quantity . "\">
		<input type=\"submit\" name=\"CMD_UpdateCart\" value=\"Aggiorna quantit&agrave;\">
		</form>
		<p><a href=\"" . FrontController::getUrl("cart", "remove", array("id"=>$book->getId())) . "\">Rimuovi tutta la riga dal carrello</a></p>
		</div>";

		$html .= "</div>";
		return $html;
	}
}