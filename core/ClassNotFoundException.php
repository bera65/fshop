<?php

/**
 * Thrown when a core class file is present but does not define the expected class,
 * or when a resolved path escapes the core directory.
 */
class ClassNotFoundException extends Exception
{
	/** @var string */
	private $className;

	public function __construct(string $className, string $message = '', int $code = 0, $previous = null)
	{
		$this->className = $className;

		if ($message === '') {
			$message = 'Class not found: ' . $className;
		}

		parent::__construct($message, $code, $previous);
	}

	public function getClassName(): string
	{
		return $this->className;
	}
}
