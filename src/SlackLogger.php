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
    /** @var int|string The minimum interval between two events. */
    private $interval = '1 day';
    /** @var string|null File where the filter persists the state. */
    private $file;
    /** @var bool Indicates whether problems with the file write-ability was already logged (prevents recursion). */
    private $loggedUnwriteableFile = false;


    public function __construct($slackUrl, $logUrl, $channel = null, $username = null, $icon = null, $pretext = null, $file = null, $interval = null)
    {
        parent::__construct(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
        $this->slackUrl = $slackUrl;
        $this->logUrl = $logUrl;
        $this->channel = $channel;
        $this->username = $username;
        $this->icon = $icon;
        $this->pretext = $pretext;
        $this->file = $file;
        $this->interval = $interval;
    }


    /**
     * @inheritdoc
     */
    public function log($value, $priority = self::INFO)
    {
        $logFile = parent::log($value, $priority);

        $message = ucfirst($priority) . ': ';
        if ($value instanceof Exception || $value instanceof \Throwable) { // NOTE: backwards compatibility with PHP 5
            $message .= $value->getMessage();
        } elseif (is_array($value)) {
            $message .= reset($value);
        } else {
            $message .= (string)$value;
        }

        if ($logFile && $this->logUrl) {
            $message .= ' (<' . str_replace('__FILE__', basename($logFile), $this->logUrl) . '|Open log file>)';
        }

        $notify = $this->isAllowedByInterval();
        //add logged message to buffer
        $logSuccessful = (bool) @file_put_contents($this->getFile(), "\n" . date("r") . "\n" . $message, FILE_APPEND);

        if (!$logSuccessful && !$this->loggedUnwriteableFile) {
            $this->loggedUnwriteableFile = true;
            trigger_error("Unable to write to file '{$this->getFile()}'. Filter will deny the incoming events.", E_USER_WARNING);
        }

        if ($notify) {
            //if interval after last update has passed, flush all messages in queue to Slack
            $this->sendSlackMessage(@file_get_contents($this->getFile()), $priority);
            @file_put_contents($this->getFile(), null);
        }
        return $logFile;
    }

    private function isAllowedByInterval(): bool
    {
        $now = time();

        $interval = $this->getInterval();
        if (!isset($interval)) {
            return true;
        }
        if (!is_numeric($interval)) {
            $interval = strtotime($interval) - $now;
        }

        $lastEventTime = @filemtime($this->getFile());
        $nextPossibleEventTime = $lastEventTime + $interval;

        return $now >= $nextPossibleEventTime;
    }

    /**
     * @return null|string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int|string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param string $message
     * @param string $priority one of {@link ILogger} priority constants
     */
    private function sendSlackMessage($message, $priority)
    {
        $payload = array_filter(
            [
                'channel' => $this->channel,
                'username' => $this->username,
                'icon_emoji' => $this->icon,
                'attachments' => [
                    array_filter(
                        [
                            'text' => $message,
                            'color' => self::getColor($priority),
                            'pretext' => $this->pretext,
                        ]
                    ),
                ],
            ]
        );

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
        $resultStr = @file_get_contents($url, null, $ctx);

        if ($resultStr != 'ok') {
            throw new \RuntimeException('Error sending request to the Slack API: ' . $http_response_header[0]);
        }
    }
}
