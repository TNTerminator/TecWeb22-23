<?php
/**
 * CategoriesController.php
 * 
 * The CategoriesController manages the categories of books.
 * 
 */

class CategoriesController
{
	public function indexAction($parentid = null)
	{
		$page = new View();
		$page->setName("cat_list");
		$page->setPath("categories/index.html");
		$page->setTemplate("main");
		$page->setTitle("Categorie");
		$page->setId("categories_list");

		$mothercategory = FrontController::DbManager()->categorySelect($parentid);
		$mcancestry = FrontController::DbManager()->categoryAncestry($mothercategory);
		$categories = FrontController::DbManager()->categoriesTree($parentid);

		if($parentid == null)
			$page->addBreadcrumb("Categorie", null, null);
		else
		{
			$page->addBreadcrumb("Categorie", FrontController::getUrl("categories", "index", null), null);
			for($i=0; $i<count($mcancestry); $i++)
			{
				if($i<count($mcancestry)-1)
				{
					$page->addBreadcrumb($mcancestry[$i]->getName(), FrontController::getUrl("categories", "list", array("parentid" => $mcancestry[$i]->getId()) ), null);
				}
				else
					$page->addBreadcrumb($mcancestry[$i]->getName(), null, null);
			}
		}
		
		if($mothercategory != null)
		{
			$page->addDictionary("category_caption", "Categoria: " . $mothercategory->getName());
		}
		else
		{
			$page->addDictionary("category_caption", "Elenco categorie");
		}

		if($mothercategory != null)
		{
			$html = "";
			$params = array();
			$motherpid = $mothercategory->getIdParentCategory();
			if($motherpid != null)
				$params["parentid"] = $motherpid;
			$html .= "<p><a href=\"" . FrontController::getUrl("categories", "index", $params) . "\">Torna all'elenco categorie di livello superiore</a>.</p>";
			$page->AddDictionary("UpOneLevel", $html);
		}
		if(count($categories) == 0)
		{
			$page->AddDictionary("CategoriesList", "");
		}
		else
		{
			$html = "<section>";
			if($mothercategory != null)
				$html .= "<h3>Elenco delle sotto-categorie</h3>";
			else
				$html .= ""; //"<h3>Elenco delle categorie</h3>";
			$html .= "<ul>";
			foreach($categories as $category)
			{
				$html .= "<li><a href=\"" . FrontController::getUrl("categories", "index", array("parentid"=>$category->getId())) . "\">" . $category->getName() . "</a></li>";
			}
			$html .= "</ul>";
			$html .= "</section>";
			$page->AddDictionary("CategoriesList", $html);

			if($parentid == null)
			{
				$html = "";
				reset($categories);
				foreach($categories as $category)
				{
					$books = FrontController::DbManager()->getBooksByCategory($category->getId(), 3);
					if(count($books) > 0)
					{
						$html .= "<section>";
						$html .= "<h3>" . $category->getName() . "</h3>";
						foreach($books as $book)
						{
							$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
							$html .= BooksController::printBookSmallBox($book, $authors);
						}
						$html .= "<p><a href=\"" . FrontController::getUrl("categories", "list", array("parentid"=>$category->getId())) . "\">Mostra tutti i libri di questa categoria</a></p>";
						$html .= "</section>";
					}
				}
				$page->addDictionary("StartingCategoriesBooks", $html);
			}
		}

		if($mothercategory != null)
		{
			$books = FrontController::DbManager()->getBooksByCategory($parentid);
			$html = "<section>";
			$html .= "<h3>Libri presenti in questa categoria</h3>";
			if(count($books) > 0)
			{
				foreach($books as $book)
				{
					$authors = FrontController::DbManager()->getAuthorsByBook($book->getId());
					$html .= BooksController::printBookSmallBox($book, $authors);
				}
			}else
			{
				$html .= "<p>In questa categoria, al momento, non ci sono libri disponibili.</p>";
			}
			$html .= "</section>";
			$page->addDictionary("BooksList", $html);
		}
		else
		{
			$page->addDictionary("BooksList", "");
		}

		$page->render();
	}

	public function listAction($parentid = null)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin","unauthorized",null));

		$page = new View();
		$page->setName("list");
		$page->setPath("categories/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Categorie");
		$page->setId("admin_categories_list");

		$mothercategory = FrontController::DbManager()->categorySelect($parentid);
		$mcancestry = FrontController::DbManager()->categoryAncestry($mothercategory);
		$categories = FrontController::DbManager()->categoriesTree($parentid);

		$page->addBreadcrumb("Amministrazione sito", FrontController::getUrl("admin","index",null), null);
		if($mcancestry == null)
			$page->addBreadcrumb("Gestione categorie", null, null);
		else
		{
			$page->addBreadcrumb("Gestione categorie", FrontController::getUrl("categories","list",null), null);
			for($i=0; $i<count($mcancestry); $i++)
			{
				if($i<count($mcancestry)-1)
					$page->addBreadcrumb($mcancestry[$i]->getName(), FrontController::getUrl("categories","list",array("parentid"=>$mcancestry[$i]->getId())), null);
				else
					$page->addBreadcrumb($mcancestry[$i]->getName(), null, null);
			}
		}
		$btn_edit = "<a href=\"/categories/edit/id/##-ID-##/\" class=\"button button_edit\" aria-label=\"Modifica la categoria ##-Name-##\">Modifica</a>";
		$btn_delete = "<a href=\"/categories/delete/id/##-ID-##/\" class=\"button button_delete\" aria-label=\"Elimina la categoria ##-Name-##\">Elimina</a>";
		$btn_into = "<a href=\"/categories/list/parentid/##-ID-##/\" class=\"button button_into\" aria-label=\"Visualizza le sotto-categorie della categoria ##-Name-##\">Visualizza le sotto-categorie</a>";
		$btn_out = "<a href=\"/categories/list/parentid/##-ID-##/\" class=\"button button_out\">Torna al livello di categoria superiore</a>";
		$btn_add = "<a href=\"/categories/add/parentid/##-ID-##/\" class=\"button button_add\">Aggiungi categoria in questo livello</a>";

		if($mothercategory != null)
		{
			$page->addDictionary("category_up", self::buttonCategoryOut($mothercategory->getIdParentCategory()));
			$page->addDictionary("category_caption", " figlie della categoria " . $mothercategory->getName());
		}
		else
		{
			$page->addDictionary("category_up", "");
			$page->addDictionary("category_caption", "");
		}

		$page->addDictionary("category_add", self::buttonCategoryAdd($parentid));

		if(count($categories) == 0)
		{
			$html = "<tr><td>Nessuna categoria esistente in questo livello. Per aggiungere una nuova categoria a questo livello, clicca su \"Aggiungi categoria in questo livello\"</td><td>";
			$html .= self::buttonCategoryAdd($parentid);
			$html .= "</td></th>";
			$page->AddDictionary("TableContent", $html);
		}
		else
		{
			$html = "";
			foreach($categories as $category)
			{
				$html .= "
				<tr>
					<td scope=\"row\" data-title=\"Categoria\">" . $category->getName() . "</td>
					<td data-title=\"Operazioni\">
						<ul>
							<li>" . self::buttonCategoryEdit($category->getId(), $category->getName()) . "</li>
							<li>" . self::buttonCategoryDelete($category->getId(), $category->getName()) . "</li>
							<li>" . self::buttonCategoryInto($category->getId(), $category->getName()) . "</li>
						</ul>
					</td>
				</tr>";
			}
			$page->AddDictionary("TableContent", $html);
		}

		$page->render();
	}

	public function addAction($parentid = null)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin","unauthorized",null));

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$name = Application::cleanInput($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome della categoria &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$newcategory = new Category();
				$newcategory
					->setId(null)
					->setIdParentCategory($parentid)
					->setName($name);
				FrontController::DbManager()->categorySave($newcategory);
				return FrontController::getFrontController()->redirect("/categories/list/parentid/" . $parentid . "/");
			}
		}

		$page = new View();
		$page->setName("add");
		$page->setPath("categories/add.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Categorie");
		$page->setId("admin_categories_add");
		$page->setFormAction("/categories/add/parentid/".$parentid."/");

		$mothercategory = FrontController::DbManager()->categorySelect($parentid);
		$mcancestry = FrontController::DbManager()->categoryAncestry($mothercategory);

		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		$page->addBreadcrumb("Gestione categorie", "/categories/list/", null);
		if($mcancestry != null)
		{
			for($i=0; $i<count($mcancestry); $i++)
			{
				$page->addBreadcrumb($mcancestry[$i]->getName(), "/categories/list/parentid/" . $mcancestry[$i]->getId() . "/", null);
			}
		}
		$page->addBreadcrumb("Aggiungi sotto-categoria", null, null);

		$page->addDictionary("mothername", $mothercategory != null ? $mothercategory->getName() : "nessuna");

		if(count($errors) > 0)
		{
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		$page->render();
	}

	public function editAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin","unauthorized",null));

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");
	
		$category = FrontController::DbManager()->categorySelect($id);
		$mothercategory = FrontController::DbManager()->categorySelect($category->getIdParentCategory());
		$mcancestry = FrontController::DbManager()->categoryAncestry($category);

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$name = Application::cleanInput($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome della categoria &egrave; obbligatorio."
				);
			}else if(!preg_match('/^[-.a-zA-ZáàéèóòíìúùÁÀÉÈÍÌÓÒÚÙ\s]+$/', $name))
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il nome deve contenere solamente lettere."
				);
			}

			if(count($errors) == 0)
			{
				// form validate, saving to db
				$category->setName($name);
				FrontController::DbManager()->categorySave($category);
				return FrontController::getFrontController()->redirect("/categories/list/parentid/" . $category->getIdParentCategory() . "/");
			}
		}

		$page = new View();
		$page->setName("edit");
		$page->setPath("categories/edit.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Categorie");
		$page->setId("admin_categories_edit");
		$page->setFormAction("/categories/edit/id/".$id."/");

		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		$page->addBreadcrumb("Gestione categorie", "/categories/list/", null);
		if($mcancestry != null)
		{
			for($i=0; $i<count($mcancestry); $i++)
			{
				$page->addBreadcrumb($mcancestry[$i]->getName(), "/categories/list/parentid/" . $mcancestry[$i]->getId() . "/", null);
			}
		}
		$page->addBreadcrumb("Modifica categoria", null, null);

		$page->addDictionary("mothername", $mothercategory != null ? $mothercategory->getName() : "nessuna");
		$page->AddDictionary("Id", (isset($_POST["id"]) ? htmlspecialchars($_POST["id"]) : $category->getId()) );
		$page->AddDictionary("Name", (isset($_POST["name"]) ? htmlspecialchars($_POST["name"]) : $category->getName()) );
		

		if(count($errors) > 0)
		{
			foreach($errors as $err)
				$page->addFormError($err["field"], $err["message"]);
		}

		$page->render();
	}

	public function deleteAction($id)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect(FrontController::getUrl("admin","unauthorized",null));

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		$category = FrontController::DbManager()->categorySelect($id);
		FrontController::DbManager()->categoryDelete($id);
		return FrontController::getFrontController()->redirect("/categories/list/parentid/" . $category->getIdParentCategory() . "/");
	}

	public static function buttonCategoryEdit($id, $label)
	{
		$btn_edit = "<a href=\"";
		$btn_edit .= FrontController::getUrl("categories","edit",array("id"=>$id));
		$btn_edit .= "\" class=\"button button_edit\" aria-label=\"Modifica la categoria " . $label . "\">Modifica</a>";
		return $btn_edit;
	}
	public static function buttonCategoryDelete($id, $label)
	{
		$btn_delete = "<a href=\"";
		$btn_delete .= FrontController::getUrl("categories","delete",array("id"=>$id));
		$btn_delete .= "\" class=\"button button_delete\" aria-label=\"Elimina la categoria " . $label . "\">Elimina</a>";
		return $btn_delete;
	}
	public static function buttonCategoryInto($id, $label)
	{
		$btn_into = "<a href=\""; 
		$btn_into .= FrontController::getUrl("categories","list",array("parentid"=>$id));
		$btn_into .= "\" class=\"button button_into\" aria-label=\"Visualizza le sotto-categorie della categoria " . $label . "\">Visualizza le sotto-categorie</a>";
		return $btn_into;
	}
	public static function buttonCategoryOut($id)
	{
		$btn_out = "<a href=\"";
		$btn_out .= FrontController::getUrl("categories","list",array("parentid"=>$id));
		$btn_out .= "\" class=\"button button_out\">Torna al livello di categoria superiore</a>";
		return $btn_out;
	}
	public static function buttonCategoryAdd($id)
	{
		$btn_add = "<a href=\"";
		$btn_add .= FrontController::getUrl("categories","add",array("parentid"=>$id));
		$btn_add .= "\" class=\"button button_add\">Aggiungi categoria in questo livello</a>";
		return $btn_add;
	}
}