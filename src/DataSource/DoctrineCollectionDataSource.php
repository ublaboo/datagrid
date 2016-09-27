<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Martin Procházka <juniwalk@outlook.cz>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\DataSource;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Ublaboo\DataGrid\Filter;
use Ublaboo\DataGrid\Utils\Sorting;

final class DoctrineCollectionDataSource extends FilterableDataSource implements IDataSource
{

	/**
	 * @var Collection
	 */
	private $data_source;

	/**
	 * @var string
	 */
	private $primary_key;

	/**
	 * @var Criteria
	 */
	private $criteria;

	/**
	 * @var array
	 */
	protected $aggregations = [];

	/**
	 * @param Collection  $collection
	 * @param string      $primary_key
	 */
	public function __construct(Collection $collection, $primary_key)
	{
		$this->criteria = Criteria::create();
		$this->data_source = $collection;
		$this->primary_key = $primary_key;
	}


	/**
	 * @return Collection
	 */
	private function getFilteredCollection()
	{
		return $this->data_source->matching($this->criteria);
	}


	/********************************************************************************
	 *                          IDataSource implementation                          *
	 ********************************************************************************/


	/**
	 * Get count of data
	 * @return int
	 */
	public function getCount()
	{
		return $this->getFilteredCollection()->count();
	}


	/**
	 * Get the data
	 * @return array
	 */
	public function getData()
	{
		return $this->getFilteredCollection()->toArray();
	}


	/**
	 * Filter data - get one row
	 * @param array $condition
	 * @return static
	 */
	public function filterOne(array $condition)
	{
		foreach ($condition as $column => $value) {
			$expr = Criteria::expr()->eq($column, $value);
			$this->criteria->andWhere($expr);
		}

		return $this;
	}


	/**
	 * Filter by date
	 * @param  Filter\FilterDate $filter
	 * @return void
	 */
	public function applyFilterDate(Filter\FilterDate $filter)
	{
		foreach ($filter->getCondition() as $column => $value) {
			$date = \DateTime::createFromFormat($filter->getPhpFormat(), $value)
				->format('Y-m-d H:i:s');

			$from = Criteria::expr()->gte($filter->getColumn(), $date->format('Y-m-d 00:00:00'));
			$to = Criteria::expr()->lte($filter->getColumn(), $date->format('Y-m-d 23:59:59'));

			$this->criteria->andWhere($from)->andWhere($to);
		}
	}


	/**
	 * Filter by date range
	 * @param  Filter\FilterDateRange $filter
	 * @return void
	 */
	public function applyFilterDateRange(Filter\FilterDateRange $filter)
	{
		$values = $conditions[$filter->getColumn()];

		if ($value_from = $values['from']) {
			$date_from = \DateTime::createFromFormat($filter->getPhpFormat(), $value_from)
				->setTime(0, 0, 0)
				->format('Y-m-d H:i:s');

			$expr = Criteria::expr()->gte($filter->getColumn(), $date_from);
			$this->criteria->andWhere($expr);
		}

		if ($value_to = $values['to']) {
			$date_to = \DateTime::createFromFormat($filter->getPhpFormat(), $value_to)
				->setTime(23, 59, 59)
				->format('Y-m-d H:i:s');

			$expr = Criteria::expr()->lte($filter->getColumn(), $date_to);
			$this->criteria->andWhere($expr);
		}
	}


	/**
	 * Filter by range
	 * @param  Filter\FilterRange $filter
	 * @return void
	 */
	public function applyFilterRange(Filter\FilterRange $filter)
	{
		$values = $conditions[$filter->getColumn()];

		if ($value_from = $values['from']) {
			$expr = Criteria::expr()->gte($filter->getColumn(), $value_from);
			$this->criteria->andWhere($expr);
		}

		if ($value_to = $values['to']) {
			$expr = Criteria::expr()->lte($filter->getColumn(), $value_to);
			$this->criteria->andWhere($expr);
		}
	}


	/**
	 * Filter by keyword
	 * @param  Filter\FilterText $filter
	 * @return void
	 */
	public function applyFilterText(Filter\FilterText $filter)
	{
		$exprs = [];

		foreach ($filter->getCondition() as $column => $value) {
			if($filter->isExactSearch()) {
				$exprs[] = Criteria::expr()->eq($column, $value);
				continue;
			}

			if ($filter->hasSplitWordsSearch() === FALSE) {
				$words = [$value];
			} else {
				$words = explode(' ', $value);
			}

			foreach ($words as $word) {
				$exprs[] = Criteria::expr()->contains($column, $word);
			}
		}

		$expr = call_user_func_array([Criteria::expr(), 'orX'], $exprs);
		$this->criteria->andWhere($expr);
	}


	/**
	 * Filter by multi select value
	 * @param  Filter\FilterMultiSelect $filter
	 * @return void
	 */
	public function applyFilterMultiSelect(Filter\FilterMultiSelect $filter)
	{
		$values = $filter->getCondition()[$filter->getColumn()];

		$expr = Criteria::expr()->in($filter->getColumn(), $values);
		$this->criteria->andWhere($expr);
	}


	/**
	 * Filter by select value
	 * @param  Filter\FilterSelect $filter
	 * @return void
	 */
	public function applyFilterSelect(Filter\FilterSelect $filter)
	{
		foreach ($filter->getCondition() as $column => $value) {
			$expr = Criteria::expr()->eq($column, $value);
			$this->criteria->andWhere($expr);
		}
	}


	/**
	 * Apply limit and offset on data
	 * @param int $offset
	 * @param int $limit
	 * @return static
	 */
	public function limit($offset, $limit)
	{
		$this->criteria->setFirstResult($offset)->setMaxResults($limit);

		return $this;
	}


	/**
	 * Sort data
	 * @param Sorting $sorting
	 * @return static
	 */
	public function sort(Sorting $sorting)
	{
		if (is_callable($sorting->getSortCallback())) {
			call_user_func(
				$sorting->getSortCallback(),
				$this->criteria,
				$sorting->getSort()
			);

			return $this;
		}

		if ($sort = $sorting->getSort()) {
			$this->criteria->orderBy($sort);
			return $this;
		}

		$this->criteria->orderBy([$this->primary_key => 'ASC']);

		return $this;
	}

	/**
	 * @param string $aggregation_type
	 * @param string $column
	 * @return mixed
	 */
	public function addAggregationColumn($aggregation_type, $column)
	{
		$this->aggregations[$column] = $aggregation_type;
	}

	/**
	 * get aggregation row
	 * @return array
	 */
	public function getAggregationData()
	{
		$result = [];
		foreach ($this->data_source as $row) {
			foreach ($this->aggregations as $column => $aggregation_type) {
				switch ($aggregation_type) {
					case ColumnAggregationFunction::$aggregation_type_sum:
					case ColumnAggregationFunction::$aggregation_type_avg:
						if (!isset($result[$column])) {
							$result[$column] = 0;
						}
						$result[$column] += $row[$column];
						break;
					case ColumnAggregationFunction::$aggregation_type_min:
						if (!isset($result[$column]) || $row[$column] < $result[$column]) {
							$result[$column] = $row[$column];
						}
						break;
					case ColumnAggregationFunction::$aggregation_type_max:
						if (!isset($result[$column]) || $row[$column] > $result[$column]) {
							$result[$column] = $row[$column];
						}
						break;
				}
			}
		}

		foreach ($this->aggregations as $column => $aggregation_type) {
			if ($aggregation_type == ColumnAggregationFunction::$aggregation_type_avg) {
				$result[$column] =  $result[$column] / $this->data_source->count();
			}
		}
		return $result;
	}
}
