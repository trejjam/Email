<?php
declare(strict_types=1);

namespace Trejjam\Email;

use Nette\Mail\MimePart;
use Nette\Application\LinkGenerator;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Http\UrlScript;
use Nette\Http\Request;
use Nette\Utils\Validators;
use stdClass;

class Email
{
    protected UrlScript $refUrl;

    protected string|null $to;
    protected string|null $toName;
    protected string|null $replyTo = null;
    protected string|null $replyToName;
    /**
     * @var string|string[]|null
     */
    protected string|array|null $subject = null;
    protected string|null $subjectDefault = '';
    protected array|null $subjectArgs;

    protected string|null $content = null;

    protected string|null $template;
    protected array $templateArgs = [];
    protected array $templateArgsMinimum = [];

    protected string|null $unsubscribeEmail = null;
    protected string|null $unsubscribeLink = null;
    protected array $attachments = [];
    /**
     * @var MimePart[]
     */
    protected array $inlinePart = [];

    /**
     * @var callable[]
     */
    protected array $latteSetupFilterCallback = [];

    public function __construct(
        private string                 $from,
        private string|null            $fromName,
        private readonly LatteFactory  $latteFactory,
        private readonly LinkGenerator $linkGenerator,
        Request                        $httpRequest
    )
    {
        $this->from($from, $fromName);
        $this->refUrl = $httpRequest->getUrl();
    }

    /**
     * @throws EmailException
     */
    public function unsubscribeEmail(string $unsubscribeEmail): self
    {
        if (!Validators::isEmail($unsubscribeEmail)) {
            throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
        }

        $this->unsubscribeEmail = $unsubscribeEmail;

        return $this;
    }

    public function unsubscribeLink(string $unsubscribeLink): self
    {
        if (!Validators::isUrl($unsubscribeLink)) {
            throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
        }

        $this->unsubscribeLink = $unsubscribeLink;

        return $this;
    }

    public function from(string $from, string $name = null): self
    {
        if (!Validators::isEmail($from)) {
            throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
        }

        $this->from = $from;
        $this->fromName = $name;

        return $this;
    }

    public function to(string $to, string $name = null): self
    {
        if (!Validators::isEmail($to)) {
            throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
        }

        $this->to = $to;
        $this->toName = $name;

        return $this;
    }

    public function replyTo(string $to, string $name = null): self
    {
        if (!Validators::isEmail($to)) {
            throw new EmailException('Email is not valid', EmailException::INVALID_EMAIL);
        }

        $this->replyTo = $to;
        $this->replyToName = $name;

        return $this;
    }

    /**
     * @internal
     */
    public function defaultSubject(string $subject): self
    {
        $this->subjectDefault = $subject;

        return $this;
    }

    /**
     * @param string|string[] $subject
     */
    public function subject(string|array $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string[] $args
     *
     * @internal
     */
    public function subjectArgs(array $args = null): self
    {
        $this->subjectArgs = $args;

        return $this;
    }

    protected function getSubject(): string
    {
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
        } else if (is_null($this->subject)) {
            return $this->subjectDefault;
        } else if (is_array($this->subject)) {
            return call_user_func_array('sprintf', array_merge([$this->subjectDefault], $this->subject));
        } else {
            return $this->subject;
        }
    }

    function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    function templateArgs(array $args): self
    {
        $this->templateArgs = $args;

        return $this;
    }

    /**
     * @internal
     */
    function templateArgsMinimum($args): self
    {
        $this->templateArgsMinimum = $args;

        return $this;
    }

    public function addLatteSetupFilterCallback(callable $callback): void
    {
        $this->latteSetupFilterCallback[] = $callback;
    }

    public function addAttachment($file, $content = NULL, $contentType = NULL): void
    {
        $this->attachments[] = [$file, $content, $contentType];
    }

    public function addInlinePart(MimePart $part): void
    {
        $this->inlinePart[] = $part;
    }

    function get(bool $validate = TRUE, bool $parseSubject = true): stdClass
    {
        $required = [
            'from',
            'to',
            'subject',
            'content',
        ];
        $args = [
            'from' => $this->from,
            'fromName' => $this->fromName,
            'to' => $this->to,
            'toName' => $this->toName,
            'replyTo' => is_null($this->replyTo) ? $this->from : $this->replyTo,
            'replyToName' => is_null($this->replyTo) ? $this->fromName : $this->replyToName,
            'subject' => $parseSubject ? $this->getSubject() : $this->subjectDefault,
            'content' => $this->content,
            'attachments' => $this->attachments,
            'inlinePart' => $this->inlinePart,
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
                UIMacros::install($latte->getCompiler());

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

