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
    //Default maximum length of Slack message is 5000 characters. Note that ~150 characters should be reserved for interval warning (see SlackLogger::$showIntervalWarning)
    const MAX_MESSAGE_LENGTH = 4850;

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
    /** @var int|string|null The minimum interval between two events. Accepts either number of seconds or date/time string for strtotime(). Attribute $file has to be set as well, otherwise $interval won't be acknowledged. */
    private $interval;
    /** @var string|null File where the filter persists the state. */
    private $file;
    /** @var bool Indicates whether problems with the file write-ability was already logged (prevents recursion). */
    private $loggedUnwriteableFile = false;
    /** @var bool Whether information about no more slack notifications for <interval> seconds shall be sent with error message */
    private $showIntervalWarning = false;


    public function __construct($slackUrl, $logUrl, $channel = null, $username = null, $icon = null, $pretext = null, $file = null, $interval = null, $showIntervalWarning = true)
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
        $this->showIntervalWarning = $showIntervalWarning;
    }


    /**
     * @inheritdoc
     */
    public function log($value, $priority = self::INFO)
    {
        $notify = $this->isAllowedByInterval();

        $logFile = parent::log($value, $priority);

        if ($notify) {

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

            $truncated = mb_strlen($message) - self::MAX_MESSAGE_LENGTH;
            $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);

            if ($truncated > 0) {
                $message .= '...' . PHP_EOL . '(' . $truncated . ')';
            }

            $interval = $this->getInterval();

            if ($interval !== null) {
                //log time of this notification
                $this->mark();
                if ($this->showIntervalWarning) {
                    $secs = (is_numeric($interval) ? $interval : (strtotime($interval) ?: '?'));
                    $message .= PHP_EOL . '*NOTE: No further Slack notifications will be sent for another ' . $secs . ' seconds*';
                }
            }

            //if interval after last update has passed, flush error message to Slack
            $this->sendSlackMessage($message, $priority);
        }
        return $logFile;
    }

    /**
     * Marks the passed event and updates the stateful information.
     *
     * @return bool True whether mark was successful; false otherwise.
     */
    private function mark(): bool
    {
        $hasMarked = (bool)@file_put_contents($this->getFile(), static::class . PHP_EOL . date("r"));

        if (!$hasMarked && !$this->loggedUnwriteableFile) {
            $this->loggedUnwriteableFile = true;
            trigger_error("Unable to write to file '{$this->getFile()}'. Filter will deny the incoming events.", E_USER_WARNING);
        }

        return $hasMarked;
    }

    private function isAllowedByInterval(): bool
    {
        $now = time();

        $interval = $this->getInterval();
        $file = $this->getFile();

        if ($interval === null) {
            return true;
        }
        if ($file === null) {
            throw new \InvalidArgumentException(
                'Interval for SlackLogger is set, but no file for storing time of last notification is specified.'
            );
        }

        if (!is_numeric($interval)) {
            $interval = strtotime($interval) - $now;
        }

        $lastEventTime = @filemtime($this->getFile());
        $nextPossibleEventTime = $lastEventTime + $interval;

        return ($now >= $nextPossibleEventTime);
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
