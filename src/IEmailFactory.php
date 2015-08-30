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

interface IEmailFactory
{
	/**
	 * @return Email
	 */
	function create($from, $fromName = FALSE, $locale = NULL);
}
