<?php
/**
 * GeneralException.php
 * 
 * Class GeneralException for undefined or general errors and exceptions management.
 * 
 */
class GeneralException extends Exception
{
	public const ERR_GENERAL = 0;
	public const ERR_SECURITY = 1;

    /**
	 * Redefine the exception so message isn't optional
	 */
    public function __construct($message, $code = 0, Throwable $previous = null) 
	{
		parent::__construct($message, $code, $previous);
    }

    /** 
	 * custom string representation of object
	 */
    public function __toString() 
	{
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}