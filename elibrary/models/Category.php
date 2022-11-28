<?php
/**
 * Category.php
 * 
 * Model class that represents a Category of books.
 */

class Category
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

	private $_IdParentCategory;
	public function setIdParentCategory($id)
	{
		$this->_IdParentCategory = intval($id);
		if($this->_IdParentCategory == 0)
			$this->_IdParentCategory = null;
		return $this;
	}
	public function getIdParentCategory()
	{
		return $this->_IdParentCategory;
	}
	public function setParentCategory(Category $parent)
	{
		if($parent == null)
			return $this->setIdParentCategory(null);
		else
			return $this->setIdParentCategory($parent->getId());
	}

	private $_Name;
	public function setName($name)
	{
		$this->_Name = $name;
		return $this;
	}
	public function getName()
	{
		return $this->_Name;
	}

	private $_Childs;
	public function addChild(Category $category)
	{
		if($this->_Childs == null)
			$this->_Childs = array();
		$this->_Childs[] = $category;
	}
	public function setChilds($childs)
	{
		$this->_Childs = $childs;
	}
	public function getChilds()
	{
		return $this->_Childs;
	}
}