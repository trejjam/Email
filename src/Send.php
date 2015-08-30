<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 14.2.15
 * Time: 21:53
 */

namespace Trejjam\Email;


use Nette,
	Trejjam;

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
	 * @var IEmailFactory
	 */
	protected $emailFactory;
	/**
	 * @var Nette\Mail\IMailer
	 */
	protected $mailer;

	/**
	 * @var string
	 */
	protected $locale = NULL;

	function __construct($templateDirectory, $configurations, $useTranslator, IEmailFactory $email, Nette\Mail\IMailer $mailer)
	{
		$this->templateDirectory = $templateDirectory;
		$this->configurations = $configurations;
		$this->emailFactory = $email;
		$this->mailer = $mailer;
		$this->useTranslator = $useTranslator;
	}

	protected function setLocale($locale)
	{
		$this->locale = $locale;
	}

	protected function getLocale($locale = NULL)
	{
		return (!is_null($locale) ? $locale : $this->locale);
	}

	protected function getLocaleDir($configuration, $locale)
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
	 * @param $name
	 * @param $emailFrom
	 * @return Email
	 */
	function getTemplate($name, $emailFrom, $emailFromName = FALSE, $locale = NULL)
	{
		if (isset($this->configurations[$name])) {
			$configuration = $this->configurations[$name];
			$templateFile = is_null($configuration['template']) ? $name : $configuration['template'];

			return $this->emailFactory
				->create(
					$emailFrom,
					$emailFromName,
					$this->useTranslator && $configuration['useTranslator']
						? $this->getLocale($locale)
						: NULL
				)
				->template(
					$this->templateDirectory . '/'
					. $this->getLocaleDir($configuration, $locale)
					. $templateFile
					. '.latte'
				)
				->subject(
					$this->useTranslator
					&& $configuration['useTranslator']
					&& !is_null($this->getLocale($locale))
						? $configuration['locale'][$this->getLocale($locale)]['subject']
						: $configuration['subject']
				)
				->templateArgsMinimum($configuration['requiredFields']);
		}
		else {
			throw new EmailException('Template not exist', EmailException::TEMPLATE_NOT_FOUND);
		}
	}

	function send(Email $email)
	{
		$data = $email->get();

		$mail = new Nette\Mail\Message;
		$mail->setFrom($data->from)
			 ->addTo($data->to)
			 ->setHtmlBody($data->content);

		if (isset($data->unsubscribeEmail) || isset($data->unsubscribeLink)) {
			$mail->setHeader('List-Unsubscribe', (isset($data->unsubscribeEmail) ? '<mailto:' . $data->unsubscribeEmail . '>' : '') . (isset($data->unsubscribeEmail) && isset($data->unsubscribeLink) ? ", " : "") . (isset($data->unsubscribeLink) ? '<' . $data->unsubscribeLink . '>' : ''), TRUE);
		}
		$mail->setSubject($data->subject);

		$this->mailer->send($mail);
	}
}
