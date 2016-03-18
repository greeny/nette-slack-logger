<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger\Exception;

use Exception;


class HandlerException extends Exception
{

	private $handler;


	public function __construct($handler)
	{
		$this->handler = $handler;
		parent::__construct('Handler is not callable');
	}


	public function getHandler()
	{
		return $this->handler;
	}

}
