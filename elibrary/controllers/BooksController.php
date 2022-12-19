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

		$btn_edit = "<a href=\"/books/edit/id/##-ID-##/\" class=\"button button_edit\" aria-label=\"Modifica la scheda del libro ##-Title-##\">Modifica</a>";
		$btn_delete = "<a href=\"/books/delete/id/##-ID-##/\" class=\"button button_delete\" aria-label=\"Elimina la scheda del libro ##-Title-##\">Elimina</a>";
		$btn_add = "<a href=\"/books/add/\" class=\"button button_add\">Aggiungi una nuova scheda libro</a>";

		$page->addDictionary("book_add", $btn_add);

		if(count($books) == 0)
		{
			$html = "<tr><td colspan=\"5\">Nessuna scheda libro inserita al momento. Per aggiungere una nuova scheda, clicca su \"Aggiungi una nuova scheda libro\"</td><td>" . $btn_add . "</td></th>";
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
							<li>" . str_replace("##-Title-##", $book->getTitle(), str_replace("##-ID-##", $book->getId(), $btn_edit)) . "</li>
							<li>" . str_replace("##-Title-##", $book->getTitle(), str_replace("##-ID-##", $book->getId(), $btn_delete)) . "</li>
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
}