<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

use Exception;
use Throwable;


class MessageFactory implements IMessageFactory
{

	/** @var array */
	private $defaults;

	/** @var string|NULL */
	private $logUrl;


	public function __construct(array $defaults, $logUrl)
	{
		$this->defaults = $defaults;
		$this->logUrl = $logUrl;
	}


	/**
	 * @inheritdoc
	 */
	public function create($exception, $priority, $logFile)
	{
		$message = new Message($this->defaults);

		$text = ucfirst($priority) . ': ';
		if ($exception instanceof Exception || $exception instanceof Throwable) {
			$text .= $exception->getMessage();
		} elseif (is_array($exception)) {
			$text .= reset($exception);
		} else {
			$text .= (string) $exception;
		}

		if ($this->logUrl && $logFile) {
			$text .= ' (<' . str_replace('__FILE__', basename($logFile), $this->logUrl) . '|Open log file>)';
		}

		$message->setText($text);
	}

}
