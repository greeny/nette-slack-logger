<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger\DI;

use greeny\NetteSlackLogger\MessageFactory;
use greeny\NetteSlackLogger\SlackLogger;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
use Tracy\Debugger;


class SlackLoggerExtension extends CompilerExtension
{


	private $defaults = [
		'enabled' => FALSE,
		'messageFactory' => MessageFactory::class,
		'defaults' => [
			'channel' => NULL,
			'icon' => NULL,
			'name' => NULL,
			'title' => NULL,
			'text' => NULL,
			'color' => NULL,
		],
	];


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		Validators::assertField($config, 'enabled', 'boolean');
		Validators::assertField($config, 'messageFactory', 'string');

		if ($config['enabled']) {
			Validators::assertField($config, 'slackUrl', 'string');
			Validators::assertField($config, 'logUrl', 'string');
		}

		$builder->addDefinition($this->prefix('messageFactory'))
			->setClass($config['messageFactory'])
			->setArguments([$config['defaults'], $config['logUrl']]);
	}


	public function afterCompile(ClassType $class)
	{
		$config = $this->getConfig($this->defaults);

		if ($config['enabled']) {
			$init = $class->getMethod('initialize');
			$init->addBody(Debugger::class . '::setLogger(new ' . SlackLogger::class . '(?, $this->getService(?)));', [$config['slackUrl'], $this->prefix('messageFactory')]);
		}
	}


}
