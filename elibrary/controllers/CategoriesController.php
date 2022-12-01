<?php
/**
 * CategoriesController.php
 * 
 * The CategoriesController manages the categories of books.
 * 
 */

class CategoriesController
{
	public function indexAction()
	{
	}

	public function listAction($parentid = null)
	{
		if(!AuthController::isAdmin())
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		$page = new View();
		$page->setName("list");
		$page->setPath("categories/list.html");
		$page->setTemplate("main");
		$page->setTitle("Amministrazione sito - Gestione Categorie");
		$page->setId("admin_categories_list");

		$mothercategory = FrontController::DbManager()->categorySelect($parentid);
		$mcancestry = FrontController::DbManager()->categoryAncestry($mothercategory);
		$categories = FrontController::DbManager()->categoriesTree($parentid);

		$page->addBreadcrumb("Amministrazione sito", "/admin/index", null);
		if($mcancestry == null)
			$page->addBreadcrumb("Gestione categorie", null, null);
		else
		{
			$page->addBreadcrumb("Gestione categorie", "/categories/list/", null);
			for($i=0; $i<count($mcancestry); $i++)
			{
				if($i<count($mcancestry)-1)
					$page->addBreadcrumb($mcancestry[$i]->getName(), "/categories/list/parentid/" . $mcancestry[$i]->getId() . "/", null);
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
			$page->addDictionary("category_up", str_replace("##-ID-##", $mothercategory->getIdParentCategory(), $btn_out));
			$page->addDictionary("category_caption", " figlie della categoria " . $mothercategory->getName());
		}
		else
		{
			$page->addDictionary("category_up", "");
			$page->addDictionary("category_caption", "");
		}

		$page->addDictionary("category_add", str_replace("##-ID-##", $parentid, $btn_add));

		if(count($categories) == 0)
		{
			$html = "<tr><td>Nessuna categoria esistente in questo livello. Per aggiungere una nuova categoria a questo livello, clicca su \"Aggiungi categoria in questo livello\"</td><td>" . str_replace("##-ID-##", $parentid, $btn_add) . "</td></th>";
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
							<li>" . str_replace("##-Name-##", $category->getName(), str_replace("##-ID-##", $category->getId(), $btn_edit)) . "</li>
							<li>" . str_replace("##-Name-##", $category->getName(), str_replace("##-ID-##", $category->getId(), $btn_delete)) . "</li>
							<li>" . str_replace("##-Name-##", $category->getName(), str_replace("##-ID-##", $category->getId(), $btn_into)) . "</li>
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
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		// Form validation
		$errors = array();

		if(isset($_POST["CMD_Execute"]))
		{
			// Postback management
			$name = trim($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome della categoria &egrave; obbligatorio."
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
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

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
			$name = trim($_POST["name"]);
			if($name == "")
			{
				$errors[] = array(
					"field" => "name",
					"message" => "Attenzione: Il Nome della categoria &egrave; obbligatorio."
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
			return FrontController::getFrontController()->redirect("/admin/unauthorized/");

		if($id == null || $id <= 0)
			return FrontController::getFrontController()->redirect("/error/general/");

		$category = FrontController::DbManager()->categorySelect($id);
		FrontController::DbManager()->categoryDelete($id);
		return FrontController::getFrontController()->redirect("/categories/list/parentid/" . $category->getIdParentCategory() . "/");
	}
}