<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 14.2.15
 * Time: 21:54
 */

namespace Trejjam\Email;


use Nette,
	Latte,
	Trejjam;

class Email
{
	/**
	 * @var Nette\Bridges\ApplicationLatte\ILatteFactory
	 */
	protected $latteFactory;
	/**
	 * @var Nette\Application\LinkGenerator
	 */
	protected $linkGenerator;

	protected $from;
	protected $fromName    = NULL;
	protected $to          = NULL;
	protected $toName      = NULL;
	protected $replyTo     = NULL;
	protected $replyToName = NULL;
	protected $subject     = "";

	protected $content = NULL;

	protected $template            = NULL;
	protected $templateArgs        = [];
	protected $templateArgsMinimum = [];

	protected $unsubscribeEmail = NULL;
	protected $unsubscribeLink  = NULL;
	/**
	 * @var array
	 */
	protected $attachments = [];

	function __construct($from, $fromName = NULL, Nette\Bridges\ApplicationLatte\ILatteFactory $latteFactory, Nette\Application\LinkGenerator $linkGenerator) {
		$this->from($from, $fromName);
		$this->latteFactory = $latteFactory;
		$this->linkGenerator = $linkGenerator;
	}

	/**
	 * @param string $unsubscribeEmail
	 * @return $this
	 * @throws EmailException
	 */
	public function unsubscribeEmail($unsubscribeEmail) {
		if (!Nette\Utils\Validators::isEmail($unsubscribeEmail)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->unsubscribeEmail = $unsubscribeEmail;

		return $this;
	}
	/**
	 * @param string $unsubscribeLink
	 * @return $this
	 * @throws EmailException
	 */
	public function unsubscribeLink($unsubscribeLink) {
		if (!Nette\Utils\Validators::isUrl($unsubscribeLink)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->unsubscribeLink = $unsubscribeLink;

		return $this;
	}
	/**
	 * @param string             $from
	 * @param bool(false)|string $name
	 * @return $this
	 * @throw EmailException
	 */
	function from($from, $name = NULL) {
		if (!Nette\Utils\Validators::isEmail($from)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->from = $from;
		$this->name = $name;

		return $this;
	}
	/**
	 * @param string             $to
	 * @param bool(false)|string $name
	 * @return $this
	 * @throw EmailException
	 */
	function to($to, $name = NULL) {
		if (!Nette\Utils\Validators::isEmail($to)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->to = $to;
		$this->name = $name;

		return $this;
	}
	/**
	 * @param string             $to
	 * @param bool(false)|string $name
	 * @return $this
	 * @throw EmailException
	 */
	function replyTo($to, $name = NULL) {
		if (!Nette\Utils\Validators::isEmail($to)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->replyTo = $to;
		$this->replyToName = $name;

		return $this;
	}
	/**
	 * @param string $subject
	 * @return $this
	 */
	function subject($subject) {
		$this->subject = $subject;

		return $this;
	}
	/**
	 * @param string $content
	 * @return $this
	 */
	function content($content) {
		$this->content = $content;

		return $this;
	}
	/**
	 * @param string $template
	 * @return $this
	 */
	function template($template) {
		$this->template = $template;

		return $this;
	}
	/**
	 * @param array $args
	 * @return $this
	 */
	function templateArgs(array $args) {
		$this->templateArgs = $args;

		return $this;
	}
	/**
	 * @param $args
	 * @return $this
	 *
	 * @internal
	 */
	function templateArgsMinimum($args) {
		$this->templateArgsMinimum = $args;

		return $this;
	}

	public function addAttachment($file, $content = NULL, $contentType = NULL) {
		$this->attachments[] = [$file, $content, $contentType];
	}

	function get($validate = TRUE) {
		$required = [
			'from',
			'to',
			'subject',
			'content',
		];
		$args = [
			'from'        => $this->from,
			'fromName'    => $this->fromName,
			'to'          => $this->to,
			'toName'      => $this->toName,
			'replyTo'     => is_null($this->replyTo) ? $this->from : $this->replyTo,
			'replyToName' => is_null($this->replyTo) ? $this->fromName : $this->replyToName,
			'subject'     => $this->subject,
			'content'     => $this->content,
			'attachments' => $this->attachments,
		];
		if (!is_null($this->unsubscribeEmail)) {
			$args['unsubscribeEmail'] = $this->unsubscribeEmail;
		}
		if (!is_null($this->unsubscribeLink)) {
			$args['unsubscribeLink'] = $this->unsubscribeLink;
		}

		try {
			if (is_null($this->content) && !is_null($this->template)) {
				foreach ($this->templateArgsMinimum as $v) {
					if (!isset($this->templateArgs[$v])) {
						throw new EmailException('Missing templateArgs.' . $v, EmailException::MISS_PARAMETER);
					}
				}

				$latte = $this->latteFactory->create();

				$args['_control'] = $this->linkGenerator;
				$args['_presenter'] = $this->linkGenerator;
				Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

				$args['content'] = $latte->renderToString($this->template, $args + $this->templateArgs);
			}
		}
		catch (EmailException $e) {
			if ($validate || $e->getCode() != EmailException::MISS_PARAMETER) {
				throw $e;
			}
		}

		if ($validate) {
			if (in_array(NULL, $args)) {
				foreach ($required as $v) {
					if (is_null($args[$v])) {
						throw new EmailException('Missing ' . $v, EmailException::MISS_PARAMETER);
					}
				}
			}
		}

		return (object)$args;
	}
}

