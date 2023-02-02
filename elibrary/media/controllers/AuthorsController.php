<?php
/**
 * AuthorsController.php
 * 
 * The AuthorsController manages the authors data cards.
 * 
 */

class AuthorsController
{
	public function indexAction()
	{
		$page = new View();
		$page->setName("index");
		$page->setPath("authors/index.html");
		$page->setTemplate("main");
		$page->setTitle("Autori");
		$page->setId("authors_index");

		$authors = FrontController::DbManager()->authorsList();

		$page->addBreadcrumb("Autori", null, null);

		if(count($authors) == 0)
		{
			$page->AddDictionary("AuthorsList", "Nessun autore a sistema."); // TODO mettere un errore più carino
		}
		else
		{
			$html = "";
			foreach($authors as $author)
			{
				$html .= self::printAuthorSmallBox($author);
			}
			$page->AddDictionary("AuthorsList", $html);
		}

		$page->render();
	}

	public function listAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		$page = new View();
		$page->setName("list");
		$page->setPath("authors/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_list");

		$authors = FrontController::DbManager()->authorsList();

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
		$page->addBreadcrumb("Gestione autori", null, null);

		$page->addDictionary("author_add", self::buttonAuthorAdd());

		if(count($authors) == 0)
		{
			$html = "<tr><td colspan=\"5\">Nessuna scheda autore inserita al momento. Per aggiungere una nuova scheda, clicca su \"Aggiungi una nuova scheda autore\"</td><td>" . $btn_add . "</td></th>";
			$page->AddDictionary("TableContent", $html);
		}
		else
		{
			$html = "";
			foreach($authors as $author)
			{
				$html .= "
				<tr>
					<td scope=\"row\" data-title=\"Cognome\">" . $author->getSurname() . "</td>
					<td scope=\"row\" data-title=\"Nome\">" . $author->getName() . "</td>
					<td scope=\"row\" data-title=\"Data di Nascita\">" . $author->getBirthDate()->format("Y-m-d") . "</td>
					<td scope=\"row\" data-title=\"Luogo di Nascita\">" . $author->getBirthplace() . "</td>
					<td scope=\"row\" data-title=\"Lingua madre\">" . $author->getMotherTongue() . "</td>
					<td data-title=\"Operazioni\">
						<ul>
							<li>" . self::buttonAuthorEdit($author->getId(), $author->getName() . " " . $author->getSurname()) . "</li>
							<li>" . self::buttonAuthorDelete($author->getId(), $author->getName() . " " . $author->getSurname()) . "</li>
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
		$author = FrontController::DbManager()->authorSelect($id);
		if($author == null)
			return FrontController::redirect(FrontController::getUrl("index", "notfound"));
		$books = FrontController::DbManager()->getBooksByAuthor($author->getId());
		$languages = FrontController::DbManager()->languageList();
		$mothertongue = $languages[$author->getCodMotherTongue()];

		$page = new View();
		$page->setName("view");
		$page->setPath("authors/view.html");
		$page->setTemplate("main");
		$page->setTitle("Scheda dell'autore " . $author->getName() . " " . $author->getSurname());
		$page->setId("authors_view");

		$page->addBreadcrumb("Autori", FrontController::getUrl("authors", "index", null), null);
		$page->addBreadcrumb($author->getName() . " " . $author->getSurname(), null, null);

		$page->addDictionary("AuthorName", $author->getName());
		$page->addDictionary("AuthorSurname", $author->getSurname());
		if($author->getPicture() != "")
			$page->addDictionary("AuthorPicture", FrontController::getAbsoluteUrl(Application::getThumbnail($author->getPicture(), 500, 675)));
		else
			$page->addDictionary("AuthorPicture", FrontController::getAbsoluteUrl(Application::getThumbnail("media/notfound.jpg", 500, 675)));
		$page->addDictionary("AuthorBirthDate", $author->getBirthDate()->format("d/m/Y"));
		$page->addDictionary("AuthorBirthPlace", $author->getBirthPlace());
		$page->addDictionary("AuthorMotherTongue", $mothertongue);
		$page->addDictionary("AuthorAdditionalInfo", $author->getAdditionalInfo());

		$books_list = "";
		foreach($books as $book)
		{
			$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
			$books_list .= BooksController::printBookSmallBox($book, $authors);
		}
		$page->addDictionary("BooksList", $books_list);

		$page->render();
	}

	public function addAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		$page = new View();
		$page->setName("add");
		$page->setPath("authors/add.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_add");
		$page->setFormAction(FrontController::getUrl("authors", "add", null));

		// Form validation
		$errors = array();

		$languages = FrontController::DbManager()->languageList();
		$codmothertongue = "";
		$picture_file = null;

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$name = Application::cleanInput($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			$surname = Application::cleanInput($_POST["surname"]);
			if($surname == "")
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il Cognome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $surname))
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			$birthdate_raw = Application::cleanInput($_POST["birthdate"]);
			if($birthdate_raw == "")
			{
				$errors[] = array(
					"field" => "birthdate",
					"message" => "La data di nascita &egrave; un campo obbligatorio."
				);
			}else
			{
				$birthdate = DateTime::createFromFormat("Y-m-d", $birthdate_raw);
				if ($birthdate === false)
				{
					$errors[] = array(
						"field" => "birthdate",
						"message" => "La data di nascita non &egrave; valida."
					);
				}else
				{
					$datemin = new DateTime();
					if($birthdate >= $datemin)
					{
						$errors[] = array(
							"field" => "birthdate",
							"message" => "La data di nascita &egrave; una data futura, quindi impossibile; per cortesia inserire una data di nascita nel passato."
						);
					}
				}
			}

			$birthplace = Application::cleanInput($_POST["birthplace"]);
			if($birthplace == "")
			{
				$errors[] = array(
					"field" => "birthplace",
					"message" => "Attenzione: Il Luogo di nascita dell'autore &egrave; obbligatorio."
				);
			}

			$codmothertongue = Application::cleanInput($_POST["codmothertongue"]);
			if($codmothertongue == "")
			{
				$errors[] = array(
					"field" => "codmothertongue",
					"message" => "Attenzione: La madre lingua dell'autore &egrave; obbligatoria."
				);
			}else if(!array_key_exists($codmothertongue, $languages))
			{
				$errors[] = array(
					"field" => "codmothertongue",
					"message" => "Attenzione: la lingua selezionata non è contemplata dall'elenco."
				);
			}

			$additionalinfo = Application::cleanInput($_POST["additionalinfo"]);
			if($additionalinfo == "")
			{
				$errors[] = array(
					"field" => "additionalinfo",
					"message" => "Attenzione: Le informazioni sull'autore sono obbligatorie."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$newauthor = new Author();
				$newauthor
					->setId(null)
					->setSurname($surname)
					->setName($name)
					->setBirthDate($birthdate)
					->setBirthPlace($birthplace)
					->setCodMotherTongue($codmothertongue)
					->setMotherTongue($languages[$codmothertongue])
					->setAdditionalInfo($additionalinfo);
				$result = FrontController::DbManager()->authorSave($newauthor);

				if($result)
				{
					if(isset($_FILES["picture"]) && $_FILES["picture"]["name"]!="")
					{
						if(!Application::checkUpload($_FILES["picture"]["name"]))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "picture",
								"message" => "Il formato di immagine caricato non è valido: deve essere GIF, PNG oppure JPEG."
							);
						}

						$target_dir = "media/author/" . $newauthore->getId();
						if(!Application::prepareFolder($target_dir))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "picture",
								"message" => "Problema con l'upload del file, cartella non trovata, prego riprovare."
							);
						}else
						{
							if (!Application::moveUploadedFile($_FILES["picture"]["tmp_name"], $target_dir, $_FILES["picture"]["name"], true)) 
							{
								$uploaderror = true;
								$errors[] = array(
									"field" => "picture",
									"message" => "Problema con l'upload del file, prego riprovare."
								);
							}else
								$picture_file = $target_dir . "/" . $_FILES["picture"]["name"];
						} 
					}
					$newauthor->setPicture($picture_file);
					FrontController::DbManager()->authoreSave($newauthor);
					return FrontController::getFrontController()->redirect(FrontController::getUrl("authors", "list", null));
				}

			}
		}

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
		$page->addBreadcrumb("Gestione autori", FrontController::getUrl("authors", "list", null), null);
		$page->addBreadcrumb("Aggiungi autore", null, null);

		$page->addDictionary("name", (isset($name) ? $name : ""));
		$page->addDictionary("surname", (isset($surname) ? $surname : ""));
		$page->addDictionary("birthdate", (isset($birthdate_raw) ? $birthdate_raw : ""));
		$page->addDictionary("birthplace", (isset($birthplace) ? $birthplace : ""));
		$page->addDictionary("additionalinfo", (isset($additionalinfo) ? $additionalinfo : ""));

		$MotherTongueOptions = "";
		foreach($languages as $codlang => $lang)
		{
			$MotherTongueOptions .= "<option value=\"" . $codlang . "\"";
			if($codlang == $codmothertongue)
				$MotherTongueOptions .= " selected";
			$MotherTongueOptions .= ">" . $lang . "</option>";
		}
		$page->addDictionary("MotherTongueOptions", $MotherTongueOptions);

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
		$page->setPath("authors/edit.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_edit");
		$page->setFormAction(FrontController::getUrl("authors", "edit", array("id"=>$id)));

		// Form validation
		$errors = array();

		$author = FrontController::DbManager()->authorSelect($id);
		$languages = FrontController::DbManager()->languageList();
		$codmothertongue = $author->getCodMotherTongue();

		$picture_file = $author->getPicture();

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$name = Application::cleanInput($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			$surname = Application::cleanInput($_POST["surname"]);
			if($surname == "")
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il Cognome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $surname))
			{
				$errors[] = array(
					"field" => "surname",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			$birthdate_raw = Application::cleanInput($_POST["birthdate"]);
			if($birthdate_raw == "")
			{
				$errors[] = array(
					"field" => "birthdate",
					"message" => "Il campo &egrave; obbligatorio."
				);
			}else
			{
				$birthdate = DateTime::createFromFormat("Y-m-d", $birthdate_raw);
				if ($birthdate === false)
				{
					$errors[] = array(
						"field" => "birthdate",
						"message" => "La data dell'evento non &egrave; valida."
					);
				}else
				{
					$datemin = new DateTime();
					if($birthdate >= $datemin)
					{
						$errors[] = array(
							"field" => "birthdate",
							"message" => "La data di nascita &egrave; una data futura, quindi impossibile; per cortesia inserire una data di nascita nel passato."
						);
					}
				}
			}

			$birthplace = Application::cleanInput($_POST["birthplace"]);
			if($birthplace == "")
			{
				$errors[] = array(
					"field" => "birthplace",
					"message" => "Attenzione: Il Luogo di nascita dell'autore &egrave; obbligatorio."
				);
			}

			$codmothertongue = Application::cleanInput($_POST["codmothertongue"]);
			if($codmothertongue == "")
			{
				$errors[] = array(
					"field" => "codmothertongue",
					"message" => "Attenzione: La madre lingua dell'autore &egrave; obbligatoria."
				);
			}else if(!array_key_exists($codmothertongue, $languages))
			{
				$errors[] = array(
					"field" => "codmothertongue",
					"message" => "Attenzione: la lingua selezionata non è contemplata dall'elenco."
				);
			}

			$additionalinfo = Application::cleanInput($_POST["additionalinfo"]);
			if($additionalinfo == "")
			{
				$errors[] = array(
					"field" => "additionalinfo",
					"message" => "Attenzione: Le informazioni sull'autore sono obbligatorie."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$author
					->setSurname($surname)
					->setName($name)
					->setBirthDate($birthdate)
					->setBirthPlace($birthplace)
					->setCodMotherTongue($codmothertongue)
					->setMotherTongue($languages[$codmothertongue])
					->setAdditionalInfo($additionalinfo);
				$result = FrontController::DbManager()->authorSave($author);

				if($result)
				{
					if(isset($_FILES["picture"]) && $_FILES["picture"]["name"]!="")
					{
						if(!Application::checkUpload($_FILES["picture"]["name"]))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "picture",
								"message" => "Il formato di immagine caricato non è valido: deve essere GIF, PNG oppure JPEG."
							);
						}

						$target_dir = "media/authors/" . $author->getId();
						if(!Application::prepareFolder($target_dir))
						{
							$uploaderror = true;
							$errors[] = array(
								"field" => "picture",
								"message" => "Problema con l'upload del file, cartella non trovata, prego riprovare."
							);
						}else
						{
							if (!Application::moveUploadedFile($_FILES["picture"]["tmp_name"], $target_dir, $_FILES["picture"]["name"], true)) 
							{
								$uploaderror = true;
								$errors[] = array(
									"field" => "picture",
									"message" => "Problema con l'upload del file, prego riprovare."
								);
							}else
								$picture_file = $target_dir . "/" . $_FILES["picture"]["name"];
						} 
					}
					$author->setPicture($picture_file);
					FrontController::DbManager()->authorSave($author);
					return FrontController::getFrontController()->redirect(FrontController::getUrl("authors", "list", null));
				}
			}
		}

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin", "index", null), null);
		$page->addBreadcrumb("Gestione autori", FrontController::getUrl("authors", "list", null), null);
		$page->addBreadcrumb("Modifica autore " . $author->getName() . " " . $author->getSurname(), null, null);

		$page->addDictionary("name", (isset($name) ? $name : $author->getName()));
		$page->addDictionary("surname", (isset($surname) ? $surname : $author->getSurname()));
		$page->addDictionary("birthdate", (isset($birthdate_raw) ? $birthdate_raw : $author->getBirthDate()->format("Y-m-d")));
		$page->addDictionary("birthplace", (isset($birthplace) ? $birthplace : $author->getBirthPlace()));
		$page->addDictionary("additionalinfo", (isset($additionalinfo) ? $additionalinfo : $author->getAdditionalInfo()));

		$MotherTongueOptions = "";
		foreach($languages as $codlang => $lang)
		{
			$MotherTongueOptions .= "<option value=\"" . $codlang . "\"";
			if($codlang == $codmothertongue)
				$MotherTongueOptions .= " selected";
			$MotherTongueOptions .= ">" . $lang . "</option>";
		}
		$page->addDictionary("MotherTongueOptions", $MotherTongueOptions);

		if(count($errors) > 0)
		{
			$page->addMessage(View::MSG_TYPE_ERROR, "Attenzione: ci sono uno o pi&ugrave; errori nel modulo; gli errori sono elencati in dettaglio vicino ai campi corrispondenti nel modulo.");
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		if($picture_file != "")
		{
			$html = "<figure><img src=\"" . FrontController::getAbsoluteUrl(Application::getThumbnail($picture_file, 300, 405)) . "\" alt=\"Anteprima dell'immagine caricata\">";
			$html .= "<figcaption>Anteprima dell'immagine caricata</figcaption>";
			$html .= "</figure>";
			$page->addDictionary("picture_preview", $html);
		}

		$page->render();
	}

	public function deleteAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin", "unauthorized", null));

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect(FrontController::getUrl("error", "general", null));

		FrontController::DbManager()->authorDelete($id);
		return FrontController::getFrontController()->redirect(FrontController::getUrl("authors", "list", null));

	}

	public static function printAuthorSmallBox($author)
	{
		$html = "";
		$html .= "<article class=\"author author_thumbnail\">
	<h3>" . $author->getName() . " " . $author->getSurname() . "</h3>
	<figure>
		<figcaption>Fotografia dell'autore " . $author->getName() . " " . $author->getSurname() . "</figcaption>
		<img src=\"";
		
		if($author->getPicture() != "")
			$html .= FrontController::getAbsoluteUrl(Application::getThumbnail($author->getPicture(), 200, 270));
		else
			$html .= FrontController::getAbsoluteUrl(Application::getThumbnail("media/notfound.jpg", 200, 270));
		$html .= "\" alt=\"" . $author->getName() . " " . $author->getSurname() . "\">
	</figure>
	<dl>
		<dt class=\"name\">Nome</dt>
			<dd class=\"name\">" . $author->getName() . " " . $author->getSurname() . "</dd>
		<dt class=\"birthdate\">Data di nascita</dt>
			<dd class=\"birthdate\">" . $author->getBirthDate()->format("d/m/Y") . "</dd>
		<dt class=\"birthplace\">Luogo di nascita</dt>
			<dd class=\"birthplace\">" . $author->getBirthPlace() . "</dd>
		<dt class=\"mothertongue\">Madrelingua</dt>
			<dd class=\"mothertongue\">" . $author->getMotherTongue() . "</dd>
	</dl>
	<a role=\"button\" href=\"";
		$html .= FrontController::getUrl("authors", "view", array("id"=>$author->getId()));
		$html .= "\">Maggiori informazioni</a>
</article>";
		return $html;
	}

	public static function buttonAuthorEdit($id, $label)
	{
		$btn_edit = "<a href=\"";
		$btn_edit .= FrontController::getUrl("authors","edit",array("id"=>$id));
		$btn_edit .= "\" class=\"button button_edit\" aria-label=\"Modifica la scheda dell'autore " . $label . "\">Modifica</a>";
		return $btn_edit;
	}
	public static function buttonAuthorDelete($id, $label)
	{
		$btn_delete = "<a href=\"";
		$btn_delete .= FrontController::getUrl("authors","delete",array("id"=>$id));
		$btn_delete .= "\" class=\"button button_delete\" aria-label=\"Elimina la scheda dell'autore " . $label . "\">Elimina</a>";
		return $btn_delete;
	}
	public static function buttonAuthorAdd()
	{
		$btn_add = "<a href=\"";
		$btn_add .= FrontController::getUrl("authors","add",null);
		$btn_add .= "\" class=\"button button_add\">Aggiungi una nuova scheda autore</a>";
		return $btn_add;
	}
}