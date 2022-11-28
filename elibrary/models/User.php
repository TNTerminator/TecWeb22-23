<?php
/**
 * User.php
 * 
 * Model class that represents a User object.
 */

class User
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

	public const TYPE_ADMIN = 1;
	public const TYPE_USER = 2;
	
	private $_Type;
	public function setType($type)
	{
		$type = intval($type);
		switch($type)
		{
			case self::TYPE_ADMIN:
			case self::TYPE_USER:
				$this->_Type = $type;
				break;

			default:
				$this->_Type = self::TYPE_USER;
		}
		return $this;
	}
	public function getType()
	{
		return $this->_Type;
	}

	private $_Username;
	public function setUsername($username)
	{
		$this->_Username = $username;
		return $this;
	}
	public function getUsername()
	{
		return $this->_Username;
	}

	private $_Email;
	public function setEmail($email)
	{
		$this->_Email = $email;
		return $this;
	}
	public function getEmail()
	{
		return $this->_Email;
	}

	private $_Password;
	public function setPassword($password)
	{
		$password = trim($password);
		if($password != null && $password != "")
		{
			$this->_Password = $password;
		}else
			$this->_Password = null;
		return $this;
	}
	public function getPassword()
	{
		return $this->_Password;
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

	private $_AdditionalInfo;
	public function setAdditionalInfo($addinfo)
	{
		$this->_AdditionalInfo = $addinfo;
		return $this;
	}
	public function getAdditionalInfo()
	{
		return $this->_AdditionalInfo;
	}

	private $_Privacy;
	public function setPrivacy($flag)
	{
		$this->_Privacy = boolval($flag);
		return $this;
	}
	public function getPrivacy()
	{
		return $this->_Privacy;
	}

	private $_Marketing;
	public function setMarketing($flag)
	{
		$this->_Marketing = boolval($flag);
		return $this;
	}
	public function getMarketing()
	{
		return $this->_Marketing;
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

	private $_TsLastLogin;
	public function setTsLastLogin($lastlogin)
	{
		if($lastlogin instanceof DateTime)
			$this->_TsLastLogin = $lastlogin;
		else
			$this->_TsLastLogin = null;
		return $this;
	}
	public function getTsLastLogin()
	{
		return $this->_TsLastLogin;
	}
}