<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

use greeny\NetteSlackLogger\Exception\HandlerException;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Logger;


class SlackLogger extends Logger
{

	/** @var string */
	private $slackUrl;

	/** @var array */
	private $handlers = [];

	/** @var IMessageFactory */
	private $messageFactory;


	public function __construct($slackUrl, IMessageFactory $messageFactory)
	{
		parent::__construct(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
		$this->slackUrl = $slackUrl;
		$this->messageFactory = $messageFactory;
	}


	public function addHandler($handler)
	{
		if (!is_callable($handler)) {
			throw new HandlerException($handler);
		}
		$this->handlers[] = $handler;
	}


	/**
	 * @inheritdoc
	 */
	public function log($value, $priority = self::INFO)
	{
		$logFile = parent::log($value, $priority);
		$message = $this->messageFactory->create($value, $priority, $logFile);
		$event = new MessageSendEvent($message, $value, $priority, $logFile);

		foreach ($this->handlers as $handler) {
			if (!is_callable($handler)) {
				throw new HandlerException($handler);
			}
			$handler($event);
		}


		if (!$event->isCancelled()) {
			$this->sendSlackMessage($message);
		}
		return $logFile;
	}


	/**
	 * @param IMessage $message
	 */
	private function sendSlackMessage(IMessage $message)
	{
		$result = @file_get_contents($this->slackUrl, NULL, stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query([
					'payload' => json_encode($a = array_filter([
						'channel' => $message->getChannel(),
						'username' => $message->getName(),
						'icon_emoji' => $message->getIcon(),
						'attachments' => [array_filter([
							'fallback' => $message->getText(),
							'text' => $message->getText(),
							'color' => $message->getColor(),
							'pretext' => $message->getTitle(),
						])],
					]))
				]),
			],
		]));
	}

}
