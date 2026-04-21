<?php
declare(strict_types=1);

namespace Trejjam\Email;

interface IEmailFactory
{
	function create(string $from, string|null $fromName = null) : Email;
}
