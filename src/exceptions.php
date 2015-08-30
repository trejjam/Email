<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 13.2.15
 * Time: 16:54
 */

namespace Trejjam\Email;


use Nette,
	Trejjam;

interface Exception
{

}

interface LogicalException extends Exception
{

}

class EmailException extends \LogicException implements LogicalException
{
	const
		INVALID_EMAIL = 0b0001,
		EMAIL_NOT_EXIST = 0b0010,
		MISS_PARAMETER = 0b0100,
		TEMPLATE_NOT_FOUND = 0b1000;
}
