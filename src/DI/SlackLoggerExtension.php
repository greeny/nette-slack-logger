<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger\DI;

use greeny\NetteSlackLogger\SlackLogger;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
use Tracy\Debugger;


class SlackLoggerExtension extends CompilerExtension
{


	private $defaults = [
		'enabled' => FALSE,
	];


	public function afterCompile(ClassType $class)
	{
		$config = $this->getConfig($this->defaults);

		Validators::assertField($config, 'enabled', 'boolean');

		if ($config['enabled']) {
			Validators::assertField($config, 'slackUrl', 'string');
			Validators::assertField($config, 'logUrl', 'string');

			$init = $class->getMethod('initialize');
			$init->addBody(Debugger::class . '::setLogger(new ' . SlackLogger::class . '(?, ?));', [$config['slackUrl'], $config['logUrl']]);
		}
	}


}
