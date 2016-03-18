<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

use Exception;
use Throwable;


interface IMessageFactory
{

	/**
	 * @param Exception|Throwable|array|string $value
	 * @param string $priority
	 * @param string|NULL $logFile
	 * @return IMessage
	 */
	function create($value, $priority, $logFile);

}
