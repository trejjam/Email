<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam\Email\DI;

use Nette,
	Trejjam;

class EmailExtension extends Trejjam\BaseExtension\DI\BaseExtension
{
	protected $default = [
		'templateDirectory' => '%appDir%/presenters/templates/emails',
		'templates'         => [],
		'useTranslator'     => FALSE,
	];

	protected $templates = [
		'subject'        => NULL,
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
	protected function createConfig() {
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

	public function beforeCompile() {
		parent::beforeCompile();

		$config = $this->createConfig();

		$classes = $this->getClasses();

		$classes['send']->setArguments([
			$config['templateDirectory'],
			$config['templates'],
			$config['useTranslator'],
		]);
	}
}
