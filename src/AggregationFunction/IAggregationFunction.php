<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\AggregationFunction;

use Dibi\Fluent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Nette\Database\Table\Selection;

interface IAggregationFunction
{

	public const DATA_TYPE_ALL = 'data_type_all';
	public const DATA_TYPE_FILTERED = 'data_type_filtered';
	public const DATA_TYPE_PAGINATED = 'data_type_paginated';

	public function getFilterDataType(): string;


	/**
	 * @param Fluent|QueryBuilder|Collection|Selection $dataSource
	 */
	public function processDataSource($dataSource): void;
}
