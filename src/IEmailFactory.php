<?php
declare(strict_types=1);

namespace Trejjam\Email;

use Trejjam;

interface IEmailFactory
{
	function create(string $from, string $fromName = null) : Email;
}
