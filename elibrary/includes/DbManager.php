<?php
/**
 * DbManager.php
 * 
 * Class that manages interactions with database.
 */
class DbManager
{
	private $DB_HOST;
	private $DB_NAME;
	private $DB_USER;
	private $DB_PASS;

	private $_Connection = null;
	private function Connection()
	{
		/* activate MySQLi reporting */
		$driver = new mysqli_driver();
		$driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

		if($this->_Connection == null)
		{
			try
			{
				$this->_Connection = new mysqli($this->DB_HOST, $this->DB_USER, $this->DB_PASS, $this->DB_NAME);
				$this->_Connection->set_charset("utf8");
			}catch(mysqli_sql_exception $e)
			{
				throw new DbException("C'&egrave stato un problema con la connessione al database.", DbException::ERR_CONNECTION, $e);
			}
		}
		return $this->_Connection;
	}

	public function __construct()
	{
		if(UNIPD_DELIVER)
		{
			$this->DB_HOST = "localhost";
			$this->DB_NAME = "acasadom";
			$this->DB_USER = "acasadom";
			$this->DB_PASS = "ii1EeY9quie8eich";
		}else
		{
			$this->DB_HOST = "localhost";
			$this->DB_NAME = "acasadom";
			$this->DB_USER = "acasadom";
			$this->DB_PASS = "ii1EeY9quie8eich";
			/*$this->DB_HOST = "localhost";
			$this->DB_NAME = "wgbdflgo_elibrary";
			$this->DB_USER = "wgbdflgo_elibrary";
			$this->DB_PASS = "elibrary2022";*/
		}

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		// Force the connection initialization
		$this->Connection();
	}


	public function authorsList()
	{
		$authors = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM authors ORDER BY Surname, Name");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($authorassoc = $result->fetch_assoc())
				{
					$BirthDate = DateTime::createFromFormat("Y-m-d", $authorassoc["BirthDate"]);
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsUpdate"]);
					$author = new Author();
					$author
						->setId($authorassoc["IDAuthor"])
						->setSurname($authorassoc["Surname"])
						->setName($authorassoc["Name"])
						->setPicture($authorassoc["Picture"])
						->setBirthDate($BirthDate)
						->setBirthPlace($authorassoc["BirthPlace"])
						->setCodMotherTongue($authorassoc["CodMotherTongue"])
						->setMotherTongue($this->languageSelect($authorassoc["CodMotherTongue"]))
						->setAdditionalInfo($authorassoc["AdditionalInfo"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$authors[] = $author;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $authors;
	}

	public function authorSelect($id)
	{
		$author = null;
		if($id == null || $id <= 0)
			return null;
		
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM authors WHERE IDAuthor = ?");
			$stmt->bind_param("i", $id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0 && $authorassoc = $result->fetch_assoc())
			{
				$BirthDate = DateTime::createFromFormat("Y-m-d", $authorassoc["BirthDate"]);
				$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsCreate"]);
				$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsUpdate"]);
				$author = new Author();
				$author
					->setId($authorassoc["IDAuthor"])
					->setSurname($authorassoc["Surname"])
					->setName($authorassoc["Name"])
					->setPicture($authorassoc["Picture"])
					->setBirthDate($BirthDate)
					->setBirthPlace($authorassoc["BirthPlace"])
					->setCodMotherTongue($authorassoc["CodMotherTongue"])
					->setMotherTongue($this->languageSelect($authorassoc["CodMotherTongue"]))
					->setAdditionalInfo($authorassoc["AdditionalInfo"])
					->setTsCreate($TsCreate)
					->setTsUpdate($TsUpdate);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $author;
	}

	public function authorSave(Author $author)
	{
		if($author->getId() == null)
			return $this->authorInsert($author);
		else
			return $this->authorUpdate($author);
	}

	public function authorInsert(Author $author)
	{
		if($author == null)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"INSERT INTO authors (" . 
					"Surname, " .
					"Name, " .
					"Picture, " .
					"BirthDate, " .
					"BirthPlace, " .
					"CodMotherTongue, " .
					"AdditionalInfo " .
				") VALUES (?, ?, ?, ?, ?, ?, ?)"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			$surname = $author->getSurname();
			$name = $author->getName();
			$picture = $author->getPicture();
			$birthdate = $author->getBirthDate()->format("Y-m-d");
			$birthplace = $author->getBirthPlace();
			$codmothertongue = $author->getCodMotherTongue();
			$additionalinfo = $author->getAdditionalInfo();
			$stmt->bind_param("sssssss", $surname, $name, $picture, $birthdate, $birthplace, $codmothertongue, $additionalinfo);
			$stmt->execute();
			$author->setId($this->Connection()->insert_id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function authorUpdate(Author $author)
	{
		if($author == null)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"UPDATE authors SET " . 
					"Surname = ?, " .
					"Name = ?, " .
					"Picture = ?, " .
					"BirthDate = ?, " .
					"BirthPlace = ?, " .
					"CodMotherTongue = ?, " .
					"AdditionalInfo = ?, " .
					"TsUpdate = NOW() " .
				"WHERE IDAuthor = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			$id = $author->getId();
			$surname = $author->getSurname();
			$name = $author->getName();
			$picture = $author->getPicture();
			$birthdate = $author->getBirthDate()->format("Y-m-d");
			$birthplace = $author->getBirthPlace();
			$codmothertongue = $author->getCodMotherTongue();
			$additionalinfo = $author->getAdditionalInfo();
			$stmt->bind_param("sssssssi", $surname, $name, $picture, $birthdate, $birthplace, $codmothertongue, $additionalinfo, $id);
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function authorDelete($id)
	{
		// TODO integrità referenziale!!!
		if($id == null || $id <= 0)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"DELETE FROM authors WHERE IDAuthor = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("i", $id);
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function booksSearchFullText($searchstring)
	{
		$books = array();
		$searchstring_like = "%".$searchstring."%";
		
		try
		{
			if($searchstring == null || $searchstring == "")
				return $books;

			$sqlstring = "SELECT * FROM books WHERE Title LIKE ?";
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->bind_param("s", $searchstring_like);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{ 
				while($bookassoc = $result->fetch_assoc())
				{
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$books[$book->getId()] = $book;
				}
			}
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement 1 ".__FUNCTION__." ha fallito l'execute: " . (isset($stmt) ? htmlspecialchars($stmt->error) : "no error object") . " " . print_r($e->getTrace(), true), DbException::ERR_QUERY, $e);
		}finally
		{
			if(isset($result))
				$result->close();
			if(isset($stmt))		
				$stmt->close();
		}
		try
		{
			$sqlstring = "SELECT * FROM books INNER JOIN books_categories ON books.IDBook = books_categories.IDBook WHERE IDCategory IN (SELECT IDCategory FROM categories WHERE Name LIKE ?)";
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->bind_param("s", $searchstring_like);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{ 
				while($bookassoc = $result->fetch_assoc())
				{
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$books[$book->getId()] = $book;
				}
			}
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement 2 ".__FUNCTION__." ha fallito l'execute: " . (isset($stmt) ? htmlspecialchars($stmt->error) : "no error object"), DbException::ERR_QUERY, $e);
		}finally
		{
			if(isset($result))
				$result->close();
			if(isset($stmt))		
				$stmt->close();
		}
		try
		{
			$sqlstring = "SELECT * FROM books INNER JOIN books_authors ON books.IDBook = books_authors.IDBook WHERE IDAuthor IN (SELECT IDAuthor FROM authors WHERE MATCH(Surname, Name) AGAINST (? IN NATURAL LANGUAGE MODE))";
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->bind_param("s", $searchstring);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{ 
				while($bookassoc = $result->fetch_assoc())
				{
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$books[$book->getId()] = $book;
				}
			}
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement 3 ".__FUNCTION__." ha fallito l'execute: " . (isset($stmt) ? htmlspecialchars($stmt->error) : "no error object"), DbException::ERR_QUERY, $e);
		}finally
		{
			if(isset($result))
				$result->close();
			if(isset($stmt))		
				$stmt->close();
		}
		try
		{

			$sqlstring = "SELECT * FROM books WHERE MATCH(Title, Editor, ShortDescription, Description) AGAINST (? IN NATURAL LANGUAGE MODE)";
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->bind_param("s", $searchstring);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{ 
				while($bookassoc = $result->fetch_assoc())
				{
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$books[$book->getId()] = $book;
				}
			}
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement 4 ".__FUNCTION__." ha fallito l'execute: " . (isset($stmt) ? htmlspecialchars($stmt->error) : "no error object"), DbException::ERR_QUERY, $e);
		}finally
		{
			if(isset($result))
				$result->close();
			if(isset($stmt))		
				$stmt->close();
		}
		return $books;
	}

	public function booksList()
	{
		$books = array();
		
		try
		{
			$sqlstring = "SELECT * FROM books";
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{ 
				while($bookassoc = $result->fetch_assoc())
				{
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function bookSelect($id)
	{
		$book = null;
		if($id == null || $id <= 0)
			return null;
		
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM books WHERE IDBook = ?");
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$result = $stmt->get_result();
			$book = new Book();
			if($result->num_rows > 0 && $bookassoc = $result->fetch_assoc())
			{
				$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsCreate"]);
				$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $bookassoc["TsUpdate"]);
				$book
					->setId($bookassoc["IDBook"])
					->setTitle($bookassoc["Title"])
					->setCover($bookassoc["Cover"])
					->setCoverCaption($bookassoc["CoverCaption"])
					->setPubYear($bookassoc["PubYear"])
					->setEditor($bookassoc["Editor"])
					->setPrice($bookassoc["Price"])
					->setRatingValue($bookassoc["RatingValue"])
					->setRatingCount($bookassoc["RatingCount"])
					->setSoldQuantity($bookassoc["SoldQuantity"])
					->setShortDescription($bookassoc["ShortDescription"])
					->setDescription($bookassoc["Description"])
					->setTsCreate($TsCreate)
					->setTsUpdate($TsUpdate);
			}
			$result->close();

			try
			{
				$stmt_cat = $this->Connection()->prepare("SELECT * FROM books_categories WHERE IDBook = ?");
				$stmt_cat->bind_param("i", $id);
				$stmt_cat->execute();
				$result_cat = $stmt_cat->get_result();
				if($result_cat->num_rows > 0)
				{
					while($catassoc = $result_cat->fetch_assoc())
					{
						$book
							->addIdCategory($catassoc["IDCategory"]);
					}
				}
				$result_cat->close();
			}catch(mysqli_sql_exception $e)
			{
				throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt_cat->error), DbException::ERR_QUERY, $e);
			}finally
			{
				$stmt_cat->close();
			}

			try
			{
				$stmt_aut = $this->Connection()->prepare("SELECT * FROM books_authors WHERE IDBook = ?");
				$stmt_aut->bind_param("i", $id);
				$stmt_aut->execute();
				$result_aut = $stmt_aut->get_result();
				if($result_aut->num_rows > 0)
				{
					while($autassoc = $result_aut->fetch_assoc())
					{
						$book
							->addIdAuthor($autassoc["IDAuthor"]);
					}
				}
				$result_aut->close();
			}catch(mysqli_sql_exception $e)
			{
				throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt_aut->error), DbException::ERR_QUERY, $e);
			}finally
			{
				$stmt_aut->close();
			}
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $book;
	}

	public function bookSave(Book $book)
	{
		if($book->getId() == null)
			return $this->bookInsert($book);
		else
			return $this->bookUpdate($book);
	}

	public function bookInsert(Book $book)
	{
		// Check if username or email already exists
		if($book == null)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"INSERT INTO books (" . 
					"Title," .
					"Cover," . 
					"CoverCaption," . 
					"PubYear," .
					"Editor," .
					"Price," . 
					"ShortDescription," . 
					"Description," .
					"TsCreate" . 
				") VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
			);
			$title = $book->getTitle();
			$cover = $book->getCover();
			$covercaption = $book->getCoverCaption();
			$pubyear = $book->getPubYear();
			$editor = $book->getEditor();
			$price = $book->getPrice();
			$shortdesc = $book->getShortDescription();
			$desc = $book->getDescription();
			$stmt->bind_param("sssisdss", $title, $cover, $covercaption, $pubyear, $editor, $price, $shortdesc, $desc);
			$stmt->execute();
			$book->setId($this->Connection()->insert_id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
			$stmt->close();
		}
		
		$result = $this->bookSaveAuthors($book);
		$result &= $this->bookSaveCategories($book);

		return $result;
	}

	public function bookUpdate(Book $book)
	{
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"UPDATE books SET " .
					"Title = ?," .
					"Cover = ?," .
					"CoverCaption = ?," .
					"PubYear = ?," . 
					"Editor = ?," . 
					"Price = ?," .
					"ShortDescription = ?," .
					"Description = ?," .
					"TsUpdate = NOW() " .
				"WHERE IDBook = ?"
			);
			$id = $book->getId();
			$title = $book->getTitle();
			$cover = $book->getCover();
			$covercaption = $book->getCoverCaption();
			$pubyear = $book->getPubYear();
			$editor = $book->getEditor();
			$price = $book->getPrice();
			$shortdesc = $book->getShortDescription();
			$desc = $book->getDescription();
			$stmt->bind_param("sssisdssi", $title, $cover, $covercaption, $pubyear, $editor, $price, $shortdesc, $desc, $id);
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
			$stmt->close();
		}
		
		$result = $this->bookSaveAuthors($book);
		$result &= $this->bookSaveCategories($book);

		return $result;
	}

	public function bookSaveAuthors($book)
	{
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"DELETE FROM books_authors WHERE IdBook = ?"
			);
			@$stmt->bind_param("i", $book->getId());
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'esecuzione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
			$stmt->close();
		}
		foreach($book->getIdAuthors() as $idauthor)
		{
			try
			{
				$stmt = $this->Connection()->prepare(
					"INSERT INTO books_authors (" . 
						"IdBook," .
						"IdAuthor" . 
					") VALUES (?, ?)"
				);
				@$stmt->bind_param("ii", $book->getId(), $idauthor);
				$stmt->execute();
			}catch(mysqli_sql_exception $e)
			{
				throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
				$stmt->close();
			}
		}
		$stmt->close();
		return true;
	}

	public function bookSaveCategories($book)
	{
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"DELETE FROM books_categories WHERE IdBook = ?"
			);
			@$stmt->bind_param("i", $book->getId());
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'esecuzione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
			$stmt->close();
		}
		foreach($book->getIdCategories() as $idcategory)
		{
			try
			{
				$stmt = $this->Connection()->prepare(
					"INSERT INTO books_categories (" . 
						"IdBook," .
						"IdCategory" . 
					") VALUES (?, ?)"
				);
				@$stmt->bind_param("ii", $book->getId(), $idcategory);
				$stmt->execute();
			}catch(mysqli_sql_exception $e)
			{
				throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
				$stmt->close();
			}
		}
		$stmt->close();
		return true;
	}

	public function categorySelect($id)
	{
		$category = null;
		if($id == null || $id <= 0)
			return null;
		
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM categories WHERE IDCategory = ?");
			$stmt->bind_param("i", $id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0 && $catassoc = $result->fetch_assoc())
			{
				$category = new Category();
				$category
					->setId($catassoc["IDCategory"])
					->setIdParentCategory($catassoc["IDParentCategory"])
					->setName($catassoc["Name"]);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $category;
	}

	public function categorySave(Category $category)
	{
		if($category->getId() == null)
			return $this->categoryInsert($category);
		else
			return $this->categoryUpdate($category);
	}

	public function categoryInsert(Category $category)
	{
		if($category == null)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"INSERT INTO categories (" . 
					"IDParentCategory, " .
					"Name" .
				") VALUES (?, ?)"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("is", $category->getIdParentCategory(), $category->getName());
			$stmt->execute();
			$category->setId($this->Connection()->insert_id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function categoryUpdate(Category $category)
	{
		if($category == null)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"UPDATE categories SET " . 
					"IDParentCategory = ?, " .
					"Name = ? " .
				"WHERE IDCategory = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("isi", $category->getIdParentCategory(), $category->getName(), $category->getId());
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function categoryDelete($id)
	{
		// TODO cancellare anche le figlie !!!
		if($id == null || $id <= 0)
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"DELETE FROM categories WHERE IDCategory = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("i", $id);
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function categoryAncestry($category)
	{
		if($category == null)
			return null;
		else
		{
			$parentcategory = $this->categorySelect($category->getIdParentCategory());
			$ancestry = $this->categoryAncestry($parentcategory);
			if($ancestry == null)
				$ancestry = array();
			$ancestry[] = $category;
			return $ancestry;
		}
	}

	public function categoriesTree($idcategoryparent = null)
	{
		$categories = array();
		$stmt = null;
		try
		{
			if($idcategoryparent != null)
			{
				$stmt = $this->Connection()->prepare("SELECT * FROM categories WHERE IDParentCategory = ? ORDER BY Name");
				$stmt->bind_param("i", $idcategoryparent);
			}else
				$stmt = $this->Connection()->prepare("SELECT * FROM categories WHERE IDParentCategory IS NULL ORDER BY Name");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($catassoc = $result->fetch_assoc())
				{
					$category = new Category();
					$category
						->setId($catassoc["IDCategory"])
						->setIdParentCategory($catassoc["IDParentCategory"])
						->setName($catassoc["Name"]);
					$categories[] = $category;
					$category->setChilds($this->categoriesTree($category->getId()));
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $categories;
	}

	public function languageList()
	{
		$languages = array();
		
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM languages ORDER BY Lang");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			while($result->num_rows > 0 && $langassoc = $result->fetch_assoc())
			{
				$languages[$langassoc["CodLang"]] = $langassoc["Lang"];
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $languages;
	}

	public function languageSelect($codlanguage)
	{
		$language = null;
		if($codlanguage == null)
			return null;
		
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM languages WHERE CodLang = ?");
			$stmt->bind_param("s", $codlanguage);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0 && $langassoc = $result->fetch_assoc())
			{
				$language = $langassoc["Lang"];
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $language;
	}

	public function userSelect($id)
	{
		$user = null;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE IDUser = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$userassoc = $result->fetch_assoc();
				$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
				if ($datanascita === false)
					$datanascita = null;
				$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
				if ($tscreate === false)
					$tscreate = null;
				$tsupdate = $userassoc["TsUpdate"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]) : null;
				if ($tsupdate === false)
					$tsupdate = null;
				$tslastlogin = $userassoc["TsLastLogin"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]) : null;
				if ($tslastlogin === false)
					$tslastlogin = null;

				$user = new User();
				$user
					->setId($userassoc["IDUser"])
					->setType($userassoc["IDUserType"])
					->setUsername($userassoc["Username"])
					->setEmail($userassoc["Email"])
					->setPassword($userassoc["Password"])
					->setName($userassoc["Name"])
					->setSurname($userassoc["Surname"])
					->setBirthDate($datanascita)
					->setAdditionalInfo($userassoc["AdditionalInfo"])
					->setPrivacy($userassoc["F_Privacy"])
					->setMarketing($userassoc["F_Marketing"])
					->setTsCreate($tscreate)
					->setTsUpdate($tsupdate)
					->setTsLastLogin($tslastlogin);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $user;
	}

	public function userSave(User $user)
	{
		if($user->getId() == null)
			return $this->userInsert($user);
		else
			return $this->userUpdate($user);
	}

	public function userInsert(User $user)
	{
		// Check if username or email already exists
		if($user == null || $this->checkUsernameExists($user->getUsername()) || $this->checkUserEmailExists($user->getEmail()))
			return false;

		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"INSERT INTO users (" . 
					"IDUserType," . 
					"Username," .
					"Email," .
					"Name," .
					"Surname," . 
					"BirthDate," . 
					"Password," . 
					"AdditionalInfo," .
					"F_Privacy," .
					"F_Marketing," .
					"TsCreate" .
				") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}

		$dn = $user->getBirthDate();
		
		try
		{
			@$stmt->bind_param("isssssssii", $user->getType(), $user->getUsername(), $user->getEmail(), $user->getName(), $user->getSurname(), $dn->format("Y-m-d"), $user->getPassword(), $user->getAdditionalInfo(), $user->getPrivacy(), $user->getMarketing());
			$stmt->execute();
			$user->setId($this->Connection()->insert_id);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function userUpdate(User $user)
	{
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"UPDATE users SET " .
					"IDUserType = ?," .
					"Name = ?," .
					"Surname = ?," . 
					"BirthDate = ?," .
					"Password = ?," . 
					"AdditionalInfo = ?," .
					"F_Privacy = ?," .
					"F_Marketing = ?, " .
					"TsUpdate = NOW() " . 
				"WHERE IDUser = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("isssssiis", $user->getType(), $user->getName(), $user->getSurname(), $user->getBirthDate()->format("Y-m-d"), $user->getPassword(), $user->getAdditionalInfo(), $user->getPrivacy(), $user->getMarketing(), $user->getId());
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return true;
	}

	public function userUpdateLastLogin(User $user)
	{
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare(
				"UPDATE users SET TsLastLogin = NOW() " . 
				"WHERE IDUser = ?"
			);
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}
		
		try
		{
			@$stmt->bind_param("i", $user->getId());
			$stmt->execute();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
	}

	public function userByUsername($username)
	{
		$user = null;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE Username = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$userassoc = $result->fetch_assoc();
				$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
				if ($datanascita === false)
					$datanascita = null;
				$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
				if ($tscreate === false)
					$tscreate = null;
				$tsupdate = $userassoc["TsUpdate"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]) : null;
				if ($tsupdate === false)
					$tsupdate = null;
				$tslastlogin = $userassoc["TsLastLogin"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]) : null;
				if ($tslastlogin === false)
					$tslastlogin = null;

				$user = new User();
				$user
					->setId($userassoc["IDUser"])
					->setType($userassoc["IDUserType"])
					->setUsername($userassoc["Username"])
					->setEmail($userassoc["Email"])
					->setPassword($userassoc["Password"])
					->setName($userassoc["Name"])
					->setSurname($userassoc["Surname"])
					->setBirthDate($datanascita)
					->setAdditionalInfo($userassoc["AdditionalInfo"])
					->setPrivacy($userassoc["F_Privacy"])
					->setMarketing($userassoc["F_Marketing"])
					->setTsCreate($tscreate)
					->setTsUpdate($tsupdate)
					->setTsLastLogin($tslastlogin);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $user;
	}

	public function userByEmail($email)
	{
		$user = null;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE Email = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$userassoc = $result->fetch_assoc();
				$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
				if ($datanascita === false)
					$datanascita = null;
				$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
				if ($tscreate === false)
					$tscreate = null;
				$tsupdate = $userassoc["TsUpdate"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]) : null;
				if ($tsupdate === false)
					$tsupdate = null;
				$tslastlogin = $userassoc["TsLastLogin"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]) : null;
				if ($tslastlogin === false)
					$tslastlogin = null;

				$user = new User();
				$user
					->setId($userassoc["IDUser"])
					->setType($userassoc["IDUserType"])
					->setUsername($userassoc["Username"])
					->setEmail($userassoc["Email"])
					->setPassword($userassoc["Password"])
					->setName($userassoc["Name"])
					->setSurname($userassoc["Surname"])
					->setBirthDate($datanascita)
					->setAdditionalInfo($userassoc["AdditionalInfo"])
					->setPrivacy($userassoc["F_Privacy"])
					->setMarketing($userassoc["F_Marketing"])
					->setTsCreate($tscreate)
					->setTsUpdate($tsupdate)
					->setTsLastLogin($tslastlogin);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $user;
	}

	public function checkUsernameExists($username)
	{
		$num_rows = 1;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE Username = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();
			$num_rows = $result->num_rows;
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $num_rows > 0;
	}

	public function checkUserEmailExists($email)
	{
		$num_rows = 1;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE Email = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$result = $stmt->get_result();
			$num_rows = $result->num_rows;
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $num_rows > 0;
	}

	public function getUserByLogin($username, $password)
	{
		$user = null;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE Username = ? OR Email = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("ss", $username, $username);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$userassoc = $result->fetch_assoc();
				if(password_verify($password, $userassoc["Password"]))
				{
					$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
					if ($datanascita === false)
						$datanascita = null;
					$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
					if ($tscreate === false)
						$tscreate = null;
					$tsupdate = $userassoc["TsUpdate"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]) : null;
					if ($tsupdate === false)
						$tsupdate = null;
					$tslastlogin = $userassoc["TsLastLogin"]!=null ? DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]) : null;
					if ($tslastlogin === false)
						$tslastlogin = null;

					$user = new User();
					$user
						->setId($userassoc["IDUser"])
						->setType($userassoc["IDUserType"])
						->setUsername($userassoc["Username"])
						->setEmail($userassoc["Email"])
						->setPassword($userassoc["Password"])
						->setName($userassoc["Name"])
						->setSurname($userassoc["Surname"])
						->setBirthDate($datanascita)
						->setAdditionalInfo($userassoc["AdditionalInfo"])
						->setPrivacy($userassoc["F_Privacy"])
						->setMarketing($userassoc["F_Marketing"])
						->setTsCreate($tscreate)
						->setTsUpdate($tsupdate)
						->setTsLastLogin($tslastlogin);
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $user;
	}

	public function getAuthorsByBook($idbook)
	{
		$authors = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM authors NATURAL JOIN books_authors WHERE IDBook = ?");
			$stmt->bind_param("i", $idbook);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($authorassoc = $result->fetch_assoc())
				{
					$BirthDate = DateTime::createFromFormat("Y-m-d", $authorassoc["BirthDate"]);
					$TsCreate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsCreate"]);
					$TsUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $authorassoc["TsUpdate"]);
					$author = new Author();
					$author
						->setId($authorassoc["IDAuthor"])
						->setSurname($authorassoc["Surname"])
						->setName($authorassoc["Name"])
						->setPicture($authorassoc["Picture"])
						->setBirthDate($BirthDate)
						->setBirthPlace($authorassoc["BirthPlace"])
						->setCodMotherTongue($authorassoc["CodMotherTongue"])
						->setMotherTongue($this->languageSelect($authorassoc["CodMotherTongue"]))
						->setAdditionalInfo($authorassoc["AdditionalInfo"])
						->setTsCreate($TsCreate)
						->setTsUpdate($TsUpdate);
					$authors[] = $author;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $authors;
	}

	public function getBooksByAuthor($idauthor)
	{
		$books = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM books NATURAL JOIN books_authors WHERE IDAuthor = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("i", $idauthor);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($bookassoc = $result->fetch_assoc())
				{
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"]);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function getBooksByCategory($idcategory, $limit = null)
	{
		$books = array();
		$stmt = null;
		try
		{
			$sqlstring = "SELECT * FROM books NATURAL JOIN books_categories WHERE IDCategory = ?";
			if($limit != null)
				$sqlstring .= " LIMIT 0," . $limit;
			$stmt = $this->Connection()->prepare($sqlstring);
			$stmt->bind_param("i", $idcategory);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($bookassoc = $result->fetch_assoc())
				{
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"]);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function getBooksLatest($num = 6)
	{
		$books = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM books ORDER BY TsCreate DESC LIMIT 0, " . $num);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($bookassoc = $result->fetch_assoc())
				{
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"]);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function getBooksBestSeller($num = 6)
	{
		$books = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM books ORDER BY SoldQuantity DESC LIMIT 0, " . $num);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($bookassoc = $result->fetch_assoc())
				{
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"]);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function getBooksTopRating($num = 6)
	{
		$books = array();
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT *, (CAST(RatingValue AS DECIMAL) / CAST(RatingCount AS DECIMAL)) AS RatingScore FROM books ORDER BY RatingScore DESC LIMIT 0, " . $num);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				while($bookassoc = $result->fetch_assoc())
				{
					$book = new Book();
					$book
						->setId($bookassoc["IDBook"])
						->setTitle($bookassoc["Title"])
						->setCover($bookassoc["Cover"])
						->setCoverCaption($bookassoc["CoverCaption"])
						->setPubYear($bookassoc["PubYear"])
						->setEditor($bookassoc["Editor"])
						->setPrice($bookassoc["Price"])
						->setRatingValue($bookassoc["RatingValue"])
						->setRatingCount($bookassoc["RatingCount"])
						->setSoldQuantity($bookassoc["SoldQuantity"])
						->setShortDescription($bookassoc["ShortDescription"])
						->setDescription($bookassoc["Description"]);
					$books[] = $book;
				}
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}finally
		{
			$stmt->close();
		}
		return $books;
	}

	public function getUserById($id)
	{
		$id = intval($id);
		if($id == 0)
			return null;

		$user = null;
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users WHERE IDUser = ?");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$userassoc = $result->fetch_assoc();
				$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
				if ($datanascita === false)
					$datanascita = null;
				$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
				if ($tscreate === false)
					$tscreate = null;
				$tsupdate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]);
				if ($tsupdate === false)
					$tsupdate = null;
				$tslastlogin = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]);
				if ($tslastlogin === false)
					$tslastlogin = null;

				$user = new User();
				$user
					->setId($userassoc["ID"])
					->setType($userassoc["IDUserType"])
					->setUsername($userassoc["Username"])
					->setEmail($userassoc["Email"])
					->setPassword($userassoc["Password"])
					->setName($userassoc["Name"])
					->setSurname($userassoc["Surname"])
					->setBirthDate($datanascita)
					->setAdditionalInfo($userassoc["AdditionalInfo"])
					->setPrivacy($userassoc["F_Privacy"])
					->setMarketing($userassoc["F_Marketing"])
					->setTsCreate($tscreate)
					->setTsUpdate($tsupdate)
					->setTsLastLogin($tslastlogin);
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $user;
	}

	public function getUsers()
	{
		$users = array();
		
		$stmt = null;
		try
		{
			$stmt = $this->Connection()->prepare("SELECT * FROM users ORDER BY Surname, Name");
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito la creazione: " . htmlspecialchars($this->Connection()->error), DbException::ERR_PREPSTMT, $e);
		}		
		try
		{
			$stmt->execute();
			$result = $stmt->get_result();
			while ($userassoc = $result->fetch_assoc()) 
			{
				$userassoc = $result->fetch_assoc();
				$datanascita = DateTime::createFromFormat("Y-m-d", $userassoc["BirthDate"]);
				if ($datanascita === false)
					$datanascita = null;
				$tscreate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsCreate"]);
				if ($tscreate === false)
					$tscreate = null;
				$tsupdate = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsUpdate"]);
				if ($tsupdate === false)
					$tsupdate = null;
				$tslastlogin = DateTime::createFromFormat("Y-m-d H:i:s", $userassoc["TsLastLogin"]);
				if ($tslastlogin === false)
					$tslastlogin = null;

				$user = new User();
				$user
					->setId($userassoc["ID"])
					->setType($userassoc["IDUserType"])
					->setUsername($userassoc["Username"])
					->setEmail($userassoc["Email"])
					->setPassword($userassoc["Password"])
					->setName($userassoc["Name"])
					->setSurname($userassoc["Surname"])
					->setBirthDate($datanascita)
					->setAdditionalInfo($userassoc["AdditionalInfo"])
					->setPrivacy($userassoc["F_Privacy"])
					->setMarketing($userassoc["F_Marketing"])
					->setTsCreate($tscreate)
					->setTsUpdate($tsupdate)
					->setTsLastLogin($tslastlogin);
				$users[] = $user;
			}
			$result->close();
		}catch(mysqli_sql_exception $e)
		{
			throw new DbException("Il prepared statement ".__FUNCTION__." ha fallito l'execute: " . htmlspecialchars($stmt->error), DbException::ERR_QUERY, $e);
		}finally
		{
			$stmt->close();
		}
		return $users;
	}
}