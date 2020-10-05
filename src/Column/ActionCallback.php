<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column;

use Nette\SmartObject;
use Ublaboo\DataGrid\DataGrid;

/**
 * @method void onClick(mixed $id)
 */
class ActionCallback extends Action
{

	use SmartObject;

	/**
	 * @var callable[]
	 */
	public $onClick;


	/**
	 * Create link to datagrid::handleActionCallback() to fire custom callback
	 */
	protected function createLink(DataGrid $grid, string $href, array $params): string
	{
		/**
		 * Int case of ActionCallback, $this->href is a identifier of user callback
		 */
		$params += ['__key' => $this->href];

		return $this->grid->link('actionCallback!', $params);
	}
}
