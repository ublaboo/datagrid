<?php declare(strict_types = 1);

namespace Ublaboo\DataGrid\Utils;

use Nette\Database\Table\Selection;
use ReflectionClass;

final class NetteDatabaseSelectionHelper
{

	public static function getDriver(Selection $selection)
	{
		$connection = self::getContext($selection)->getConnection();

		return $connection->getSupplementalDriver();
	}


	public static function getContext(Selection $selection)
	{
		$reflection = new ReflectionClass($selection);

		$context_property = $reflection->getProperty('context');
		$context_property->setAccessible(true);

		return $context_property->getValue($selection);
	}

}
