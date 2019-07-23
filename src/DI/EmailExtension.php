<?php
declare(strict_types=1);

namespace Trejjam\Email\DI;

use Nette\DI\ServiceDefinition;
use Trejjam\Email\Send;
use Trejjam\Email\IEmailFactory;
use Trejjam\BaseExtension\DI\BaseExtension;

class EmailExtension extends BaseExtension
{
	protected $default = [
		'templateDirectory' => 'presenters/templates/emails',
		'templates'         => [],
		'useTranslator'     => FALSE,
		'subjectPrefix'     => '',
	];

	protected $templates = [
		'subject'        => NULL,
		'subjectFields'  => NULL,
		'template'       => NULL,
		'requiredFields' => [],
		'useTranslator'  => TRUE,
	];

	protected $classesDefinition = [
		'send' => Send::class,
	];

	protected $factoriesDefinition = [
		'emailFactory' => IEmailFactory::class,
	];

	public function loadConfiguration(bool $validateConfig = TRUE) : void
	{
		$this->default['templateDirectory'] = $this->getContainerBuilder()->parameters['appDir'] . DIRECTORY_SEPARATOR . $this->default['templateDirectory'];

		$config = $this->config;

		if (
			array_key_exists('templates', $config)
			&& is_array($config['templates'])
		) {
			foreach ($config['templates'] as $k => $v) {
				$this->default['templates'][$k] = $this->templates;
			}
		}

		parent::loadConfiguration();
	}

	public function beforeCompile() : void
	{
		parent::beforeCompile();

		$types = $this->getTypes();

		$this->registerEmailFactory($types['send'], $this->config);
	}

	public function registerEmailFactory(ServiceDefinition $factory, array $config)
	{
		$factory->setArguments(
			[
				$config['templateDirectory'],
				$config['templates'],
				$config['useTranslator'],
				$config['subjectPrefix'],
			]
		);
	}
}
