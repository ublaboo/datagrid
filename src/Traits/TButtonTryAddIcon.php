<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Traits;

use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

trait TButtonTryAddIcon
{

	/**
	 * Should the element has an icon?
	 * @param  Html            $el
	 * @param  string|null     $icon
	 * @param  string          $name
	 * @return void
	 */
	public function tryAddIcon(Html $el, $icon, $name)
	{
		if ($icon) {
			if (is_object($icon)) {
				$el->addHtml(Html::el('span')->class(DataGrid::$icon_prefix . $icon->class)->setText($icon->content));
			} else {
				$el->addHtml(Html::el('span')->class(DataGrid::$icon_prefix . $icon));
			}

			if (strlen($name)) {
				$el->addHtml('&nbsp;');
			}
		}
	}
}
