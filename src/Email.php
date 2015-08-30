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
	protected $fromName = FALSE;
	protected $to       = NULL;
	protected $toName   = FALSE;
	protected $subject  = "";

	protected $content = NULL;

	protected $template            = NULL;
	protected $templateArgs        = [];
	protected $templateArgsMinimum = [];

	protected $unsubscribeEmail = NULL;
	protected $unsubscribeLink  = NULL;

	function __construct($from, $fromName = FALSE, Nette\Bridges\ApplicationLatte\ILatteFactory $latteFactory, Nette\Application\LinkGenerator $linkGenerator)
	{
		$this->from($from, $fromName);
		$this->latteFactory = $latteFactory;
		$this->linkGenerator = $linkGenerator;
	}

	/**
	 * @param string $unsubscribeEmail
	 * @return $this
	 * @throws EmailException
	 */
	public function unsubscribeEmail($unsubscribeEmail)
	{
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
	public function unsubscribeLink($unsubscribeLink)
	{
		if (!Nette\Utils\Validators::isUrl($unsubscribeLink)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->unsubscribeLink = $unsubscribeLink;

		return $this;
	}
	/**
	 * @param string $from
	 * @return $this
	 * @throw EmailException
	 */
	function from($from, $name = FALSE)
	{
		if (!Nette\Utils\Validators::isEmail($from)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->from = $from;
		$this->name = $name;

		return $this;
	}
	/**
	 * @param string $to
	 * @return $this
	 * @throw EmailException
	 */
	function to($to, $name = FALSE)
	{
		if (!Nette\Utils\Validators::isEmail($to)) {
			throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
		}

		$this->to = $to;
		$this->name = $name;

		return $this;
	}
	/**
	 * @param string $subject
	 * @return $this
	 */
	function subject($subject)
	{
		$this->subject = $subject;

		return $this;
	}
	/**
	 * @param string $content
	 * @return $this
	 */
	function content($content)
	{
		$this->content = $content;

		return $this;
	}
	/**
	 * @param string $template
	 * @return $this
	 */
	function template($template)
	{
		$this->template = $template;

		return $this;
	}
	/**
	 * @param array $args
	 * @return $this
	 */
	function templateArgs(array $args)
	{
		$this->templateArgs = $args;

		return $this;
	}
	/**
	 * @param $args
	 * @return $this
	 *
	 * @internal
	 */
	function templateArgsMinimum($args)
	{
		$this->templateArgsMinimum = $args;

		return $this;
	}

	function get()
	{
		$args = [
			'from'    => $this->from,
			'to'      => $this->to,
			'subject' => $this->subject,
			'content' => $this->content,
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
					throw new EmailException('Missing templateArgs.' . $v, EmailException::MISS_PARAMETER);
				}
			}

			$latte = $this->latteFactory->create();

			$args['_control'] = $this->linkGenerator;
			$args['_presenter'] = $this->linkGenerator;
			Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

			$args['content'] = $latte->renderToString($this->template, $args + $this->templateArgs);
		}

		if (in_array(NULL, $args)) {
			foreach ($args as $k => $v) {
				if (is_null($v)) {
					throw new EmailException('Missing ' . $k, EmailException::MISS_PARAMETER);
				}
			}
		}

		return (object)$args;
	}
}

