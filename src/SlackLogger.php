<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

use Exception;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Logger;


class SlackLogger extends Logger
{

	/** @var string */
	private $slackUrl;

	/** @var string */
	private $logUrl;


	public function __construct($slackUrl, $logUrl)
	{
		parent::__construct(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
		$this->slackUrl = $slackUrl;
		$this->logUrl = $logUrl;
	}


	/**
	 * @inheritdoc
	 */
	public function log($value, $priority = self::INFO)
	{
		$logFile = parent::log($value, $priority);

		$message = ucfirst($priority) . ': ';
		if ($value instanceof Exception) {
			$message .= $value->getMessage();
		} else {
			if (is_array($value)) {
				$message .= reset($value);
			} else {
				$message .= (string) $value;
			}
		}

		if ($this->logUrl && $logFile) {
			$message .= ' (<' . str_replace('__FILE__', basename($logFile), $this->logUrl) . '|Open log file>)';
		}

		$this->sendSlackMessage($message);
		return $logFile;
	}


	/**
	 * @param string $message
	 */
	private function sendSlackMessage($message)
	{
		file_get_contents($this->slackUrl, NULL, stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query([
					'payload' => json_encode([
						'text' => $message,
					])
				]),
			],
		]));
	}

}
