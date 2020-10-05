<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\AggregationFunction;

interface ISingleColumnAggregationFunction extends IAggregationFunction
{

	/**
	 * @return mixed
	 */
	public function renderResult();
}
