# nette-slack-logger
Log your errors directly into a Slack room

## Installation

`composer require OndrejBouda/nette-slack-logger`

And register extension to your config.neon:

```yaml
extensions:
	slackLogger: OndrejBouda\NetteSlackLogger\DI\SlackLoggerExtension
```

By default the logger is just turned off, since you probably do not want to log errors from dev environment. If you want
to enable it, add following lines to config.local.neon at your production server:

```yaml
slackLogger:
	enabled: true
	slackUrl: https://hooks.slack.com/services/XXX
    logUrl: http://path/to/your/logs/directory/__FILE__
    channel: "#somechannel"
    username: "PHP Bot"
    icon: ":joystick:"
    pretext: "Error at example.com"
```

Details:
- `slackUrl` must contain your incoming webhook URL - see https://api.slack.com/incoming-webhooks.
- `logUrl`, if specified, tells the URL at which the log file will be available. The substring `__FILE__` within the URL
  will be replaced with the actual log file basename. The resulting URL gets appended to the message posted to Slack.
  Note the file should not be available for public as it contains sensitive information. It is your responsibility to
  protect the file, e.g., by HTTP auth or restricting access by IP addresses.
- `channel`: Name or ID of channel to post to. If not specified, the message gets posted to the default channel
  according to the incoming webhook specification.
- `username`: Username to use for the post. Optional.
- `icon`: Icon to use besides the post instead of the default icon. Optional.
- `pretext`: Pretext for the message. Useful for distinguishing, e.g., the site.
