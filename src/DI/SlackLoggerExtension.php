<?php
/**
 * @author Tomáš Blatný
 * @author Ondřej Bouda <bouda@edookit.com>
 */
namespace OndrejBouda\NetteSlackLogger\DI;

use OndrejBouda\NetteSlackLogger\SlackLogger;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;
use Tracy\Debugger;


class SlackLoggerExtension extends CompilerExtension
{
	private $defaults = [
		'enabled' => FALSE,
		'logUrl' => NULL,
		'channel' => NULL,
		'username' => NULL,
		'icon' => NULL,
		'pretext' => NULL,
	];


	public function afterCompile(ClassType $class)
	{
		$config = $this->getConfig($this->defaults);

		Validators::assertField($config, 'enabled', 'boolean');

		if ($config['enabled']) {
			Validators::assertField($config, 'slackUrl', 'string');
			Validators::assertField($config, 'logUrl', 'string|null');
			Validators::assertField($config, 'channel', 'string|null');
			Validators::assertField($config, 'username', 'string|null');
			Validators::assertField($config, 'icon', 'string|null');
			Validators::assertField($config, 'pretext', 'string|null');

			$init = $class->getMethod('initialize');
			$init->addBody('?::setLogger(new ?(?, ?, ?, ?, ?, ?));', [
				new PhpLiteral(Debugger::class),
				new PhpLiteral(SlackLogger::class),
				$config['slackUrl'],
				$config['logUrl'],
				$config['channel'],
				$config['username'],
				$config['icon'],
				$config['pretext'],
			]);
		}
	}
}
