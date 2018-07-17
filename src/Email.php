<?php
declare(strict_types=1);

namespace Trejjam\Email;

use Nette;
use Nette\Mail\MimePart;
use Trejjam;

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
	/**
	 * @var Nette\Http\UrlScript
	 */
	protected $refUrl;

	protected $from;
	protected $fromName;
	protected $to;
	protected $toName;
	protected $replyTo;
	protected $replyToName;
	protected $subject;
	protected $subjectDefault = '';
	protected $subjectArgs;

	protected $content;

	protected $template;
	protected $templateArgs        = [];
	protected $templateArgsMinimum = [];

	protected $unsubscribeEmail;
	protected $unsubscribeLink;
	/**
	 * @var array
	 */
	protected $attachments = [];
	/**
	 * @var MimePart[]
	 */
	protected $inlinePart = [];

	/**
	 * @var callable[]
	 */
	protected $latteSetupFilterCallback = [];

	public function __construct(
		string $from,
		string $fromName = NULL,
		Nette\Bridges\ApplicationLatte\ILatteFactory $latteFactory,
		Nette\Application\LinkGenerator $linkGenerator,
		Nette\Http\Request $httpRequest
	) {
		$this->from($from, $fromName);
		$this->latteFactory = $latteFactory;
		$this->linkGenerator = $linkGenerator;
		$this->refUrl = $httpRequest->getUrl();
	}

	/**
	 * @param string $unsubscribeEmail
	 *
	 * @return $this
	 * @throws EmailException
	 */
	public function unsubscribeEmail($unsubscribeEmail) : self
	{
		if ( !Nette\Utils\Validators::isEmail($unsubscribeEmail)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->unsubscribeEmail = $unsubscribeEmail;

		return $this;
	}

	public function unsubscribeLink(string $unsubscribeLink) : self
	{
		if ( !Nette\Utils\Validators::isUrl($unsubscribeLink)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->unsubscribeLink = $unsubscribeLink;

		return $this;
	}

	public function from(string $from, string $name = NULL) : self
	{
		if ( !Nette\Utils\Validators::isEmail($from)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->from = $from;
		$this->fromName = $name;

		return $this;
	}

	public function to(string $to, string $name = NULL) : self
	{
		if ( !Nette\Utils\Validators::isEmail($to)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->to = $to;
		$this->toName = $name;

		return $this;
	}

	public function replyTo(string $to, string $name = NULL) : self
	{
		if ( !Nette\Utils\Validators::isEmail($to)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->replyTo = $to;
		$this->replyToName = $name;

		return $this;
	}

	/**
	 * @param string $subject
	 *
	 * @return $this
	 * @internal
	 */
	public function defaultSubject(string $subject) : self
	{
		$this->subjectDefault = $subject;

		return $this;
	}

	/**
	 * @param string|string[] $subject
	 *
	 * @return $this
	 */
	public function subject($subject) : self
	{
		$this->subject = $subject;

		return $this;
	}

	/**
	 * @param string[] $args
	 *
	 * @return $this
	 * @internal
	 */
	public function subjectArgs(array $args = NULL) : self
	{
		$this->subjectArgs = $args;

		return $this;
	}

	protected function getSubject() : string
	{
		if (is_array($this->subjectArgs) && !is_array($this->subject)) {
			$thisValues = ((array)$this->get(FALSE, FALSE)) + $this->templateArgs;

			$subjectFields = [$this->subjectDefault];
			foreach ($this->subjectArgs as $v) {
				if ( !isset($thisValues[$v])) {
					trigger_error('Missing ' . $v);
					$thisValues[$v] = '';
				}
				$subjectFields[] = $thisValues[$v];
			}

			return call_user_func_array('sprintf', $subjectFields);
		}
		else if (is_null($this->subject)) {
			return $this->subjectDefault;
		}
		else if (is_array($this->subject)) {
			return call_user_func_array('sprintf', array_merge([$this->subjectDefault], $this->subject));
		}
		else {
			return $this->subject;
		}
	}

	function content(string $content) : self
	{
		$this->content = $content;

		return $this;
	}

	function template(string $template) : self
	{
		$this->template = $template;

		return $this;
	}

	function templateArgs(array $args) : self
	{
		$this->templateArgs = $args;

		return $this;
	}

	/**
	 * @param $args
	 *
	 * @return $this
	 *
	 * @internal
	 */
	function templateArgsMinimum($args) : self
	{
		$this->templateArgsMinimum = $args;

		return $this;
	}

	public function addLatteSetupFilterCallback(callable $callback) : void
	{
		$this->latteSetupFilterCallback[] = $callback;
	}

	public function addAttachment($file, $content = NULL, $contentType = NULL) : void
	{
		$this->attachments[] = [$file, $content, $contentType];
	}

	public function addInlinePart(MimePart $part) : void
	{
		$this->inlinePart[] = $part;
	}

	function get(bool $validate = TRUE, bool $parseSubject = TRUE)
	{
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
			'subject'     => $parseSubject ? $this->getSubject() : $this->subjectDefault,
			'content'     => $this->content,
			'attachments' => $this->attachments,
			'inlinePart'  => $this->inlinePart,
		];
		if ( !is_null($this->unsubscribeEmail)) {
			$args['unsubscribeEmail'] = $this->unsubscribeEmail;
		}
		if ( !is_null($this->unsubscribeLink)) {
			$args['unsubscribeLink'] = $this->unsubscribeLink;
		}

		if (is_null($this->content) && !is_null($this->template)) {
			foreach ($this->templateArgsMinimum as $v) {
				if ( !isset($this->templateArgs[$v])) {
					if ($validate) {
						trigger_error('Missing templateArgs.' . $v);
						$this->templateArgs[$v] = '';
					}
				}
			}

			if ($validate) {
				$latte = $this->latteFactory->create();

				$latte->addProvider('uiControl', $this->linkGenerator);
				$latte->addProvider('uiPresenter', $this->linkGenerator);
				$args['_url'] = $this->refUrl;
				Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

				foreach ($this->latteSetupFilterCallback as $v) {
					$v($latte);
				}

				$args['content'] = $latte->renderToString($this->template, $args + $this->templateArgs);
			}
		}

		foreach ($required as $v) {
			if (is_null($args[$v])) {
				if ($validate) {
					trigger_error('Missing ' . $v);
					$args[$v] = '';
				}
			}
		}

		return (object)$args;
	}
}

