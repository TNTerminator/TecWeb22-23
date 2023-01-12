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
		$page = new View();
		$page->setName("books");
		$page->setPath("books/index.html");
		$page->setTemplate("main");
		$page->setTitle("Entra in libreria da dovunque nel mondo");
		$page->setId("books_index");
		$page->addBreadcrumb("Elenco libri", null, "");
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$books = FrontController::DbManager()->booksList();
		$html = "";
		foreach($books as $book)
		{
			$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
			$html .= BooksController::printBookSmallBox($book, $authors);
		}
		$page->addDictionary("BooksList", $html);

		$page->render();
	}

	public function searchAction()
	{
		$searchstring = Application::cleanInput($_POST["search_text"]);

		$page = new View();
		$page->setName("search");
		$page->setPath("books/search.html");
		$page->setTemplate("main");
		$page->setTitle("Entra in libreria da dovunque nel mondo");
		$page->setId("home");
		$page->addBreadcrumb("Elenco libri", FrontController::getUrl("books", "index", null), null);
		$page->addBreadcrumb("Risultati della ricerca", null, null);
		$page->setAuthors(Application::PROJECT_AUTHORS);
		$page->setKeywords(""); // TODO
		$page->setDescription(""); // TODO

		$books = FrontController::DbManager()->booksSearchFullText($searchstring);
		$html = "";
		if(count($books) > 0)
		{
			foreach($books as $book)
			{
				$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
				$html .= BooksController::printBookSmallBox($book, $authors);
			}
		}else
			$html .= "";
		$page->addDictionary("SearchString", $searchstring);
		$page->addDictionary("NumResults", count($books));
		$page->addDictionary("BooksList", $html);

		$page->render();
	}

	public function listAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		$page = new View();
		$page->setName("list");
		$page->setPath("books/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_list");

		$books = FrontController::DbManager()->booksList();

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
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
		if($book->getCover() != "")
		{
			$page->addDictionary("BookCoverPath", FrontController::getAbsoluteUrl(Application::getThumbnail($book->getCover(), 500, 675)));
			$page->addDictionary("BookCoverCaption", $book->getCoverCaption());
		}else
		{
			$page->addDictionary("BookCoverPath", FrontController::getAbsoluteUrl(Application::getThumbnail("media/notfound.jpg", 500, 675)));
			$page->addDictionary("BookCoverCaption", "Nessuna immagine di copertina");
		}

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

		$authors_info = "";
		foreach($authors as $author)
			$authors_info .= AuthorsController::printAuthorSmallBox($author);
		$page->addDictionary("AuthorsInfo", $authors_info);

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
			{
				$list2 = $this->categoryTreeToList($childs, $level+1);
				foreach($list2 as $idc => $ccc)
					$list[$idc] = $ccc;
			}
		}
		return $list;
	}

	public function addAction() 
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		$page = new View();
		$page->setName("add");
		$page->setPath("books/add.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_add");
		$page->setFormAction(FrontController::getUrl("books", "add", null));

		// Form validation
		$errors = array();

		$authors = FrontController::DbManager()->authorsList();
		$categories = $this->categoryTreeToList(FrontController::DbManager()->categoriesTree());

		$uploaderror = false;
		$cover_file = null;
		$covercaption = "";

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

			if(isset($_POST["idauthor"]))
				$idauthor = Application::cleanInput($_POST["idauthor"]);
			else
				$idauthor = array();
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
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, null);
				}
			}

			if(isset($_POST["idcategory"]))
				$idcategory = Application::cleanInput($_POST["idcategory"]);
			else
				$idcategory = array();
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
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, null);
				}
			}

			$pubyear = Application::cleanInput($_POST["pubyear"]);
			if($pubyear == "")
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione del libro &egrave; obbligatorio."
				);
			}else if(intval($pubyear)<1700 || intval($pubyear)>date("Y"))
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione non è valido. Deve essere un numero intero maggiore o uguale all'anno 1700 e minore o uguale all'anno corrente, " . date("Y") . "."
				);
			}else
			{
				$aut_fail = false;
				foreach($errors as $error)
					if($error["field"] == "idauthor")
					{
						$aut_fail = true;
						break;
					}
				if(!$aut_fail)
				{
					$aut_fail2 = false;
					foreach($idauthor as $ida)
					{
						$aaa = FrontController::DbManager()->authorSelect($ida);
						if($aaa != null && $aaa->getBirthDate()->format("Y")>=$pubyear)
						{
							$aut_fail2 = true;
							break;
						}
					}
					if($aut_fail2)
					{
						$errors[] = array(
							"field" => "pubyear",
							"message" => "Attenzione: l'Anno di pubblicazione è precedente alla data di nascita dell'autore."
						);
					}
				}
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
				$result = FrontController::DbManager()->bookSave($newbook);

				if($result)
				{
					if(isset($_FILES["cover"]) && $_FILES["cover"]["name"]!="")
					{
						if(!Application::checkUpload($_FILES["cover"]["name"]))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "cover",
								"message" => "Il formato di immagine caricato non è valido: deve essere GIF, PNG oppure JPEG."
							);
						}

						$target_dir = "media/books/" . $newbook->getId();
						if(!Application::prepareFolder($target_dir))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "cover",
								"message" => "Problema con l'upload del file, cartella non trovata, prego riprovare."
							);
						}else
						{
							if (!Application::moveUploadedFile($_FILES["cover"]["tmp_name"], $target_dir, $_FILES["cover"]["name"], true)) 
							{
								$uploaderror = true;
								$errors[] = array(
									"field" => "cover",
									"message" => "Problema con l'upload del file, prego riprovare."
								);
							}else
								$cover_file = $target_dir . "/" . $_FILES["cover"]["name"];
						} 
					}
					
					$covercaption = Application::cleanInput($_POST["covercaption"]);
					if($covercaption == "" && !$uploaderror)
					{
						$errors[] = array(
							"field" => "covercaption",
							"message" => "Attenzione: quando viene caricata una immagine di copertina, è obbligatorio inserire anche una didascalia di tale immagine."
						);
					}
					$newbook
						->setCover($cover_file)
						->setCoverCaption($covercaption);
					FrontController::DbManager()->bookSave($newbook);
					return FrontController::getFrontController()->redirect(FrontController::getUrl("books", "list", null));
				}
			}
		}

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
		$page->addBreadcrumb("Gestione libri", FrontController::getUrl("books", "list", null), null);
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

		$page->addDictionary("title", (isset($title) ? $title : ""));
		$page->addDictionary("covercaption", (isset($covercaption) ? $covercaption : ""));
		$page->addDictionary("pubyear", (isset($pubyear) ? $pubyear : ""));
		$page->addDictionary("editor", (isset($editor) ? $editor : ""));
		$page->addDictionary("price", (isset($price_raw) ? $price_raw : ""));
		$page->addDictionary("shortdescription", (isset($shortdescription) ? $shortdescription : ""));
		$page->addDictionary("description", (isset($description) ? $description : ""));

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
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect(FrontController::getUrl("error", "general", null));

		$page = new View();
		$page->setName("edit");
		$page->setPath("books/edit.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Libri");
		$page->setId("admin_books_edit");
		$page->setFormAction(FrontController::getUrl("books", "edit", array("id"=>$id)));

		// Form validation
		$errors = array();

		$book = FrontController::DbManager()->bookSelect($id);

		$authors = FrontController::DbManager()->authorsList();
		$categories = $this->categoryTreeToList(FrontController::DbManager()->categoriesTree());

		$uploaderror = false;
		$cover_file = $book->getCover();
		$covercaption = $book->getCoverCaption();

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
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, null);
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
					throw new GeneralException("Purtroppo c'è stato un errore che impatta sulla sicurezza del sito.", GeneralException::ERR_SECURITY, null);
				}
			}

			$pubyear = Application::cleanInput($_POST["pubyear"]);
			if($pubyear == "")
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione del libro &egrave; obbligatorio."
				);
			}else if(intval($pubyear)<1700 || intval($pubyear)>date("Y"))
			{
				$errors[] = array(
					"field" => "pubyear",
					"message" => "Attenzione: l'Anno di pubblicazione non è valido. Deve essere un numero intero maggiore o uguale all'anno 1700 e minore o uguale all'anno corrente, " . date("Y") . "."
				);
			}else
			{
				$aut_fail = false;
				foreach($errors as $error)
					if($error["field"] == "idauthor")
					{
						$aut_fail = true;
						break;
					}
				if(!$aut_fail)
				{
					$aut_fail2 = false;
					foreach($idauthor as $ida)
					{
						$aaa = FrontController::DbManager()->authorSelect($ida);
						if($aaa != null && $aaa->getBirthDate()->format("Y")>=$pubyear)
						{
							$aut_fail2 = true;
							break;
						}
					}
					if($aut_fail2)
					{
						$errors[] = array(
							"field" => "pubyear",
							"message" => "Attenzione: l'Anno di pubblicazione è precedente alla data di nascita dell'autore."
						);
					}
				}
			}

			$editor = Application::cleanInput($_POST["editor"]);
			if($editor == "")
			{
				$errors[] = array(
					"field" => "editor",
					"message" => "Attenzione: l'Editore del libro &egrave; obbligatorio."
				);
			}

			$price_raw = Application::cleanInput($_POST["price"]);
			if($price_raw == "")
			{
				$errors[] = array(
					"field" => "price",
					"message" => "Attenzione: il Prezzo del libro &egrave; obbligatorio."
				);
			}else
			{
				$price = Application::stringToFloat($price_raw);
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
				$result = FrontController::DbManager()->bookSave($book);

				if($result)
				{
					if(isset($_FILES["cover"]) && $_FILES["cover"]["name"]!="")
					{
						if(!Application::checkUpload($_FILES["cover"]["name"]))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "cover",
								"message" => "Il formato di immagine caricato non è valido: deve essere GIF, PNG oppure JPEG."
							);
						}

						$target_dir = "media/books/" . $book->getId();
						if(!Application::prepareFolder($target_dir))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "cover",
								"message" => "Problema con l'upload del file, cartella non trovata, prego riprovare."
							);
						}else
						{
							if (!Application::moveUploadedFile($_FILES["cover"]["tmp_name"], $target_dir, $_FILES["cover"]["name"], true)) 
							{
								$uploaderror = true;
								$errors[] = array(
									"field" => "cover",
									"message" => "Problema con l'upload del file, prego riprovare."
								);
							}else
								$cover_file = $target_dir . "/" . $_FILES["cover"]["name"];
						} 
					}
					
					$covercaption = Application::cleanInput($_POST["covercaption"]);
					if($covercaption == "" && !$uploaderror)
					{
						$errors[] = array(
							"field" => "covercaption",
							"message" => "Attenzione: quando viene caricata una immagine di copertina, è obbligatorio inserire anche una didascalia di tale immagine."
						);
					}
					$book
						->setCover($cover_file)
						->setCoverCaption($covercaption);
					FrontController::DbManager()->bookSave($book);
					return FrontController::getFrontController()->redirect(FrontController::getUrl("books", "list", null));
				}
			}
		}

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
		$page->addBreadcrumb("Gestione libri", FrontController::getUrl("books", "list", null), null);
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

		if($cover_file != "")
		{
			$html = "<figure><img src=\"" . FrontController::getAbsoluteUrl(Application::getThumbnail($cover_file, 300, 405)) . "\">";
			if($covercaption != "")
				$html .= "<figcaption>" . $covercaption . "</figcaption>";
			$html .= "</figure>";
			$page->addDictionary("cover_preview", $html);
		}

		$page->addDictionary("title", (isset($title) ? $title : $book->getTitle()));
		$page->addDictionary("covercaption", (isset($covercaption) ? $covercaption : $book->getCoverCaption()));
		$page->addDictionary("pubyear", (isset($pubyear) ? $pubyear : $book->getPubYear()));
		$page->addDictionary("editor", (isset($editor) ? $editor : $book->getEditor()));
		$page->addDictionary("price", (isset($price_raw) ? $price_raw : Application::floatToString($book->getPrice())));
		$page->addDictionary("shortdescription", (isset($shortdescription) ? $shortdescription : $book->getShortDescription()));
		$page->addDictionary("description", (isset($description) ? $description : $book->getDescription()));

		$page->addDictionary("backToListLink", FrontController::getUrl("books", "list", null));
		$page->render();
	}

	public function deleteAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect(FrontController::getUrl("error", "general", null));

		FrontController::DbManager()->bookDelete($id);
		return FrontController::getFrontController()->redirect(FrontController::getUrl("books", "list", null));

	}

	public static function printBookSmallBox($book, $authors, $addbtn = true)
	{
		$html = "";
		$html .= "<article class=\"book book_thumbnail\">
	<h3>" . $book->getTitle() . "</h3>
	<figure><figcaption>";
		if($book->getCover() != "")
			$html .= $book->getCoverCaption();
		else
			$html .= "Nessuna immagine di copertina";
		$html .= "</figcaption><img src=\"";
		if($book->getCover() != "")
			$html .= FrontController::getAbsoluteUrl(Application::getThumbnail($book->getCover(), 200, 270));
		else
			$html .= FrontController::getAbsoluteUrl(Application::getThumbnail("media/notfound.jpg", 200, 270));
		$html .= "\" alt=\"Immagine di copertina\"></figure>
	<dl>
		<dt class=\"author\">Autore</dt>
			<dd class=\"author\">";
		$f = true;
		foreach($authors as $author)
		{
			if($f)
				$f = false;
			else
				$html .= "<br />";
			$html .= "<a href=\"" . FrontController::getUrl("authors", "view", array("id"=>$author->getId())) . "\">" . $author->getName() . " " . $author->getSurname() . "</a>"; // TODO: verificare problemi breadcrumb
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
		</dl>";
		$params = array(
			"id" => $book->getId()
		); 
		if($addbtn)
		{
			$html .= "<a role=\"button\" href=\"";
			$html .= FrontController::getUrl("cart", "add", $params);
			$html .= "\">Aggiungi al carrello</a>";
		}
		$html .= "<a role=\"button\" href=\"";
		$html .= FrontController::getUrl("books", "view", $params);
		$html .= "\">Maggiori informazioni</a>
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