<?php
/**
 * Author.php
 * 
 * Model class that represents an author.
 */

class Author
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

	private $_Surname;
	public function setSurname($surname)
	{
		$this->_Surname = $surname;
		return $this;
	}
	public function getSurname()
	{
		return $this->_Surname;
	}

	private $_BirthDate;
	public function setBirthDate($birthdate)
	{
		if($birthdate instanceof DateTime)
			$this->_BirthDate = $birthdate;
		else
			$this->_BirthDate = new DateTime();
		return $this;
	}
	public function getBirthDate()
	{
		return $this->_BirthDate;
	}

	private $_BirthPlace;
	public function setBirthPlace($birthplace)
	{
		$this->_BirthPlace = $birthplace;
		return $this;
	}
	public function getBirthPlace()
	{
		return $this->_BirthPlace;
	}

	private $_CodMotherTongue;
	public function setCodMotherTongue($CodMotherTongue)
	{
		$this->_CodMotherTongue = $CodMotherTongue;
		return $this;
	}
	public function getCodMotherTongue()
	{
		return $this->_CodMotherTongue;
	}

	private $_MotherTongue;
	public function setMotherTongue($MotherTongue)
	{
		$this->_MotherTongue = $MotherTongue;
		return $this;
	}
	public function getMotherTongue()
	{
		return $this->_MotherTongue;
	}

	private $_AdditionalInfo;
	public function setAdditionalInfo($AdditionalInfo)
	{
		$this->_AdditionalInfo = $AdditionalInfo;
		return $this;
	}
	public function getAdditionalInfo()
	{
		return $this->_AdditionalInfo;
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

	public function getPicture()
	{
		return ""; // TODO
	}
}