<?php
declare(strict_types=1);

namespace Trejjam\Email;

use Trejjam;

interface IEmailFactory
{
	/**
	 * @param string      $from
	 * @param string|null $fromName
	 * @return Email
	 */
	function create(string $from, string $fromName = NULL);
}
