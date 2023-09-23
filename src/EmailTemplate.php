<?php
declare(strict_types=1);

namespace Trejjam\Email;

class EmailTemplate
{
    public string|null $subject;

    /** @var array<string, EmailSubjectTemplate>|null */
    public array|null $locale;

    public string|null $subjectFields;

    public string|null $template;

    /** @var array<string>|null */
    public array|null $requiredFields;

    public string|null $useTranslator;
}