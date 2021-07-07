<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column\Action\Confirmation;

use Ublaboo\DataGrid\Row;

interface IConfirmation
{

	public function getMessage(Row $row): ?string;
}
