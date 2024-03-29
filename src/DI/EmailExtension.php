<?php
declare(strict_types=1);

namespace Trejjam\Email\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Trejjam\Email\EmailSubjectTemplate;
use Trejjam\Email\EmailTemplate;
use Trejjam\Email\Send;
use Trejjam\Email\IEmailFactory;

final class EmailExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'templateDirectory' => Expect::string()->default('presenters/templates/emails'),
            'templates' => Expect::arrayOf(
                Expect::from(new EmailTemplate, [
                    'locale' => Expect::arrayOf(
                        Expect::from(new EmailSubjectTemplate),
                    ),
                    'subject' => Expect::string()->nullable()->default(null),
                    'subjectFields' => Expect::string()->nullable()->default(null),
                    'template' => Expect::string()->nullable()->default(null),
                    'requiredFields' => Expect::arrayOf(
                        Expect::string()
                    )->default([]),
                    'useTranslator' => Expect::bool()->nullable()->default(null),
                ]),
                Expect::string(),
            ),
            'useTranslator' => Expect::bool()->default(false),
            'subjectPrefix' => Expect::string()->default(''),
        ]);
    }

    public function beforeCompile(): void
    {
        parent::beforeCompile();

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('send'))
            ->setType(Send::class)
            ->setArguments([
                'templateDirectory' => $this->config->templateDirectory,
                'templates' => $this->config->templates,
                'useTranslator' => $this->config->useTranslator,
                'subjectPrefix' => $this->config->subjectPrefix,
            ]);

        $builder->addFactoryDefinition($this->prefix('emailFactory'))
            ->setImplement(IEmailFactory::class);
    }

}
