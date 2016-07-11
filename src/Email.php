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
	/**
	 * @var Nette\Http\UrlScript
	 */
	protected $refUrl;

	protected $from;
	protected $fromName       = NULL;
	protected $to             = NULL;
	protected $toName         = NULL;
	protected $replyTo        = NULL;
	protected $replyToName    = NULL;
	protected $subject        = NULL;
	protected $subjectDefault = '';
	protected $subjectArgs    = NULL;

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

	/**
	 * @var callable[]
	 */
	protected $latteSetupFilterCallback = [];

	function __construct($from, $fromName = NULL, Nette\Bridges\ApplicationLatte\ILatteFactory $latteFactory, Nette\Application\LinkGenerator $linkGenerator, Nette\Http\Request $httpRequest) {
		$this->from($from, $fromName);
		$this->latteFactory = $latteFactory;
		$this->linkGenerator = $linkGenerator;
		$this->refUrl = $httpRequest->getUrl();
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
	 * @internal
	 */
	function defaultSubject($subject) {
		$this->subjectDefault = $subject;

		return $this;
	}
	/**
	 * @param string|string[] $subject
	 * @return $this
	 */
	function subject($subject) {
		$this->subject = $subject;

		return $this;
	}
	/**
	 * @param string[] $args
	 * @return $this
	 * @internal
	 */
	function subjectArgs(array $args = NULL) {
		$this->subjectArgs = $args;

		return $this;
	}
	/**
	 * @return string
	 */
	protected function getSubject() {
		if (is_array($this->subjectArgs) && !is_array($this->subject)) {
			$thisValues = ((array)$this->get(FALSE, FALSE)) + $this->templateArgs;

			$subjectFields = [$this->subjectDefault];
			foreach ($this->subjectArgs as $v) {
				if (!isset($thisValues[$v])) {
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

	public function addLatteSetupFilterCallback(callable $callback)
	{
		$this->latteSetupFilterCallback[] = $callback;
	}

	public function addAttachment($file, $content = NULL, $contentType = NULL) {
		$this->attachments[] = [$file, $content, $contentType];
	}

	function get($validate = TRUE, $parseSubject = TRUE) {
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
		];
		if (!is_null($this->unsubscribeEmail)) {
			$args['unsubscribeEmail'] = $this->unsubscribeEmail;
		}
		if (!is_null($this->unsubscribeLink)) {
			$args['unsubscribeLink'] = $this->unsubscribeLink;
		}


		if (is_null($this->content) && !is_null($this->template)) {
			foreach ($this->templateArgsMinimum as $v) {
				if (!isset($this->templateArgs[$v])) {
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

