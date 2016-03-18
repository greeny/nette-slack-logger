# nette-slack-logger
Log your errors directly into Slack room

## Installation

`composer require greeny/nette-slack-logger`

And register extension to your config.neon:

```yaml
extensions:
	slackLogger: greeny\NetteSlackLogger\DI\SlackLoggerExtension
```

By default the logger is just turned off, since you probably do not want to log errors from dev environment.
If you want to enable it, add following lines to config.local.neon at your production server:

```yaml
slackLogger:
	enabled: true
	slackUrl: https://hooks.slack.com/services/XXX
	logUrl: http://path/to/your/logs/directory/__FILE__
```

Of course replace `slackUrl` with payload URL from your incomming webhook from Slack.

You can leave `logUrl` empty, but if you have your logs accessible through web (of course e.g. protected by HTTP auth or available only from company IPs),
you can define this URL here. `__FILE__` will be replaced by filename of file with exception.

### Configuration

You can also futher configure your logger:

```yaml
slackLogger:
	messageFactory: Some\Message\Factory
	defaults:
		channel: XXX
		icon: XXX
		name: XXX
		title: XXX
		text: XXX
		color: XXX
```

`messageFactory` holds FQN of class, which is implementing `greeny\NetteSlackLogger\IMessageFactory`. This class is used for creating messages for Slack.
You can omit it, if you want to use default provided one. If you create custom message factory, it receives default parameters as first argument and `logUrl` as second one.

Defaults are self-explaining, but here is description of them:

- `channel` - the channel you want your messages to arrive (overrides settings in webhook in slack administration)
- `icon` - the icon you want your bot to have (overrides settings in webhook in slack administration)
- `name` - the name you want your bot to have (overrides settings in webhook in slack administration)
- `title` - the title you want your message to have
- `text` - the text you want your message to have (you will probably never use this, but it is here for consistency purposes)
- `color` - the color you want your message to have (accepts `#RRGGBB` and maybe some other things, not sure about it)

### Handlers

You can also set custom handlers for logger. Just get your logger instance through DI or by calling `Tracy\Debugger::getLogger()` and use method `addHandler` to add your custom handler.

Handler is a callable, which receives `greeny\NetteSlackLogger\MessageSendEvent` as only argument. It has set of usefull methods:

- `getMessage()` - returns the message being sent
- `getValue()` - returns value being logged
- `getPriority()` - returns the priority of this log action
- `getLogFile()` - returns file to which value was logged (or NULL if nothing got logged)
- `cancel()` - cancels sending of message
- `isCancelled()` - returns if sending is cancelled or not

`getMessage()` method returns instance of `greeny\NetteSlackLogger\IMessage`, which has getters and setters for same properties, which you define in `defaults` section (see Configuration)

You can alter your message here, since handlers are called before message is sent. If you cancel message, it still gets logged into that file.
