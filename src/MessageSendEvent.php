<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

use Exception;
use Throwable;


class MessageSendEvent
{

	/** @var bool */
	private $cancelled = FALSE;

	/** @var Message */
	private $message;

	/** @var Exception|Throwable|array|string */
	private $value;

	/** @var string */
	private $priority;

	/** @var string */
	private $logFile;


	public function __construct(IMessage $message, $value, $priority, $logFile)
	{
		$this->message = $message;
		$this->value = $value;
		$this->priority = $priority;
		$this->logFile = $logFile;
	}


	/**
	 * @return $this
	 */
	public function cancel()
	{
		$this->cancelled = TRUE;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function isCancelled()
	{
		return $this->cancelled;
	}


	/**
	 * @return Message
	 */
	public function getMessage()
	{
		return $this->message;
	}


	/**
	 * @return Exception|Throwable|array|string
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @return string
	 */
	public function getPriority()
	{
		return $this->priority;
	}


	/**
	 * @return string
	 */
	public function getLogFile()
	{
		return $this->logFile;
	}

}
