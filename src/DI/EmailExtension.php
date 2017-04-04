<?php

namespace Trejjam\Email\DI;

use Nette;
use Trejjam;

class EmailExtension extends Trejjam\BaseExtension\DI\BaseExtension
{
	protected $default = [
		'templateDirectory' => '%appDir%/presenters/templates/emails',
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
		'send' => 'Trejjam\Email\Send',
	];

	protected $factoriesDefinition = [
		'emailFactory' => 'Trejjam\Email\IEmailFactory',
	];

	/**
	 * @return array
	 * @throws Nette\Utils\AssertionException
	 */
	protected function createConfig()
	{
		$originalConfig = $this->config;

		if (count($originalConfig['templates'])) {

			foreach ($originalConfig['templates'] as $k => $v) {
				$this->default['templates'][$k] = $this->templates;
			}
		}

		$config = parent::createConfig();

		Nette\Utils\Validators::assert($config, 'array');

		return $config;
	}

	public function beforeCompile()
	{
		parent::beforeCompile();

		$config = $this->createConfig();

		$classes = $this->getClasses();

		$this->registerEmailFactory($classes['send'], $config);
	}

	/**
	 * @param Nette\DI\ServiceDefinition $factory
	 * @param array                      $config
	 */
	public function registerEmailFactory(Nette\DI\ServiceDefinition $factory, $config)
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
