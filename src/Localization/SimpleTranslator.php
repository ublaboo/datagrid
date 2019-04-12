<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Localization;

use Nette;
use Nette\SmartObject;

class SimpleTranslator extends BaseSimpleTranslator
{

	/**
	 * Translates the given string
	 *
	 * @param  string
	 * @param  int
	 */
	public function translate($message, $count = null): string
	{
		return isset($this->dictionary[$message]) ? $this->dictionary[$message] : $message;
	}

}
