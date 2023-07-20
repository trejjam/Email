<?php
declare(strict_types=1);

namespace Trejjam\Email\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Trejjam\Email\Send;
use Trejjam\Email\IEmailFactory;

final class EmailExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'templateDirectory' => Expect::string()->default('presenters/templates/emails'),
            'templates' => Expect::arrayOf(
                Expect::structure([
                    'subject' => Expect::string()->nullable()->default(null),
                    'subjectFields' => Expect::string()->nullable()->default(null),
                    'template' => Expect::string()->nullable()->default(null),
                    'requiredFields' => Expect::arrayOf(
                        Expect::string()
                    )->default([]),
                    'useTranslator' => Expect::string()->nullable()->default(null),
                ]),
                Expect::string()
            ),
            'useTranslator' => Expect::bool()->default(false),
            'subjectPrefix' => Expect::string()->default(''),
        ]);
    }

    public function beforeCompile(): void
    {
        parent::beforeCompile();

        $builder = $this->getContainerBuilder();

        $templateDirectory = $this->getContainerBuilder()->parameters['appDir'] . DIRECTORY_SEPARATOR . $this->config->templateDirectory;

        $builder->addDefinition($this->prefix('send'))
            ->setType(Send::class)
            ->setArguments([
                'templateDirectory' => $templateDirectory,
                'templates' => $this->config->templates,
                'useTranslator' => $this->config->useTranslator,
                'subjectPrefix' => $this->config->subjectPrefix,
            ]);

        $builder->addFactoryDefinition($this->prefix('emailFactory'))
            ->setImplement(IEmailFactory::class);
    }

}
