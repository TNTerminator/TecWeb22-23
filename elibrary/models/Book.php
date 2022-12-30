<?php
/**
 * Book.php
 * 
 * Model class that represents a book.
 */

class Book
{
	private $_Id;
	public function setId($id)
	{
		$this->_Id = intval($id);
		if($this->_Id == 0)
			$this->_Id = null;
		return $this;
	}
	public function getId()
	{
		return $this->_Id;
	}

	private $_Title;
	public function setTitle($title)
	{
		$this->_Title = $title;
		return $this;
	}
	public function getTitle()
	{
		return $this->_Title;
	}

	private $_IdAuthors = array();
	public function addIdAuthor($id)
	{
		$IdAuthor = intval($id);
		if($IdAuthor == 0)
			$IdAuthor = null;
		$this->_IdAuthors[] = $IdAuthor;
		return $this;
	}
	public function addIdAuthors($array_of_id)
	{
		if(is_array($array_of_id))
		{
			foreach($array_of_id as $id)
				$this->addIdAuthor($id);
		}
		return $this;
	}
	public function getIdAuthors()
	{
		return $this->_IdAuthors;
	}
	public function hasIdAuthor($id)
	{
		foreach($this->_IdAuthors as $idauthor)
			if($idauthor == $id)
				return true;
		return false;
	}
	public function emptyIdAuthors()
	{
		$this->_IdAuthors = array();
	}

	private $_IdCategories = array();
	public function addIdCategory($id)
	{
		$IdCategory = intval($id);
		if($IdCategory == 0)
			$IdCategory = null;
		$this->_IdCategories[] = $IdCategory;
		return $this;
	}
	public function addIdCategories($array_of_id)
	{
		if(is_array($array_of_id))
		{
			foreach($array_of_id as $id)
				$this->addIdCategory($id);
		}
		return $this;
	}
	public function getIdCategories()
	{
		return $this->_IdCategories;
	}
	public function hasIdCategory($id)
	{
		foreach($this->_IdCategories as $idcategory)
		{
			if($idcategory == $id)
				return true;
		}
		return false;
	}
	public function emptyIdCategories()
	{
		$this->_IdCategories = array();
	}

	private $_PubYear;
	public function setPubYear($year)
	{
		$this->_PubYear = intval($year);
		if($this->_PubYear == 0)
			$this->_PubYear = null;
		return $this;
	}
	public function getPubYear()
	{
		return $this->_PubYear;
	}

	private $_Editor;
	public function setEditor($editor)
	{
		$this->_Editor = $editor;
		return $this;
	}
	public function getEditor()
	{
		return $this->_Editor;
	}

	private $_Price;
	public function setPrice($price)
	{
		$this->_Price = floatval($price);
		if($this->_Price == 0)
			$this->_Price = null;
		return $this;
	}
	public function getPrice()
	{
		return $this->_Price;
	}

	private $_RatingValue;
	public function setRatingValue($value)
	{
		$this->_RatingValue = intval($value);
		return $this;
	}
	public function getRatingValue()
	{
		return $this->_RatingValue;
	}

	private $_RatingCount;
	public function setRatingCount($value)
	{
		$this->_RatingCount = intval($value);
		return $this;
	}
	public function getRatingCount()
	{
		return $this->_RatingCount;
	}

	public function getRating()
	{
		if($this->getRatingCount() == 0)
			return null;
		else
			return floatval($this->getRatingValue()) / floatval($this->getRatingCount());
	}

	private $_SoldQuantity;
	public function setSoldQuantity($quantity)
	{
		$this->_SoldQuantity = intval($quantity);
		return $this;
	}
	public function getSoldQuantity()
	{
		return $this->_SoldQuantity;
	}

	private $_ShortDescription;
	public function setShortDescription($descr)
	{
		$this->_ShortDescription = $descr;
		return $this;
	}
	public function getShortDescription()
	{
		return $this->_ShortDescription;
	}

	private $_Description;
	public function setDescription($descr)
	{
		$this->_Description = $descr;
		return $this;
	}
	public function getDescription()
	{
		return $this->_Description;
	}

	private $_TsCreate;
	public function setTsCreate($tscreate)
	{
		if($tscreate instanceof DateTime)
			$this->_TsCreate = $tscreate;
		else
			$this->_TsCreate = null;
		return $this;
	}
	public function getTsCreate()
	{
		return $this->_TsCreate;
	}

	private $_TsUpdate;
	public function setTsUpdate($tsupdate)
	{
		if($tsupdate instanceof DateTime)
			$this->_TsUpdate = $tsupdate;
		else
			$this->_TsUpdate = null;
		return $this;
	}
	public function getTsUpdate()
	{
		return $this->_TsUpdate;
	}

	public function getCover()
	{
		return ""; // TODO
	}

	public static function getRatingText($rating)
	{
		if($rating > 0 && $rating < 1.5)
		{
			return "Pessimo";
		}else if($rating >= 1.5 && $rating < 2.5)
		{
			return "Brutto";
		}else if($rating >= 2.5 && $rating < 3.5)
		{
			return "Neutro";
		}else if($rating >= 3.5 && $rating < 4.5)
		{
			return "Bello";
		}else if($rating >= 4.5)
		{
			return "Ottimo";
		}else 
			return "Non valutato";
	}

	public static function getRatingInt($rating)
	{
		if($rating > 0 && $rating < 1.5)
		{
			return 1;
		}else if($rating >= 1.5 && $rating < 2.5)
		{
			return 2;
		}else if($rating >= 2.5 && $rating < 3.5)
		{
			return 3;
		}else if($rating >= 3.5 && $rating < 4.5)
		{
			return 4;
		}else if($rating >= 4.5)
		{
			return 5;
		}else 
			return 0;
	}
}