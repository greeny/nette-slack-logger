<?php
/**
 * @author Tomáš Blatný
 * @author Ondřej Bouda <bouda@edookit.com>
 */
namespace OndrejBouda\NetteSlackLogger;

use Exception;
use Tracy\Debugger;
use Tracy\ILogger;
use Tracy\Logger;


class SlackLogger extends Logger
{
	/** @var string */
	private $slackUrl;
	/** @var string */
	private $logUrl;
	/** @var string */
	private $channel;
	/** @var string */
	private $username;
	/** @var string */
	private $icon;
	/** @var string */
	private $pretext;


	public function __construct($slackUrl, $logUrl, $channel = NULL, $username = NULL, $icon = NULL, $pretext = NULL)
	{
		parent::__construct(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
		$this->slackUrl = $slackUrl;
		$this->logUrl = $logUrl;
		$this->channel = $channel;
		$this->username = $username;
		$this->icon = $icon;
		$this->pretext = $pretext;
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
		} elseif (is_array($value)) {
			$message .= reset($value);
		} else {
			$message .= (string) $value;
		}

		if ($logFile && $this->logUrl) {
			$message .= ' (<' . str_replace('__FILE__', basename($logFile), $this->logUrl) . '|Open log file>)';
		}

		$this->sendSlackMessage($message, $priority);
		return $logFile;
	}


	/**
	 * @param string $message
	 * @param string $priority one of {@link ILogger} priority constants
	 */
	private function sendSlackMessage($message, $priority)
	{
		$payload = array_filter([
			'channel' => $this->channel,
			'username' => $this->username,
			'icon_emoji' => $this->icon,
			'attachments' => [
				array_filter([
					'text' => $message,
					'color' => self::getColor($priority),
					'pretext' => $this->pretext,
				]),
			],
		]);

		self::slackPost($this->slackUrl, ['payload' => json_encode($payload)]);
	}

	private static function getColor($priority)
	{
		switch ($priority) {
			case ILogger::DEBUG:
			case ILogger::INFO:
				return '#444444';

			case ILogger::WARNING:
				return 'warning';

			case ILogger::ERROR:
			case ILogger::EXCEPTION:
			case ILogger::CRITICAL:
				return 'danger';

			default:
				return null;
		}
	}

	private static function slackPost($url, array $postContent)
	{
		$ctxOptions = [
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query($postContent),
			],
		];
		$ctx = stream_context_create($ctxOptions);
		$resultStr = file_get_contents($url, NULL, $ctx);

		if ($resultStr === FALSE) {
			throw new \RuntimeException('Error sending request to the Slack API.');
		}
		$result = json_decode($resultStr);
		if ($result === NULL) {
			throw new \RuntimeException('Error decoding response from Slack - not a well-formed JSON.');
		}
		if (!$result->ok) {
			throw new \RuntimeException('Slack Error: ' . $result->error);
		}
		return $result;
	}
}
