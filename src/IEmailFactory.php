<?php
declare(strict_types=1);

namespace Trejjam\Email;

interface IEmailFactory
{
	function create(string $from, string $fromName = null) : Email;
}
