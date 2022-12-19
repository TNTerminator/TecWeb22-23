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
	}

	public function listAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		$page = new View();
		$page->setName("list");
		$page->setPath("authors/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_list");

		$authors = FrontController::DbManager()->authorsList();

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione autori", null, null);

		$btn_edit = "<a href=\"/authors/edit/id/##-ID-##/\" class=\"button button_edit\" aria-label=\"Modifica la scheda dell'autore ##-Name-##\">Modifica</a>";
		$btn_delete = "<a href=\"/authors/delete/id/##-ID-##/\" class=\"button button_delete\" aria-label=\"Elimina la scheda dell'autore ##-Name-##\">Elimina</a>";
		$btn_add = "<a href=\"/authors/add/\" class=\"button button_add\">Aggiungi una nuova scheda autore</a>";

		$page->addDictionary("author_add", $btn_add);

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
							<li>" . str_replace("##-Name-##", $author->getName() . " " . $author->getSurname(), str_replace("##-ID-##", $author->getId(), $btn_edit)) . "</li>
							<li>" . str_replace("##-Name-##", $author->getName() . " " . $author->getSurname(), str_replace("##-ID-##", $author->getId(), $btn_delete)) . "</li>
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

	public function addAction()
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		$page = new View();
		$page->setName("add");
		$page->setPath("authors/add.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_add");
		$page->setFormAction("/authors/add/");

		// Form validation
		$errors = array();

		$languages = FrontController::DbManager()->languageList();
		$codmothertongue = "";

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
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
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
					"field" => "name",
					"message" => "Attenzione: Il Cognome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $surname))
			{
				$errors[] = array(
					"field" => "name",
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
				FrontController::DbManager()->authorSave($newauthor);
				return FrontController::getFrontController()->redirect("/authors/list/");
			}
		}

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione autori", "/authors/list/", null);
		$page->addBreadcrumb("Aggiungi autore", null, null);

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
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		$page = new View();
		$page->setName("edit");
		$page->setPath("authors/edit.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Autori");
		$page->setId("admin_authors_edit");
		$page->setFormAction("/authors/edit/");

		// Form validation
		$errors = array();

		$author = FrontController::DbManager()->authorSelect($id);
		$languages = FrontController::DbManager()->languageList();
		$codmothertongue = $author->getCodMotherTongue();

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
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
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
					"field" => "name",
					"message" => "Attenzione: Il Cognome dell'autore &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $surname))
			{
				$errors[] = array(
					"field" => "name",
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
				FrontController::DbManager()->authorSave($author);
				return FrontController::getFrontController()->redirect("/authors/list/");
			}
		}

		$page->addBreadcrumb("Amministrazione sito", "/admin/index/", null);
		$page->addBreadcrumb("Gestione autori", "/authors/list/", null);
		$page->addBreadcrumb("Modifica autore " . $author->getName() . " " . $author->getSurname(), null, null);

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

	public function deleteAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		FrontController::DbManager()->authorDelete($id);
		return FrontController::getFrontController()->redirect("/authors/list/");

	}
}