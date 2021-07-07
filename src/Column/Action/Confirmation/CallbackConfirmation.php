<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column\Action\Confirmation;

use Ublaboo\DataGrid\Row;

final class CallbackConfirmation implements IConfirmation
{

	/**
	 * @var callable
	 */
	private $callback;


	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}


	public function getMessage(Row $row): ?string
	{
		return ($this->callback)($row->getItem());
	}
}
