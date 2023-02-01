<?php
/**
 * Order.php
 * 
 * Model class that represents a Order of books.
 */

class Order
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

	private $_IdUser;
	public function setIdUser($id)
	{
		$this->_IdUser = intval($id);
		if($this->_IdUser == 0)
			$this->_IdUser = null;
		return $this;
	}
	public function getIdUser()
	{
		return $this->_IdUser;
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

	private $_Details = array();
	public function addDetail($idbook, $quantity)
	{
		$this->_Details[] = array( "idbook" => $idbook, "quantity" => $quantity);
	}
	public function getDetails()
	{
		return $this->_Details;
	}
}