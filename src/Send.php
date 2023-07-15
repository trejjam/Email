<?php

namespace Trejjam\Email;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

class Send
{
    protected string|null $locale = null;

    function __construct(
        private readonly string        $templateDirectory,
        private readonly array         $templates,
        private readonly bool          $useTranslator,
        private readonly string        $subjectPrefix,
        private readonly IEmailFactory $emailFactory,
        private readonly Mailer        $mailer
    )
    {
    }

    protected function setLocale(string|null $locale)
    {
        $this->locale = $locale;
    }

    protected function getLocale(string|null $locale = null)
    {
        return $locale ?? $this->locale;
    }

    protected function getLocaleDir(array $configuration, ?string $locale)
    {
        if (
            $this->useTranslator
            && $configuration['useTranslator']
            && !is_null($this->getLocale($locale))
        ) {
            return $this->getLocale($locale) . '/';
        } else {
            return '';
        }
    }

    /**
     * @throws EmailException
     */
    public function getTemplate(
        string $name,
        string $emailFrom,
        string $emailFromName = null,
        string $locale = null,
               $customAttribute = null
    ): Email
    {
        if (isset($this->templates[$name])) {
            $configuration = $this->templates[$name];

            $template = $this->getTemplateFile($name, $locale, $customAttribute);

            return $this->emailFactory
                ->create(
                    $emailFrom,
                    $emailFromName
                )
                ->template($template)
                ->defaultSubject(
                    $this->useTranslator
                    && $configuration['useTranslator']
                    && !is_null($this->getLocale($locale))
                        ? $configuration['locale'][$this->getLocale($locale)]['subject']
                        : $configuration['subject']
                )
                ->subjectArgs($configuration['subjectFields'])
                ->templateArgsMinimum($configuration['requiredFields']);
        } else {
            throw new EmailException('Template not exist', EmailException::TEMPLATE_NOT_FOUND);
        }
    }

    protected function getTemplateFile(
        string $templateName,
        string $locale = null,
               $customAttribute = null
    ): string
    {
        $configuration = $this->templates[$templateName];

        $templateFile = is_null($configuration['template']) ? $templateName : $configuration['template'];

        return $this->templateDirectory . '/'
            . $this->getLocaleDir($configuration, $locale)
            . $templateFile
            . '.latte';
    }

    public function send(Email $email, Mailer $mailer = null): void
    {
        $mailer = $mailer ?? $this->mailer;

        $data = $email->get();
        $mail = new Message;
        $mail
            ->setFrom($data->from, $data->fromName)
            ->addReplyTo($data->replyTo, $data->replyToName)
            ->addTo($data->to, $data->toName)
            ->setHtmlBody($data->content);

        foreach ($data->attachments as $attachment) {
            call_user_func_array([$mail, 'addAttachment'], $attachment);
        }
        foreach ($data->inlinePart as $inlinePart) {
            $mail->addInlinePart($inlinePart);
        }

        if (isset($data->unsubscribeEmail) || isset($data->unsubscribeLink)) {
            $mail->setHeader('List-Unsubscribe', (isset($data->unsubscribeEmail) ? '<mailto:' . $data->unsubscribeEmail . '>' : '') . (isset($data->unsubscribeEmail) && isset($data->unsubscribeLink) ? ", " : "") . (isset($data->unsubscribeLink) ? '<' . $data->unsubscribeLink . '>' : ''), TRUE);
        }
        $mail->setSubject($this->subjectPrefix . $data->subject);
        $mailer->send($mail);
    }
}
