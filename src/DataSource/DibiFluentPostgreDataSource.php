<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\DataSource;

use Ublaboo\DataGrid\Filter\FilterText;

class DibiFluentPostgreDataSource extends DibiFluentDataSource
{

	protected function applyFilterText(FilterText $filter): void
	{
		$condition = $filter->getCondition();
		$driver = $this->dataSource->getConnection()->getDriver();
		$or = [];

		foreach ($condition as $column => $value) {

			$column = '[' . $column . ']';

			if ($filter->isExactSearch()) {
				$this->dataSource->where("$column = %s", $value);

				continue;
			}

			$words = $filter->hasSplitWordsSearch() === false ? [$value] : explode(' ', $value);

			foreach ($words as $word) {
				$or[] = "$column ILIKE " . $driver->escapeText('%' . $word . '%');
			}
		}

		if (sizeof($or) > 1) {
			$this->dataSource->where('(%or)', $or);
		} else {
			$this->dataSource->where($or);
		}
	}
}
