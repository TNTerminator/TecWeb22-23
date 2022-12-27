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
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		$page = new View();
		$page->setName("list");
		$page->setPath("books/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_list");

		$books = FrontController::DbManager()->booksList();

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione libri", null, null);

		$page->addDictionary("book_add", self::buttonBookAdd());

		if(count($books) == 0)
		{
			$html = "<tr><td colspan=\"5\">Nessuna scheda libro inserita al momento. Per aggiungere una nuova scheda, clicca su \"Aggiungi una nuova scheda libro\"</td><td>" . self::buttonBookAdd() . "</td></th>";
			$page->AddDictionary("TableContent", $html);
		}
		else
		{
			$html = "";
			foreach($books as $book)
			{
				$html .= "
				<tr>
					<td scope=\"row\" data-title=\"Titolo\">" . $book->getTitle() . "</td>
					<td scope=\"row\" data-title=\"Anno pubblicazione\">" . $book->getPubYear() . "</td>
					<td scope=\"row\" data-title=\"Editore\">" . $book->getEditor() . "</td>
					<td scope=\"row\" data-title=\"Prezzo\">" . "&euro; " . number_format($book->getPrice(), 2, ",", ".") . "</td>
					<td scope=\"row\" data-title=\"Quantità venduta\">" . $book->getSoldQuantity() . "</td>
					<td data-title=\"Operazioni\">
						<ul>
							<li>" . self::buttonBookEdit($book->getId(), $book->getTitle())  . "</li>
							<li>" . self::buttonBookDelete($book->getId(), $book->getTitle()) . "</li>
						</ul>
					</td>
				</tr>";
			}
			$page->AddDictionary("TableContent", $html);
		}

		$page->render();
	}

	public function viewAction($id)
	{
		$book = FrontController::DbManager()->bookSelect($id);
		if($book == null)
			return FrontController::redirect(FrontController::getUrl("index", "notfound"));
		$categories = $book->getIdCategories();
		$mothercategory = FrontController::DbManager()->categorySelect($categories[0]);
		$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());

		$page = new View();
		$page->setName("view");
		$page->setPath("books/view.html");
		$page->setTemplate("main");
		$page->setTitle("Scheda del libro " . $book->getTitle());
		$page->setId("books_view");

		$mcancestry = FrontController::DbManager()->categoryAncestry($mothercategory);
		$categories = FrontController::DbManager()->categoriesTree($mothercategory->getId());

		$page->addBreadcrumb("Categorie", FrontController::getUrl("categories", "index", null), null);
		for($i=0; $i<count($mcancestry); $i++)
		{
			$page->addBreadcrumb($mcancestry[$i]->getName(), FrontController::getUrl("categories", "list", array("parentid" => $mcancestry[$i]->getId()) ), null);
		}
		$page->addBreadcrumb($book->getTitle(), null, null);

		$page->addDictionary("BookTitle", $book->getTitle());
		$page->addDictionary("BookCoverPath", ""); // TODO

		$bookauthors = "";
		$f = true;
		foreach($authors as $author)
		{
			if($f)
			{
				$f = false;
			}else
			{
				$bookauthors .= "<br />";
			}
			$bookauthors .= $author->getName() . " " . $author->getSurname();
		}
		$page->addDictionary("BookAuthors", $bookauthors);
		$page->addDictionary("BookEditor", $book->getEditor());
		$page->addDictionary("BookPubYear", $book->getPubYear());
		$page->addDictionary("BookPrice", Application::priceToString($book->getPrice()));
		$page->addDictionary("BookSoldQuantity", $book->getSoldQuantity());

		if($book->getRatingCount() > 0)
			$rating = floatval($book->getRatingValue()) / floatval($book->getRatingCount());
		else
			$rating = 0;
		$page->addDictionary("BookRating", "<span class=\"val_" . Book::getRatingInt($rating) . "\">" . Book::getRatingText($rating) . "</span>");

		$page->addDictionary("BookDescription", $book->getDescription());
		$page->addDictionary("AddToCart", FrontController::getUrl("cart", "add", array("id"=>$book->getId())));

		$page->addDictionary("AuthorsInfo", "In costruzione");

		$page->render();
	}

	private function categoryTreeToList($tree, $level = 0)
	{
		$list = array();
		foreach($tree as $cat)
		{
			$list[$cat->getId()] = str_repeat("--", $level) . ($level > 0 ? "> " : "") . $cat->getName();
			$childs = $cat->getChilds();
			if($childs != null && count($childs) > 0)
				//$list = array_merge($list, $this->categoryTreeToList($childs, $level+1));
				array_push($list, ...$this->categoryTreeToList($childs, $level+1));
		}
		return $list;
	}

	public function addAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		$page = new View();
		$page->setName("add");
		$page->setPath("books/add.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_add");
		$page->setFormAction("/books/add/");

		// Form validation
		$errors = array();

		$authors = FrontController::DbManager()->authorsList();
		$categories = $this->categoryTreeToList(FrontController::DbManager()->categoriesTree());

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$title = Application::cleanInput($_POST["title"]);
			if($title == "")
			{
				$errors[] = array(
					"field" => "title",
					"message" => "Attenzione: Il Titolo del libro &egrave; obbligatorio."
				);
			}

			$idauthor = Application::cleanInput($_POST["idauthor"]);
			if(!is_array($idauthor))
				$idauthor = array($idauthor);
			if(count($idauthor)==0)
			{
				$errors[] = array(
					"field" => "idauthor",
					"message" => "Attenzione: &egrave; obbligatorio selezionare un autore."
				);
			}else
			{
				$authors_ok = false;
				foreach($idauthor as $ida)
				{
					$authors_ok = false;
					foreach($authors as $aut)
					{	if($ida == $aut->getId())
						{
							$authors_ok = true;
							break;
						}
					}
					if($authors_ok == false)
						break;
				}
				if($authors_ok == false)
				{
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, $e);
				}
			}

			$idcategory = Application::cleanInput($_POST["idcategory"]);
			if(!is_array($idcategory))
				$idcategory = array($idcategory);
			if(count($idcategory)==0)
			{
				$errors[] = array(
					"field" => "idcategory",
					"message" => "Attenzione: &egrave; obbligatorio selezionare una categoria."
				);
			}else
			{
				$cats_ok = false;
				foreach($idcategory as $idc)
				{
					$cats_ok = false;
					foreach($categories as $catid => $cat)
					{	if($idc == $catid)
						{
							$cats_ok = true;
							break;
						}
					}
					if($cats_ok == false)
						break;
				}
				if($cats_ok == false)
				{
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, $e);
				}
			}

			$pubyear = Application::cleanInput($_POST["pubyear"]);
			if($pubyear == "")
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione del libro &egrave; obbligatorio."
				);
			}else if(intval($pubyear)<1700)
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione non è valido. Deve essere un numero intero maggiore o uguale all'anno 1700."
				);
			}

			$editor = Application::cleanInput($_POST["editor"]);
			if($editor == "")
			{
				$errors[] = array(
					"field" => "editor",
					"message" => "Attenzione: l'Editore del libro &egrave; obbligatorio."
				);
			}

			$price = Application::cleanInput($_POST["price"]);
			if($price == "")
			{
				$errors[] = array(
					"field" => "price",
					"message" => "Attenzione: il Prezzo del libro &egrave; obbligatorio."
				);
			}else
			{
				$price = Application::stringToFloat($price);
				if($price == null || $price == 0)
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non &egrave; valido. Deve essere un numero e deve essere maggiore di zero."
					);
				}else if(!is_numeric($price))
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non &egrave; un numero."
					);
				}else if($price<0)
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non pu&ograve; essere negativo; per cortesia inserire un prezzo maggiore o uguale a zero."
					);
				}else
				{					
					$_POST["price"] = $price;
				}
			}

			$shortdescription = Application::cleanInput($_POST["shortdescription"]);
			if($shortdescription == "")
			{
				$errors[] = array(
					"field" => "shortdescription",
					"message" => "Attenzione: la descrizione breve del libro &egrave; obbligatoria."
				);
			}else if(strlen($shortdescription) > 100)
			{
				$errors[] = array(
					"field" => "shortdescription",
					"message" => "Attenzione: la descrizione breve del libro non può superare i 100 caratteri."
				);
			}

			$description = Application::cleanInput($_POST["description"]);
			if($description == "")
			{
				$errors[] = array(
					"field" => "description",
					"message" => "Attenzione: la descrizione estesa del libro &egrave; obbligatoria."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$newbook = new Book();
				$newbook
					->setId(null)
					->setTitle($title)
					->setPubYear($pubyear)
					->setEditor($editor)
					->setPrice($price)
					->setShortDescription($shortdescription)
					->setDescription($description)
					->setRatingValue(0)
					->setRatingCount(0)
					->setSoldQuantity(0);
				foreach($idauthor as $ida)
					$newbook->addIdAuthor($ida);
				foreach($idcategory as $idc)
					$newbook->addIdCategory($idc);
				FrontController::DbManager()->bookSave($newbook);
				return FrontController::getFrontController()->redirect("/books/list/");
			}
		}

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione libri", "/books/list/", null);
		$page->addBreadcrumb("Aggiungi libro", null, null);

		$AuthorOptions = "";
		foreach($authors as $author)
		{
			$AuthorOptions .= "<option value=\"" . $author->getId() . "\"";
			if(isset($idauthor))
			{	if(in_array($author->getId(), $idauthor))
					$AuthorOptions .= " selected";
			}
			$AuthorOptions .= ">" . $author->getSurname() . ", " . $author->getName() . "</option>";
		}
		$page->addDictionary("AuthorOptions", $AuthorOptions);

		$CategoryOptions = "";
		foreach($categories as $idc => $category)
		{
			$CategoryOptions .= "<option value=\"" . $idc . "\"";
			if(isset($idcategory))
			{	if(in_array($idc, $idcategory))
					$CategoryOptions .= " selected";
			}
			$CategoryOptions .= ">" . $category . "</option>";
		}
		$page->addDictionary("CategoryOptions", $CategoryOptions);

		if(count($errors) > 0)
		{
			$page->addMessage(View::MSG_TYPE_ERROR, "Attenzione: ci sono uno o pi&ugrave; errori nel modulo; gli errori sono elencati in dettaglio vicino ai campi corrispondenti nel modulo.");
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		$page->render();
	}

	public function editAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		$page = new View();
		$page->setName("edit");
		$page->setPath("books/edit.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_edit");
		$page->setFormAction("/books/edit/id/" . $id . "/");

		// Form validation
		$errors = array();

		$book = FrontController::DbManager()->bookSelect($id);

		$authors = FrontController::DbManager()->authorsList();
		$categories = $this->categoryTreeToList(FrontController::DbManager()->categoriesTree());

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$title = Application::cleanInput($_POST["title"]);
			if($title == "")
			{
				$errors[] = array(
					"field" => "title",
					"message" => "Attenzione: Il Titolo del libro &egrave; obbligatorio."
				);
			}

			$idauthor = Application::cleanInput($_POST["idauthor"]);
			if(!is_array($idauthor))
				$idauthor = array($idauthor);
			if(count($idauthor)==0)
			{
				$errors[] = array(
					"field" => "idauthor",
					"message" => "Attenzione: &egrave; obbligatorio selezionare un autore."
				);
			}else
			{
				$authors_ok = false;
				foreach($idauthor as $ida)
				{
					$authors_ok = false;
					foreach($authors as $aut)
					{	if($ida == $aut->getId())
						{
							$authors_ok = true;
							break;
						}
					}
					if($authors_ok == false)
						break;
				}
				if($authors_ok == false)
				{
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, $e);
				}
			}

			$idcategory = Application::cleanInput($_POST["idcategory"]);
			if(!is_array($idcategory))
				$idcategory = array($idcategory);
			if(count($idcategory)==0)
			{
				$errors[] = array(
					"field" => "idcategory",
					"message" => "Attenzione: &egrave; obbligatorio selezionare una categoria."
				);
			}else
			{
				$cats_ok = false;
				foreach($idcategory as $idc)
				{
					$cats_ok = false;
					foreach($categories as $catid => $cat)
					{	if($idc == $catid)
						{
							$cats_ok = true;
							break;
						}
					}
					if($cats_ok == false)
						break;
				}
				if($cats_ok == false)
				{
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, $e);
				}
			}

			$pubyear = Application::cleanInput($_POST["pubyear"]);
			if($pubyear == "")
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione del libro &egrave; obbligatorio."
				);
			}else if(intval($pubyear)<1700)
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione non è valido. Deve essere un numero intero maggiore o uguale all'anno 1700."
				);
			}

			$editor = Application::cleanInput($_POST["editor"]);
			if($editor == "")
			{
				$errors[] = array(
					"field" => "editor",
					"message" => "Attenzione: l'Editore del libro &egrave; obbligatorio."
				);
			}

			$price = Application::cleanInput($_POST["price"]);
			if($price == "")
			{
				$errors[] = array(
					"field" => "price",
					"message" => "Attenzione: il Prezzo del libro &egrave; obbligatorio."
				);
			}else
			{
				$price = Application::stringToFloat($price);
				if($price == null || $price == 0)
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non &egrave; valido. Deve essere un numero e deve essere maggiore di zero."
					);
				}else if(!is_numeric($price))
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non &egrave; un numero."
					);
				}else if($price<0)
				{
					$errors[] = array(
						"field" => "price",
						"message" => "Attenzione: il Prezzo del libro non pu&ograve; essere negativo; per cortesia inserire un prezzo maggiore o uguale a zero."
					);
				}else
				{					
					$_POST["price"] = $price;
				}
			}

			$shortdescription = Application::cleanInput($_POST["shortdescription"]);
			if($shortdescription == "")
			{
				$errors[] = array(
					"field" => "shortdescription",
					"message" => "Attenzione: la descrizione breve del libro &egrave; obbligatoria."
				);
			}else if(strlen($shortdescription) > 100)
			{
				$errors[] = array(
					"field" => "shortdescription",
					"message" => "Attenzione: la descrizione breve del libro non può superare i 100 caratteri."
				);
			}

			$description = Application::cleanInput($_POST["description"]);
			if($description == "")
			{
				$errors[] = array(
					"field" => "description",
					"message" => "Attenzione: la descrizione estesa del libro &egrave; obbligatoria."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$book
					->setTitle($title)
					->setPubYear($pubyear)
					->setEditor($editor)
					->setPrice($price)
					->setShortDescription($shortdescription)
					->setDescription($description);
				$book->emptyIdAuthors();
				foreach($idauthor as $ida)
					$book->addIdAuthor($ida);
				$book->emptyIdCategories();
				foreach($idcategory as $idc)
					$book->addIdCategory($idc);
				FrontController::DbManager()->bookSave($book);
				return FrontController::getFrontController()->redirect("/books/list/");
			}
		}

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione libri", "/books/list/", null);
		$page->addBreadcrumb("Modifica libro " . $book->getTitle(), null, null);

		$AuthorOptions = "";
		foreach($authors as $author)
		{
			$AuthorOptions .= "<option value=\"" . $author->getId() . "\"";
			if(isset($idauthor))
			{	if(in_array($author->getId(), $idauthor))
					$AuthorOptions .= " selected";
			}else if($book->hasIdAuthor($author->getId()))
			{
				$AuthorOptions .= " selected";
			}
			$AuthorOptions .= ">" . $author->getSurname() . ", " . $author->getName() . "</option>";
		}
		$page->addDictionary("AuthorOptions", $AuthorOptions);

		$CategoryOptions = "";
		foreach($categories as $idc => $category)
		{
			$CategoryOptions .= "<option value=\"" . $idc . "\"";
			if(isset($idcategory))
			{	if(in_array($idc, $idcategory))
					$CategoryOptions .= " selected";
			}else if($book->hasIdCategory($idc))
			{
				$CategoryOptions .= " selected";
			}
			$CategoryOptions .= ">" . $category . "</option>";
		}
		$page->addDictionary("CategoryOptions", $CategoryOptions);

		$page->addDictionary("book_title", $book->getTitle());

		if(count($errors) > 0)
		{
			$page->addMessage(View::MSG_TYPE_ERROR, "Attenzione: ci sono uno o pi&ugrave; errori nel modulo; gli errori sono elencati in dettaglio vicino ai campi corrispondenti nel modulo.");
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		$page->addDictionary("title", (isset($_POST["title"]) ? $_POST["title"] : $book->getTitle()));
		$page->addDictionary("pubyear", (isset($_POST["pubyear"]) ? $_POST["pubyear"] : $book->getPubYear()));
		$page->addDictionary("editor", (isset($_POST["editor"]) ? $_POST["editor"] : $book->getEditor()));
		$page->addDictionary("price", (isset($_POST["price"]) ? $_POST["price"] : Application::floatToString($book->getPrice())));
		$page->addDictionary("shortdescription", (isset($_POST["shortdescription"]) ? $_POST["shortdescription"] : $book->getShortDescription()));
		$page->addDictionary("description", (isset($_POST["description"]) ? $_POST["description"] : $book->getDescription()));

		$page->addDictionary("backToListLink", FrontController::getUrl("books", "list", null));
		$page->render();
	}

	public function deleteAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		FrontController::DbManager()->bookDelete($id);
		return FrontController::getFrontController()->redirect("/books/list/");

	}

	public static function printBookSmallBox($book, $authors)
	{
		$html = "";
		$html .= "<article class=\"book book_thumbnail\">
	<h3>" . $book->getTitle() . "</h3>
	<figure>
		<figcaption>Immagine di copertina</figcaption>
		<img src=\"" . $book->getCover() . "\">
	</figure>
	<dl>
		<dt class=\"author\">Autore</dt>
			<dd class=\"author\">";
		$f = true;
		foreach($authors as $author)
		{
			if($f)
			{
				$f = false;
			}else
			{
				$html .= "<br />";
			}
			$html .= $author->getName() . " " . $author->getSurname();
		}
		$html .= "</dd>";
		$html .= "<dt class=\"editor\">Casa Editrice</dt>
			<dd class=\"editor\">" . $book->getEditor() . "</dd>
		<dt class=\"pubdate\">Anno pubblicazione</dt>
			<dd class=\"pubdate\">" . $book->getPubYear() . "</dd>
		<dt class=\"price\">Prezzo</dt>
			<dd class=\"price\">" . Application::priceToString($book->getPrice()) . " (IVA incl.)</dd>
		<dt class=\"rating\">Valutazione</dt>
			<dd class=\"rating\">";
		if($book->getRatingCount() > 0)
			$rating = floatval($book->getRatingValue()) / floatval($book->getRatingCount());
		else
			$rating = 0;
		$html .= "<span class=\"val_" . Book::getRatingInt($rating) . "\">" . Book::getRatingText($rating) . "</span>";
		
			/*<span class="val_5">Ottimo</span>
			<!--
				Numero di stelline visualizzato tramite CSS tramite class 
				val_1 => Pessimo
				val_2 => Brutto
				val_3 => Neutro
				val_4 => Bello
				val_5 => Ottimo
			--> */
		$html .= "</dd>
		</dl>
	<a role=\"button\" href=\"";
		$params = array(
			"id" => $book->getId()
		); 
		$html .= FrontController::getUrl("cart", "add", $params);
		$html .= "\">Aggiungi al carrello</a>
	<a role=\"button\" href=\"";
		$html .= FrontController::getUrl("books", "view", $params);
		$html .= "\">Maggiori informazioni</a>
	<!-- TODO questi due a button, li mettiamo dentro dei tag p? -->
</article>";
		return $html;
	}

	public static function buttonBookEdit($id, $label)
	{
		$btn_edit = "<a href=\"";
		$btn_edit .= FrontController::getUrl("books","edit",array("id"=>$id));
		$btn_edit .= "\" class=\"button button_edit\" aria-label=\"Modifica la scheda del libro " . $label . "\">Modifica</a>";
		return $btn_edit;
	}
	public static function buttonBookDelete($id, $label)
	{
		$btn_delete = "<a href=\"";
		$btn_delete .= FrontController::getUrl("books","delete",array("id"=>$id));
		$btn_delete .= "\" class=\"button button_delete\" aria-label=\"Elimina la scheda del libro " . $label . "\">Elimina</a>";
		return $btn_delete;
	}
	public static function buttonBookAdd()
	{
		$btn_add = "<a href=\"";
		$btn_add .= FrontController::getUrl("books","add",null);
		$btn_add .= "\" class=\"button button_add\">Aggiungi una nuova scheda libro</a>";
		return $btn_add;
	}
}