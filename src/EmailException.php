<?php
declare(strict_types=1);

namespace Trejjam\Email;

class EmailException extends \LogicException
{
	const INVALID_EMAIL      = 0b0001;
	const EMAIL_NOT_EXIST    = 0b0010;
	const MISS_PARAMETER     = 0b0100;
	const TEMPLATE_NOT_FOUND = 0b1000;
}
