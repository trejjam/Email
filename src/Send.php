<?php

namespace Trejjam\Email;

use Nette;
use Trejjam;

class Send
{
	/**
	 * @var string
	 */
	protected $templateDirectory;
	/**
	 * @var array
	 */
	protected $configurations = [];
	/**
	 * @var bool
	 */
	protected $useTranslator;
	/**
	 * @var string
	 */
	protected $subjectPrefix;
	/**
	 * @var IEmailFactory
	 */
	protected $emailFactory;
	/**
	 * @var Nette\Mail\IMailer
	 */
	protected $mailer;
	/**
	 * @var string|null
	 */
	protected $locale = NULL;

	function __construct(
		string $templateDirectory,
		array $configurations,
		bool $useTranslator,
		string $subjectPrefix,
		IEmailFactory $email,
		Nette\Mail\IMailer $mailer
	) {
		$this->templateDirectory = $templateDirectory;
		$this->configurations = $configurations;
		$this->emailFactory = $email;
		$this->mailer = $mailer;
		$this->useTranslator = $useTranslator;
		$this->subjectPrefix = $subjectPrefix;
	}

	protected function setLocale(?string $locale)
	{
		$this->locale = $locale;
	}

	protected function getLocale(?string $locale = NULL)
	{
		return (!is_null($locale) ? $locale : $this->locale);
	}

	protected function getLocaleDir(array $configuration, ?string $locale)
	{
		if (
			$this->useTranslator
			&& $configuration['useTranslator']
			&& !is_null($this->getLocale($locale))
		) {
			return $this->getLocale($locale) . '/';
		}
		else {
			return '';
		}
	}

	/**
	 * @param mixed|null $customAttribute
	 *
	 * @throws EmailException
	 */
	public function getTemplate(
		string $name,
		string $emailFrom,
		string $emailFromName = NULL,
		string $locale = NULL,
		$customAttribute = NULL
	) : Email {
		if (isset($this->configurations[$name])) {
			$configuration = $this->configurations[$name];

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
		}
		else {
			throw new EmailException('Template not exist', EmailException::TEMPLATE_NOT_FOUND);
		}
	}

	/**
	 * @param mixed|null $customAttribute
	 */
	protected function getTemplateFile(
		string $templateName,
		string $locale = NULL,
		$customAttribute = NULL
	) : string {
		$configuration = $this->configurations[$templateName];

		$templateFile = is_null($configuration['template']) ? $templateName : $configuration['template'];

		return $this->templateDirectory . '/'
			. $this->getLocaleDir($configuration, $locale)
			. $templateFile
			. '.latte';
	}

	public function send(Email $email, Nette\Mail\IMailer $mailer = NULL) : void
	{
		$mailer = $mailer ?: $this->mailer;

		$data = $email->get();
		$mail = new Nette\Mail\Message;
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
