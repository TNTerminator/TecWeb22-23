<?php
/**
 * DbException.php
 * 
 * Class DbException for Database errors and exceptions management.
 * 
 */
class DbException extends Exception
{
	public const ERR_CONNECTION = 1;
	public const ERR_QUERY = 2;
	public const ERR_PREPSTMT = 3;

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