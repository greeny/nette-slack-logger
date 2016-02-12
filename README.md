# nette-slack-logger
Log your errors directly into Slack room

## Installation

`composer require greeny/nette-slack-logger`

And register extension to your config.neon:

```yaml
extensions:
	slackLogger: greeny\NetteSlackLogger\DI\SlackLoggerExtension
```

By default the logger is just turned off, since you probably do not want to log errors from dev environment. If you want to enable it, add following lines to config.local.neon at your production server:

```yaml
slackLogger:
	enabled: true
	slackUrl: https://hooks.slack.com/services/XXX
	logUrl: http://path/to/your/logs/directory/__FILE__
```

Of course replace `slackUrl` with payload URL from your incomming webhook from Slack.

You can leave `logUrl` empty, but if you have your logs accessible through web (of course e.g. protected by HTTP auth or available only from company IPs), you can define this URL here. `__FILE__` will be replaced by filename of file with exception.
